<?php
require_once __DIR__ . '/auth.php';

$shop = ensureTenantContextOrRedirect();

if ($shop['status'] !== 'active' && !hasRole('admin')) {
    http_response_code(403);
    die('Questo tenant è sospeso.');
}

$msg = $_GET['msg'] ?? null;
$shopId = (int)$shop['id'];

$stmt = $conn->prepare('SELECT * FROM prodotto WHERE shop_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $shopId);
$stmt->execute();
$prodotti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
  'SELECT AVG(rating) AS media_rating, COUNT(*) AS totale_recensioni
   FROM recensione_shop
   WHERE shop_id = ?'
);
$stmt->bind_param('i', $shopId);
$stmt->execute();
$ratingStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$mediaRating = $ratingStats && $ratingStats['media_rating'] !== null ? round((float)$ratingStats['media_rating'], 1) : null;
$totaleRecensioni = $ratingStats ? (int)$ratingStats['totale_recensioni'] : 0;

$stmt = $conn->prepare(
  'SELECT r.*, u.nome, u.cognome
   FROM recensione_shop r
   JOIN utente u ON u.ID = r.utente_id
   WHERE r.shop_id = ?
   ORDER BY r.updated_at DESC'
);
$stmt->bind_param('i', $shopId);
$stmt->execute();
$recensioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$canBuy = isLoggedIn() && hasPermission('acquisto.crea');
$currentOfficina = currentOfficina();
$isOwnerShop = $currentOfficina && (int)$currentOfficina['id'] === (int)$shop['officina_id'];
$canReview = isLoggedIn() && !$isOwnerShop;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($shop['nome']) ?> - Ricomoto</title>
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
        <?php if (isLoggedIn()): ?>
          <a class="btn btn-ghost" href="<?= htmlspecialchars(dashboardTarget()) ?>">Dashboard</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('login.php')) ?>">Login</a>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>
      </div>
    </div>

    <section class="card">
      <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

      <div class="shop-hero">
        <div class="shop-logo-box large">
          <?= !empty($shop['logo'])
            ? '<img class="shop-logo" src="'.htmlspecialchars(mediaUrl($shop['logo'])).'" alt="logo">'
            : '<div class="shop-logo-fallback">'.htmlspecialchars(strtoupper(substr($shop['nome'],0,1))).'</div>' ?>
        </div>

        <div class="shop-hero-copy">
          <div class="eyebrow">Tenant shop</div>
          <h1 style="font-size:clamp(2rem,4vw,3.2rem);margin-top:14px;">
            <?= htmlspecialchars($shop['nome']) ?>
          </h1>

          <div class="rating-line">
            <?php if ($mediaRating !== null): ?>
              <span class="rating-stars">★</span>
              <strong><?= htmlspecialchars(number_format($mediaRating, 1, ',', '.')) ?>/5</strong>
              <span class="small">(<?= $totaleRecensioni ?> recension<?= $totaleRecensioni === 1 ? 'e' : 'i' ?>)</span>
            <?php else: ?>
              <span class="small">Ancora nessuna recensione</span>
            <?php endif; ?>
          </div>

          <p><?= htmlspecialchars($shop['localita']) ?></p>
          <div class="small">
            <?= nl2br(htmlspecialchars($shop['descrizione'] ?: 'Shop Ricomoto dedicato ai ricambi e accessori moto.')) ?>
          </div>

          <div class="badge-row mt-16">
            <span class="badge"><?= count($prodotti) ?> prodotti pubblicati</span>
            <span class="badge">Status: <?= htmlspecialchars($shop['status']) ?></span>
          </div>
        </div>

        <div class="panel" style="padding:20px;border-radius:26px;min-width:240px;">
          <div class="small">Storefront ufficiale</div>
          <div class="headline-sm mt-12"><?= htmlspecialchars($shop['slug']) ?>.ricomoto</div>
          <p class="mt-12">Sfoglia il catalogo dedicato a questo tenant.</p>
        </div>
      </div>
    </section>

    <section class="section grid-products">
      <?php foreach ($prodotti as $p): ?>
        <?php $isAvailable = $p['stato'] === 'disponibile' && (int)$p['quantita'] > 0; ?>
        <article class="product-card">
          <div class="product-img">
            <img src="<?= htmlspecialchars(mediaUrl($p['immagine'])) ?>" alt="img">
          </div>
          <div class="product-body">
            <div class="product-title"><?= htmlspecialchars($p['titolo']) ?></div>
            <div class="product-desc"><?= nl2br(htmlspecialchars($p['descrizione'])) ?></div>
            <div class="small mt-12">Disponibili: <?= (int)$p['quantita'] ?></div>

            <?php if ((int)$p['trattabile'] === 1): ?>
              <div class="small mt-12">Prezzo trattabile</div>
            <?php endif; ?>

            <div class="product-footer">
              <span class="product-status <?= $isAvailable ? 'avail' : 'sold' ?>">
                <?= $isAvailable ? 'disponibile' : 'venduto' ?>
              </span>
            </div>

            <div class="btn-row mt-16">
              <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$p['id'])) ?>">
                Dettagli
              </a>
            </div>

            <?php if ($canBuy && !$isOwnerShop && $isAvailable): ?>
              <form method="POST" action="<?= htmlspecialchars(appUrl('acquista.php')) ?>" class="mt-16">
                <input type="hidden" name="prodotto_id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-primary" type="submit">Acquista</button>
              </form>

              <form method="POST" action="<?= htmlspecialchars(appUrl('notifica.php')) ?>" class="mt-12">
                <input type="hidden" name="prodotto_id" value="<?= (int)$p['id'] ?>">
                <textarea name="messaggio" rows="2" placeholder="Scrivi la tua richiesta per lo shop..." required></textarea>
                <button class="btn btn-ghost mt-12" type="submit">Chiedi info</button>
              </form>
            <?php elseif (!isLoggedIn() && $isAvailable && (int)$p['trattabile'] === 1): ?>
              <a class="btn btn-ghost mt-16" href="<?= htmlspecialchars(appUrl('login.php') . '?next=' . urlencode($_SERVER['REQUEST_URI'])) ?>">
                Login per trattare
              </a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="section card">
      <div class="section-head">
        <div>
          <div class="kicker">Recensioni</div>
          <h2>Cosa dicono gli utenti di questo shop</h2>
          <p>Valutazioni e commenti lasciati da clienti e officine.</p>
        </div>
      </div>

      <?php if ($canReview): ?>
        <form method="POST" action="<?= htmlspecialchars(appUrl('recensisci_shop.php')) ?>" class="review-form">
          <input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>">

          <div class="field">
            <label>Valutazione</label>
            <select name="rating" required>
              <option value="5">5 stelle</option>
              <option value="4">4 stelle</option>
              <option value="3">3 stelle</option>
              <option value="2">2 stelle</option>
              <option value="1">1 stella</option>
            </select>
          </div>

          <div class="field">
            <label>Commento</label>
            <textarea name="commento" rows="4" placeholder="Scrivi la tua recensione..." required></textarea>
          </div>

          <button class="btn btn-primary" type="submit">Invia recensione</button>
        </form>
      <?php elseif (!isLoggedIn()): ?>
        <div class="alert">Effettua il login per lasciare una recensione.</div>
      <?php else: ?>
        <div class="alert">Non puoi recensire il tuo shop.</div>
      <?php endif; ?>

      <div class="review-list mt-20">
        <?php if (empty($recensioni)): ?>
          <div class="small">Non ci sono ancora recensioni.</div>
        <?php endif; ?>

        <?php foreach ($recensioni as $r): ?>
          <article class="review-card">
            <div class="review-top">
              <strong><?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?></strong>
              <span class="rating-stars"><?= str_repeat('★', (int)$r['rating']) ?></span>
            </div>
            <p><?= nl2br(htmlspecialchars($r['commento'])) ?></p>
            <div class="small">
              Pubblicata il <?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['updated_at']))) ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>
</body>
</html>
