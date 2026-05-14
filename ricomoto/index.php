<?php
require_once __DIR__ . '/auth.php';
$tenantShop = currentTenantShop();
if ($tenantShop) {
    header('Location: ' . appUrl('shop.php'));
    exit;
}
if (isLoggedIn()) {
    header('Location: ' . dashboardTarget());
    exit;
}
$featuredShops = [];
$latestProducts = [];
$totals = ['shop' => 0, 'prodotti' => 0, 'clienti' => 0];
if ($res = $conn->query("SELECT COUNT(*) c FROM shop WHERE status='active'")) {
    $totals['shop'] = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) c FROM prodotto")) {
    $totals['prodotti'] = (int)$res->fetch_assoc()['c'];
}
if ($res = $conn->query("SELECT COUNT(*) c FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name='cliente'")) {
    $totals['clienti'] = (int)$res->fetch_assoc()['c'];
}
$q = "SELECT s.*, o.nome AS officina_nome,
      (SELECT COUNT(*) FROM prodotto p WHERE p.shop_id=s.id AND p.stato='disponibile') AS disponibili
      FROM shop s
      JOIN officina o ON o.id = s.officina_id
      WHERE s.status='active'
      ORDER BY s.featured DESC, s.created_at DESC
      LIMIT 4";
if ($res = $conn->query($q)) {
    $featuredShops = $res->fetch_all(MYSQLI_ASSOC);
}
$q2 = "SELECT p.*, s.nome AS nome_shop, s.slug AS shop_slug
       FROM prodotto p
       LEFT JOIN shop s ON s.id = p.shop_id
       ORDER BY p.created_at DESC
       LIMIT 3";
if ($res = $conn->query($q2)) {
    $latestProducts = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container wide-container">
    <div class="brandbar">
      <a class="brand" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>
      <div class="nav-links">
        <a class="nav-link active" href="<?= htmlspecialchars(appUrl('index.php')) ?>">Home</a>
        <a class="nav-link" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>
        <a class="nav-link" href="<?= htmlspecialchars(appUrl('login.php')) ?>">Accedi</a>
        <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('register.php')) ?>">Registrati</a>
      </div>
    </div>

    <section class="hero-shell">
      <div class="hero-panel">
        <div class="hero-copy">
          <div class="eyebrow">Marketplace moto multi-shop</div>
          <h1>Tutto per la tua moto. <span class="accent">In un solo posto.</span></h1>
          <p>Ricomoto unisce officine e motociclisti. Scopri ora i pezzi giuti per te</p>
          <div class="btn-row mt-20">
            <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Scopri i negozi</a>
            <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('register.php')) ?>">Apri il tuo shop</a>
          </div>
        </div>
      </div>
      <div class="panel" style="padding:26px;border-radius:32px;display:flex;flex-direction:column;justify-content:space-between;gap:18px;">
        <div>
          <div class="kicker">Panoramica piattaforma</div>
        </div>
        <div class="hero-stats">
          <div class="mini-stat"><strong><?= $totals['shop'] ?></strong><span class="small">Shop attivi</span></div>
          <div class="mini-stat"><strong><?= $totals['prodotti'] ?></strong><span class="small">Prodotti</span></div>
          <div class="mini-stat"><strong><?= $totals['clienti'] ?></strong><span class="small">Clienti</span></div>
        </div>
        <div class="feature-card">
          <p style="margin-top:8px">Sempre in crescita!</p>
        </div>
      </div>
    </section>

 

    <section class="section">
      <div class="section-head">
        <div class="section-title">Shop in evidenza</div>
        <a class="link" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Vedi tutto il marketplace</a>
      </div>
      <div class="shop-grid">
        <?php foreach ($featuredShops as $shop): ?>
          <article class="shop-card">
            <div class="shop-logo-box"><?= !empty($shop['logo']) ? '<img class="shop-logo" src="'.htmlspecialchars(mediaUrl($shop['logo'])).'" alt="logo">' : '<div class="shop-logo-fallback">'.htmlspecialchars(strtoupper(substr($shop['nome'],0,1))).'</div>' ?></div>
            <h3><?= htmlspecialchars($shop['nome']) ?></h3>
            <p><?= htmlspecialchars($shop['localita']) ?></p>
            <div class="badge-row">
              <span class="badge"><?= (int)$shop['disponibili'] ?> prodotti disponibili</span>
              <span class="badge">Officina: <?= htmlspecialchars($shop['officina_nome']) ?></span>
            </div>
            <div class="btn-row mt-16">
              <a class="btn btn-ghost" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>" target="_blank">Visita shop</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section">
      <div class="section-head">
        <div class="section-title">Ultimi arrivi</div>
      </div>
      <div class="grid-products">
        <?php foreach ($latestProducts as $p): ?>
          <article class="product-card">
            <div class="product-img"><img src="<?= htmlspecialchars(mediaUrl($p['immagine'])) ?>" alt="<?= htmlspecialchars($p['titolo']) ?>"></div>
            <div class="product-body">
              <div class="product-title"><?= htmlspecialchars($p['titolo']) ?></div>
              <div class="product-sub">Shop: <?= htmlspecialchars($p['nome_shop'] ?: 'Marketplace') ?></div>
              <div class="product-desc"><?= nl2br(htmlspecialchars($p['descrizione'])) ?></div>
              <div class="product-footer">
                <span class="product-status <?= $p['stato'] === 'venduto' ? 'sold' : 'avail' ?>"><?= htmlspecialchars($p['stato']) ?></span>
                <?php if (!empty($p['shop_slug'])): ?><a class="btn btn-ghost" href="<?= htmlspecialchars(tenantStoreUrl($p['shop_slug'])) ?>" target="_blank">Apri shop</a><?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <footer class="site-footer">
      <div class="footer-panel topbar-flex">
        <div>
          <div class="headline-sm">Ricomoto</div>
          <div class="small">Marketplace dark mode per ricambi, accessori e shop moto multi-tenant.</div>
        </div>
        <div class="btn-row">
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('login.php')) ?>">Accedi</a>
          <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('register.php')) ?>">Inizia ora</a>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>
