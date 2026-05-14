<?php
require_once __DIR__ . '/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$ordineId = (int)($_GET['id'] ?? $_POST['ordine_id'] ?? 0);
$msg = $_GET['msg'] ?? null;

if ($ordineId <= 0) {
  http_response_code(404);
  die('Ordine non trovato.');
}

$stmt = $conn->prepare(
  'SELECT a.*, p.titolo, p.immagine, o.nome AS nome_officina, u.nome, u.cognome
   FROM acquisto a
   JOIN prodotto p ON p.id = a.prodotto_id
   JOIN officina o ON o.id = a.officina_id
   JOIN utente u ON u.ID = a.utente_id
   WHERE a.id = ?
   LIMIT 1'
);
$stmt->bind_param('i', $ordineId);
$stmt->execute();
$ordine = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ordine) {
  http_response_code(404);
  die('Ordine non trovato.');
}

$currentOfficina = currentOfficina();
$isCliente = (int)$ordine['utente_id'] === $userId;
$isVenditore = $currentOfficina && (int)$currentOfficina['id'] === (int)$ordine['officina_id'];

if (!$isCliente && !$isVenditore) {
  http_response_code(403);
  die('Non puoi accedere a questa chat.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $messaggio = trim($_POST['messaggio'] ?? '');

  if ($messaggio !== '') {
    $stmt = $conn->prepare(
      'INSERT INTO ordine_messaggio(acquisto_id, mittente_utente_id, messaggio)
       VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iis', $ordineId, $userId, $messaggio);
    $stmt->execute();
    $stmt->close();

    if ($isCliente) {
      $notifica = 'Nuovo messaggio cliente per ordine #' . $ordineId . ': "' . $ordine['titolo'] . '"';

      $stmt = $conn->prepare(
        'INSERT INTO notifica(officina_id, prodotto_id, utente_id, messaggio)
         VALUES (?, ?, ?, ?)'
      );
      $stmt->bind_param('iiis', $ordine['officina_id'], $ordine['prodotto_id'], $userId, $notifica);
      $stmt->execute();
      $stmt->close();
    } else {
      $notifica = 'Nuovo messaggio dal venditore per ordine #' . $ordineId . ': "' . $ordine['titolo'] . '"';

      $stmt = $conn->prepare(
        'INSERT INTO notifica_utente(utente_id, prodotto_id, messaggio)
         VALUES (?, ?, ?)'
      );
      $stmt->bind_param('iis', $ordine['utente_id'], $ordine['prodotto_id'], $notifica);
      $stmt->execute();
      $stmt->close();
    }
  }

  header('Location: ' . appUrl('chat_ordine.php?id=' . $ordineId));
  exit;
}

$stmt = $conn->prepare(
  'SELECT om.*, u.nome, u.cognome
   FROM ordine_messaggio om
   JOIN utente u ON u.ID = om.mittente_utente_id
   WHERE om.acquisto_id = ?
   ORDER BY om.created_at ASC'
);
$stmt->bind_param('i', $ordineId);
$stmt->execute();
$messaggi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$backUrl = $isVenditore ? appUrl('ordini_officina.php') : appUrl('ordini.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Chat ordine - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container narrow-container">
    <div class="brandbar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>

      <a class="btn btn-ghost" href="<?= htmlspecialchars($backUrl) ?>">Torna agli ordini</a>
    </div>

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="order-row">
        <img class="order-thumb" src="<?= htmlspecialchars(mediaUrl($ordine['immagine'])) ?>" alt="prodotto">

        <div>
          <div class="kicker">Chat ordine #<?= (int)$ordine['id'] ?></div>
          <h1><?= htmlspecialchars($ordine['titolo']) ?></h1>

          <p>
            Cliente: <?= htmlspecialchars($ordine['nome'] . ' ' . $ordine['cognome']) ?><br>
            Venditore: <?= htmlspecialchars($ordine['nome_officina']) ?><br>
            Stato: <?= htmlspecialchars($ordine['stato_ordine']) ?>
          </p>
        </div>
      </div>
    </section>

    <section class="section card">
      <div class="section-title">Messaggi</div>

      <?php if (!$messaggi): ?>
        <p class="small">Ancora nessun messaggio.</p>
      <?php else: ?>
        <div class="chat-box">
          <?php foreach ($messaggi as $m): ?>
            <?php $mine = (int)$m['mittente_utente_id'] === $userId; ?>

            <div class="chat-message <?= $mine ? 'mine' : 'theirs' ?>">
              <div class="chat-author">
                <?= htmlspecialchars($mine ? 'Tu' : ($m['nome'] . ' ' . $m['cognome'])) ?>
              </div>

              <div class="chat-text">
                <?= nl2br(htmlspecialchars($m['messaggio'])) ?>
              </div>

              <div class="chat-date">
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['created_at']))) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="mt-20">
        <input type="hidden" name="ordine_id" value="<?= (int)$ordine['id'] ?>">

        <div class="field">
          <label>Scrivi un messaggio</label>
          <textarea name="messaggio" rows="4" required></textarea>
        </div>

        <button class="btn btn-primary" type="submit">Invia messaggio</button>
      </form>
    </section>
  </div>
</div>
</body>
</html>