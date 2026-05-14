<?php
require_once __DIR__ . '/auth.php';

$id = (int)($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? null;

if ($id <= 0) {
  http_response_code(404);
  die('Prodotto non trovato.');
}

$stmt = $conn->prepare(
  'SELECT p.*, s.nome AS nome_shop, s.slug AS shop_slug, s.logo, s.localita, o.nome AS nome_officina
   FROM prodotto p
   JOIN shop s ON s.id = p.shop_id
   JOIN officina o ON o.id = p.officina_id
   WHERE p.id = ?
   LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$prodotto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prodotto) {
  http_response_code(404);
  die('Prodotto non trovato.');
}

$currentOfficina = currentOfficina();
$isOwnProduct = $currentOfficina && (int)$currentOfficina['id'] === (int)$prodotto['officina_id'];

$userId = isLoggedIn() ? (int)$_SESSION['user_id'] : 0;
$offertaAccettata = null;

if ($userId > 0) {
  $stmt = $conn->prepare(
    "SELECT *
     FROM offerta_prezzo
     WHERE prodotto_id = ?
     AND utente_id = ?
     AND stato = 'accettata'
     ORDER BY id DESC
     LIMIT 1"
  );
  $stmt->bind_param('ii', $id, $userId);
  $stmt->execute();
  $offertaAccettata = $stmt->get_result()->fetch_assoc() ?: null;
  $stmt->close();
}

$isAvailable = $prodotto['stato'] === 'disponibile' && (int)$prodotto['quantita'] > 0;
$canBuy = isLoggedIn() && hasPermission('acquisto.crea') && !$isOwnProduct && $isAvailable;
$canOffer = isLoggedIn() && !$isOwnProduct && $isAvailable && (int)$prodotto['trattabile'] === 1;

$prezzoMostrato = (float)$prodotto['prezzo'];

if ($offertaAccettata) {
  $prezzoMostrato = (float)$offertaAccettata['prezzo_offerto'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($prodotto['titolo']) ?> - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container wide-container">
    <div class="brandbar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>

      <div class="btn-row">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>

        <?php if (isLoggedIn()): ?>
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('notifiche_utente.php')) ?>">Notifiche</a>
          <a class="btn btn-primary" href="<?= htmlspecialchars(dashboardTarget()) ?>">Dashboard</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('login.php') . '?next=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Login</a>
          <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('register.php') . '?next=' . urlencode($_SERVER['REQUEST_URI'])) ?>">Registrati</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($offertaAccettata && $isAvailable): ?>
      <div class="ok">
        La tua offerta è stata accettata. Puoi acquistare questo prodotto a
        € <?= number_format((float)$offertaAccettata['prezzo_offerto'], 2, ',', '.') ?>.
      </div>
    <?php endif; ?>

    <section class="card">
      <div class="product-page">
        <div>
          <img class="product-page-img" src="<?= htmlspecialchars(mediaUrl($prodotto['immagine'])) ?>" alt="<?= htmlspecialchars($prodotto['titolo']) ?>">
        </div>

        <div>
          <div class="kicker">Pagina prodotto #<?= (int)$prodotto['id'] ?></div>

          <h1><?= htmlspecialchars($prodotto['titolo']) ?></h1>

          <?php if ($offertaAccettata): ?>
            <div class="small">Prezzo originale: € <?= number_format((float)$prodotto['prezzo'], 2, ',', '.') ?></div>
            <div class="price-big">
              € <?= number_format((float)$offertaAccettata['prezzo_offerto'], 2, ',', '.') ?>
            </div>
            <div class="badge">Prezzo offerta accettata</div>
          <?php else: ?>
            <div class="price-big">
              € <?= number_format((float)$prodotto['prezzo'], 2, ',', '.') ?>
            </div>
          <?php endif; ?>

          <?php if ((int)$prodotto['trattabile'] === 1): ?>
            <div class="badge">Prezzo trattabile</div>
          <?php else: ?>
            <div class="badge">Prezzo non trattabile</div>
          <?php endif; ?>

          <p class="mt-16"><?= nl2br(htmlspecialchars($prodotto['descrizione'])) ?></p>

          <div class="small mt-16">
            Shop:
            <a class="link" href="<?= htmlspecialchars(tenantStoreUrl($prodotto['shop_slug'])) ?>">
              <?= htmlspecialchars($prodotto['nome_shop']) ?>
            </a>
            · <?= htmlspecialchars($prodotto['localita']) ?>
          </div>

          <div class="small mt-12">
            Officina: <?= htmlspecialchars($prodotto['nome_officina']) ?>
          </div>

          <div class="small mt-12">
            Quantita disponibile: <?= (int)$prodotto['quantita'] ?>
          </div>

          <div class="mt-20">
            <span class="product-status <?= $isAvailable ? 'avail' : 'sold' ?>">
              <?= $isAvailable ? 'disponibile' : 'venduto' ?>
            </span>
          </div>

          <div class="btn-row mt-20">
            <?php if ($canBuy): ?>
              <?php if ($offertaAccettata): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('acquista.php?prodotto_id=' . (int)$prodotto['id'] . '&offerta_id=' . (int)$offertaAccettata['id'])) ?>">
                  Acquista a € <?= number_format((float)$offertaAccettata['prezzo_offerto'], 2, ',', '.') ?>
                </a>
              <?php else: ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('acquista.php?prodotto_id=' . (int)$prodotto['id'])) ?>">
                  Acquista ora
                </a>
              <?php endif; ?>
            <?php elseif (!isLoggedIn()): ?>
              <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('login.php') . '?next=' . urlencode($_SERVER['REQUEST_URI'])) ?>">
                Login per acquistare
              </a>
            <?php elseif ($isOwnProduct): ?>
              <span class="small">Questo prodotto appartiene alla tua officina.</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <?php if ($canOffer): ?>
      <section class="section card">
        <div class="section-title">Tratta il prezzo</div>
        <p>Invia una proposta all’officina. Se viene accettata, solo tu potrai acquistare il prodotto al prezzo concordato.</p>

        <form method="POST" action="<?= htmlspecialchars(appUrl('offerta.php')) ?>">
          <input type="hidden" name="prodotto_id" value="<?= (int)$prodotto['id'] ?>">

          <div class="field">
            <label>La tua offerta</label>
            <input type="number" name="prezzo_offerto" min="0.01" step="0.01" required>
          </div>

          <div class="field">
            <label>Messaggio</label>
            <textarea name="messaggio" rows="4" placeholder="Scrivi un messaggio per l'officina..."></textarea>
          </div>

          <button class="btn btn-primary" type="submit">Invia offerta</button>
        </form>
      </section>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
