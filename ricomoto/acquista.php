<?php
require_once __DIR__ . '/auth.php';

requirePermission('acquisto.crea');

$userId = (int)$_SESSION['user_id'];
$prodottoId = (int)($_GET['prodotto_id'] ?? $_POST['prodotto_id'] ?? 0);
$offertaId = (int)($_GET['offerta_id'] ?? $_POST['offerta_id'] ?? 0);
$err = null;
$offertaAccettata = null;
$currentUser = currentUser();
$nomeSpedizione = trim(($currentUser['nome'] ?? '') . ' ' . ($currentUser['cognome'] ?? ''));
$telefonoSpedizione = trim($currentUser['telefono'] ?? '');
$indirizzoSpedizione = trim($currentUser['indirizzo'] ?? '');
$cittaSpedizione = '';
$provinciaSpedizione = '';
$capSpedizione = '';
$noteSpedizione = '';

if ($prodottoId <= 0) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Prodotto non valido.'));
  exit;
}

$stmt = $conn->prepare(
  'SELECT p.*, s.nome AS nome_shop, s.slug AS shop_slug, o.nome AS nome_officina
   FROM prodotto p
   JOIN shop s ON s.id = p.shop_id
   JOIN officina o ON o.id = p.officina_id
   WHERE p.id = ?
   LIMIT 1'
);
$stmt->bind_param('i', $prodottoId);
$stmt->execute();
$prodotto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prodotto) {
  header('Location: ' . appUrl('prodotti.php') . '?msg=' . urlencode('Prodotto non trovato.'));
  exit;
}

if ($offertaId > 0) {
  $stmt = $conn->prepare(
    "SELECT *
     FROM offerta_prezzo
     WHERE id = ?
     AND prodotto_id = ?
     AND utente_id = ?
     AND stato = 'accettata'
     LIMIT 1"
  );
  $stmt->bind_param('iii', $offertaId, $prodottoId, $userId);
  $stmt->execute();
  $offertaAccettata = $stmt->get_result()->fetch_assoc() ?: null;
  $stmt->close();

  if (!$offertaAccettata) {
    header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Offerta non valida o non appartenente al tuo account.'));
    exit;
  }
}

$currentOfficina = currentOfficina();

if ($currentOfficina && (int)$currentOfficina['id'] === (int)$prodotto['officina_id']) {
  header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Non puoi acquistare un prodotto della tua officina.'));
  exit;
}

if ($prodotto['stato'] !== 'disponibile' || (int)$prodotto['quantita'] <= 0) {
  header('Location: ' . appUrl('prodotto.php?id=' . $prodottoId) . '&msg=' . urlencode('Prodotto non disponibile.'));
  exit;
}

$prezzoDaPagare = $offertaAccettata ? (float)$offertaAccettata['prezzo_offerto'] : (float)$prodotto['prezzo'];

function onlyDigits(string $value): string {
  return preg_replace('/\D+/', '', $value) ?? '';
}

function validCardNumber(string $number): bool {
  $number = onlyDigits($number);

  if (strlen($number) < 13 || strlen($number) > 19) {
    return false;
  }

  $sum = 0;
  $alt = false;

  for ($i = strlen($number) - 1; $i >= 0; $i--) {
    $n = (int)$number[$i];

    if ($alt) {
      $n *= 2;

      if ($n > 9) {
        $n -= 9;
      }
    }

    $sum += $n;
    $alt = !$alt;
  }

  return $sum % 10 === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nomeSpedizione = trim($_POST['nome_spedizione'] ?? '');
  $telefonoSpedizione = trim($_POST['telefono_spedizione'] ?? '');
  $indirizzoSpedizione = trim($_POST['indirizzo_spedizione'] ?? '');
  $cittaSpedizione = trim($_POST['citta_spedizione'] ?? '');
  $provinciaSpedizione = trim($_POST['provincia_spedizione'] ?? '');
  $capSpedizione = trim($_POST['cap_spedizione'] ?? '');
  $noteSpedizione = trim($_POST['note_spedizione'] ?? '');
  $nomeCarta = trim($_POST['nome_carta'] ?? '');
  $numeroCarta = onlyDigits($_POST['numero_carta'] ?? '');
  $scadenza = trim($_POST['scadenza'] ?? '');
  $cvv = onlyDigits($_POST['cvv'] ?? '');

  try {
    if ($nomeSpedizione === '') {
      throw new Exception('Inserisci il nome per la spedizione.');
    }

    if ($telefonoSpedizione === '' || strlen(onlyDigits($telefonoSpedizione)) < 6) {
      throw new Exception('Inserisci un telefono valido per la spedizione.');
    }

    if ($indirizzoSpedizione === '') {
      throw new Exception('Inserisci l indirizzo di spedizione.');
    }

    if ($cittaSpedizione === '') {
      throw new Exception('Inserisci la citta di spedizione.');
    }

    if ($capSpedizione === '' || !preg_match('/^[0-9A-Za-z -]{4,10}$/', $capSpedizione)) {
      throw new Exception('Inserisci un CAP valido.');
    }

    if ($nomeCarta === '') {
      throw new Exception('Inserisci il nome sulla carta.');
    }

    if (!validCardNumber($numeroCarta)) {
      throw new Exception('Numero carta non valido.');
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $scadenza)) {
      throw new Exception('Scadenza non valida. Usa il formato MM/AA.');
    }

    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
      throw new Exception('CVV non valido.');
    }

    if ($numeroCarta === '4000000000000002') {
      throw new Exception('Pagamento rifiutato dalla banca.');
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare(
      'SELECT id, officina_id, prezzo, stato, quantita, titolo
       FROM prodotto
       WHERE id = ?
       FOR UPDATE'
    );
    $stmt->bind_param('i', $prodottoId);
    $stmt->execute();
    $locked = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$locked) {
      throw new Exception('Prodotto non trovato.');
    }

    if ($locked['stato'] !== 'disponibile' || (int)$locked['quantita'] <= 0) {
      throw new Exception('Prodotto già venduto.');
    }

    $prezzoPagato = (float)$locked['prezzo'];
    $offertaUsataId = null;

    if ($offertaId > 0) {
      $stmt = $conn->prepare(
        "SELECT *
         FROM offerta_prezzo
         WHERE id = ?
         AND prodotto_id = ?
         AND utente_id = ?
         AND stato = 'accettata'
         FOR UPDATE"
      );
      $stmt->bind_param('iii', $offertaId, $prodottoId, $userId);
      $stmt->execute();
      $offertaLock = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$offertaLock) {
        throw new Exception('Offerta non valida.');
      }

      $prezzoPagato = (float)$offertaLock['prezzo_offerto'];
      $offertaUsataId = (int)$offertaLock['id'];
    }

    $officinaId = (int)$locked['officina_id'];

    $nuovaQuantita = max(0, (int)$locked['quantita'] - 1);
    $nuovoStato = $nuovaQuantita > 0 ? 'disponibile' : 'venduto';

    $stmt = $conn->prepare('UPDATE prodotto SET quantita = ?, stato = ? WHERE id = ?');
    $stmt->bind_param('isi', $nuovaQuantita, $nuovoStato, $prodottoId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
      'INSERT INTO acquisto(
         prodotto_id, offerta_id, utente_id, officina_id, prezzo_pagato,
         stato_pagamento, stato_ordine,
         nome_spedizione, telefono_spedizione, indirizzo_spedizione,
         citta_spedizione, provincia_spedizione, cap_spedizione, note_spedizione
       )
       VALUES (?, ?, ?, ?, ?, "pagato", "pagato", ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
      'iiiidsssssss',
      $prodottoId,
      $offertaUsataId,
      $userId,
      $officinaId,
      $prezzoPagato,
      $nomeSpedizione,
      $telefonoSpedizione,
      $indirizzoSpedizione,
      $cittaSpedizione,
      $provinciaSpedizione,
      $capSpedizione,
      $noteSpedizione
    );
    $stmt->execute();
    $ordineId = (int)$conn->insert_id;
    $stmt->close();

    if ($offertaUsataId !== null) {
      $stmt = $conn->prepare("UPDATE offerta_prezzo SET stato = 'acquistata' WHERE id = ?");
      $stmt->bind_param('i', $offertaUsataId);
      $stmt->execute();
      $stmt->close();
    }

    if ($nuovaQuantita === 0) {
      $stmt = $conn->prepare(
        "UPDATE offerta_prezzo
         SET stato = 'annullata'
         WHERE prodotto_id = ?
         AND stato IN ('in_attesa', 'accettata')"
      );
      $stmt->bind_param('i', $prodottoId);
      $stmt->execute();
      $stmt->close();
    }

    $messaggioOfficina = 'Nuovo ordine #' . $ordineId . ' per "' . $locked['titolo'] . '" - € ' . number_format($prezzoPagato, 2, ',', '.');

    $stmt = $conn->prepare(
      'INSERT INTO notifica(officina_id, prodotto_id, utente_id, messaggio)
       VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('iiis', $officinaId, $prodottoId, $userId, $messaggioOfficina);
    $stmt->execute();
    $stmt->close();

    $messaggioUtente = 'Hai acquistato "' . $locked['titolo'] . '" a € ' . number_format($prezzoPagato, 2, ',', '.') . '. Ordine #' . $ordineId . '.';

    $stmt = $conn->prepare(
      'INSERT INTO notifica_utente(utente_id, prodotto_id, messaggio)
       VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iis', $userId, $prodottoId, $messaggioUtente);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header('Location: ' . appUrl('ordini.php') . '?msg=' . urlencode('Pagamento completato. Ordine creato!'));
    exit;

  } catch (Throwable $e) {
    try {
      $conn->rollback();
    } catch (Throwable $t) {}

    $err = $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Pagamento - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container narrow-container">
    <div class="brandbar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>

      <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . $prodottoId)) ?>">Torna al prodotto</a>
    </div>

    <section class="card">
      <div class="kicker">Pagamento sicuro demo</div>
      <h1><?= htmlspecialchars($prodotto['titolo']) ?></h1>

      <p>
        Shop: <?= htmlspecialchars($prodotto['nome_shop']) ?><br>
        Officina: <?= htmlspecialchars($prodotto['nome_officina']) ?><br>
        Disponibili: <?= (int)$prodotto['quantita'] ?>
      </p>

      <?php if ($offertaAccettata): ?>
        <div class="small">Pagamento con offerta accettata</div>
      <?php endif; ?>

      <div class="price-big">
        € <?= number_format($prezzoDaPagare, 2, ',', '.') ?>
      </div>

      <?php if ($err): ?>
        <div class="alert"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="POST" class="mt-20">
        <input type="hidden" name="prodotto_id" value="<?= (int)$prodotto['id'] ?>">
        <input type="hidden" name="offerta_id" value="<?= (int)$offertaId ?>">

        <div class="section-title">Dati spedizione</div>

        <div class="field">
          <label>Nome destinatario</label>
          <input type="text" name="nome_spedizione" value="<?= htmlspecialchars($nomeSpedizione) ?>" placeholder="Mario Rossi" required>
        </div>

        <div class="field">
          <label>Telefono</label>
          <input type="text" name="telefono_spedizione" value="<?= htmlspecialchars($telefonoSpedizione) ?>" placeholder="+39 333 1234567" required>
        </div>

        <div class="field">
          <label>Indirizzo</label>
          <input type="text" name="indirizzo_spedizione" value="<?= htmlspecialchars($indirizzoSpedizione) ?>" placeholder="Via Roma 10" required>
        </div>

        <div class="grid2">
          <div class="field">
            <label>Citta</label>
            <input type="text" name="citta_spedizione" value="<?= htmlspecialchars($cittaSpedizione) ?>" placeholder="Milano" required>
          </div>

          <div class="field">
            <label>Provincia</label>
            <input type="text" name="provincia_spedizione" value="<?= htmlspecialchars($provinciaSpedizione) ?>" placeholder="MI">
          </div>
        </div>

        <div class="field">
          <label>CAP</label>
          <input type="text" name="cap_spedizione" value="<?= htmlspecialchars($capSpedizione) ?>" placeholder="20100" maxlength="10" required>
        </div>

        <div class="field">
          <label>Note consegna</label>
          <textarea name="note_spedizione" rows="3" placeholder="Scala, citofono, orari preferiti..."><?= htmlspecialchars($noteSpedizione) ?></textarea>
        </div>

        <div class="section-title mt-20">Pagamento</div>

        <div class="field">
          <label>Nome sulla carta</label>
          <input type="text" name="nome_carta" placeholder="Mario Rossi" required>
        </div>

        <div class="field">
          <label>Numero carta</label>
          <input type="text" name="numero_carta" placeholder="4242424242424242" maxlength="19" required>
        </div>

        <div class="grid2">
          <div class="field">
            <label>Scadenza</label>
            <input type="text" name="scadenza" placeholder="MM/AA" maxlength="5" required>
          </div>

          <div class="field">
            <label>CVV</label>
            <input type="password" name="cvv" placeholder="123" maxlength="4" required>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">
          Paga € <?= number_format($prezzoDaPagare, 2, ',', '.') ?> e crea ordine
        </button>
      </form>

      <div class="small mt-16">
        Test: usa <strong>4242424242424242</strong> per pagamento valido.
        Usa <strong>4000000000000002</strong> per simulare pagamento rifiutato.
        I dati carta non vengono salvati.
      </div>
    </section>
  </div>
</div>
</body>
</html>
