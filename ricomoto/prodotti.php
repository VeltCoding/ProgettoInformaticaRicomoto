<?php
require_once __DIR__ . '/auth.php';

requirePermission('prodotto.leggi');

$msg = $_GET['msg'] ?? null;

$sql = "SELECT p.*, o.nome AS nome_officina, s.nome AS nome_shop, s.slug AS shop_slug
        FROM prodotto p
        JOIN officina o ON o.id = p.officina_id
        LEFT JOIN shop s ON s.id = p.shop_id
        ORDER BY p.created_at DESC";

$prodotti = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$canBuy = hasPermission('acquisto.crea');
$currentOfficina = currentOfficina();
$currentOfficinaId = $currentOfficina ? (int)$currentOfficina['id'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Marketplace - Ricomoto</title>
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
        <a class="btn btn-ghost" href="<?= htmlspecialchars(dashboardTarget()) ?>">Dashboard</a>
      </div>
    </div>

    <section class="card">
      <div class="section-head">
        <div>
          <div class="kicker">Marketplace</div>
          <h2>Catalogo globale Ricomoto</h2>
          <p>Vista cross-tenant di tutti i prodotti pubblicati dagli shop e dalle officine.</p>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="ok"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
    </section>

    <section class="section grid-products">
      <?php foreach ($prodotti as $p): ?>
        <?php $isOwnProduct = $currentOfficinaId > 0 && $currentOfficinaId === (int)$p['officina_id']; ?>
        <?php $isAvailable = $p['stato'] === 'disponibile' && (int)$p['quantita'] > 0; ?>

        <article class="product-card">
          <a href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$p['id'])) ?>" class="product-img">
            <img src="<?= htmlspecialchars(mediaUrl($p['immagine'])) ?>" alt="img">
          </a>

          <div class="product-body">
            <div class="product-title">
              <a class="link" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$p['id'])) ?>">
                <?= htmlspecialchars($p['titolo']) ?>
              </a>
            </div>

            <div class="price-card">
              € <?= number_format((float)$p['prezzo'], 2, ',', '.') ?>
            </div>

            <?php if ((int)$p['trattabile'] === 1): ?>
              <div class="small">Prezzo trattabile</div>
            <?php endif; ?>

            <div class="product-sub">Officina: <?= htmlspecialchars($p['nome_officina']) ?></div>

            <?php if (!empty($p['nome_shop'])): ?>
              <div class="small">
                Shop:
                <a class="link" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($p['shop_slug'])) ?>">
                  <?= htmlspecialchars($p['nome_shop']) ?>
                </a>
              </div>
            <?php endif; ?>

            <div class="product-desc"><?= nl2br(htmlspecialchars($p['descrizione'])) ?></div>

            <div class="small mt-12">Disponibili: <?= (int)$p['quantita'] ?></div>

            <div class="product-footer">
              <span class="product-status <?= $isAvailable ? 'avail' : 'sold' ?>">
                <?= $isAvailable ? 'disponibile' : 'venduto' ?>
              </span>
            </div>

            <div class="btn-row mt-16">
              <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$p['id'])) ?>">
                Dettagli
              </a>

              <?php if ($canBuy && !$isOwnProduct && $isAvailable): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('acquista.php?prodotto_id=' . (int)$p['id'])) ?>">
                  Acquista
                </a>
              <?php endif; ?>
            </div>

            <?php if ($isOwnProduct): ?>
              <div class="small mt-16">Questo prodotto appartiene alla tua officina.</div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </div>
</div>
</body>
</html>
