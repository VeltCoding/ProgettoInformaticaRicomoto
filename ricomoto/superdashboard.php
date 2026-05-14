<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (!hasRole('admin')) { http_response_code(403); die('Solo admin.'); }
$actionMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $shopId = (int)($_POST['shop_id'] ?? 0);
    if ($action === 'toggle_shop' && $shopId > 0) {
        $stmt = $conn->prepare("UPDATE shop SET status = IF(status='active','suspended','active') WHERE id = ?");
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $stmt->close();
        $actionMsg = 'Stato shop aggiornato.';
    }
    if ($action === 'toggle_featured' && $shopId > 0) {
        $stmt = $conn->prepare('UPDATE shop SET featured = 1 - featured WHERE id = ?');
        $stmt->bind_param('i', $shopId);
        $stmt->execute();
        $stmt->close();
        $actionMsg = 'Shop aggiornato in vetrina.';
    }
}
$stats = [
    'utenti' => (int)$conn->query('SELECT COUNT(*) c FROM utente')->fetch_assoc()['c'],
    'officine' => (int)$conn->query('SELECT COUNT(*) c FROM officina')->fetch_assoc()['c'],
    'shop' => (int)$conn->query('SELECT COUNT(*) c FROM shop')->fetch_assoc()['c'],
    'prodotti' => (int)$conn->query('SELECT COUNT(*) c FROM prodotto')->fetch_assoc()['c'],
];
$shops = $conn->query("SELECT s.*, o.nome AS officina_nome, u.email AS owner_email,
  (SELECT COUNT(*) FROM prodotto p WHERE p.shop_id = s.id) AS totale_prodotti,
  (SELECT COUNT(*) FROM prodotto p WHERE p.shop_id = s.id AND p.stato='disponibile') AS disponibili
  FROM shop s
  JOIN officina o ON o.id = s.officina_id
  JOIN utente u ON u.ID = o.utente_id
  ORDER BY s.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pendingOfficine = $conn->query("SELECT o.id, o.nome, o.iva, o.indirizzo, u.email
  FROM officina o
  JOIN utente u ON u.ID = o.utente_id
  LEFT JOIN shop s ON s.officina_id = o.id
  WHERE s.id IS NULL
  ORDER BY o.id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>SuperDashboard - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container wide-container dashboard-shell">
    <aside class="sidebar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>"><img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto"></a>
      <div class="sidebar-menu">
        <a class="active" href="<?= htmlspecialchars(appUrl('superdashboard.php')) ?>">SuperDashboard</a>
        <a href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>
        <a href="<?= htmlspecialchars(appUrl('logout.php')) ?>">Logout</a>
      </div>
    </aside>
    <main class="content-stack">
      <section class="header-card card">
        <div>
          <div class="kicker">Admin</div>
          <div class="headline">Controllo completo dei tenant Ricomoto</div>
          <div class="small">Monitora shop, officine, prodotti e stato della piattaforma.</div>
        </div>
        <div class="btn-row">
          <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Catalogo globale</a>
        </div>
      </section>
      <?php if ($actionMsg): ?><div class="ok"><?= htmlspecialchars($actionMsg) ?></div><?php endif; ?>
      <section class="stats-grid">
        <div class="stat-card"><div class="small">Utenti</div><div class="stat-number"><?= $stats['utenti'] ?></div></div>
        <div class="stat-card"><div class="small">Officine</div><div class="stat-number"><?= $stats['officine'] ?></div></div>
        <div class="stat-card"><div class="small">Tenant shop</div><div class="stat-number"><?= $stats['shop'] ?></div></div>
        <div class="stat-card"><div class="small">Prodotti</div><div class="stat-number"><?= $stats['prodotti'] ?></div></div>
      </section>
      <section class="card">
        <div class="section-title">Officine senza shop</div>
        <?php if (!$pendingOfficine): ?>
          <p class="mt-12">Tutte le officine hanno già il loro tenant.</p>
        <?php else: ?>
          <div class="stack mt-16">
            <?php foreach ($pendingOfficine as $o): ?>
              <div class="list-item">
                <div class="headline-sm"><?= htmlspecialchars($o['nome']) ?></div>
                <div class="small"><?= htmlspecialchars($o['email']) ?> · IVA <?= htmlspecialchars($o['iva']) ?></div>
                <div class="small"><?= htmlspecialchars($o['indirizzo']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
      <section class="card">
        <div class="section-title">Gestione tenant</div>
        <div class="stack mt-16">
          <?php foreach ($shops as $shop): ?>
            <div class="list-item">
              <div class="topbar-flex">
                <div>
                  <div class="headline-sm"><?= htmlspecialchars($shop['nome']) ?></div>
                  <div class="small">Slug: <?= htmlspecialchars($shop['slug']) ?> · <?= htmlspecialchars($shop['localita']) ?></div>
                  <div class="small">Owner: <?= htmlspecialchars($shop['owner_email']) ?> · Officina: <?= htmlspecialchars($shop['officina_nome']) ?></div>
                  <div class="small">Prodotti: <?= (int)$shop['totale_prodotti'] ?> totali / <?= (int)$shop['disponibili'] ?> disponibili</div>
                  <div class="small">Status: <b><?= htmlspecialchars($shop['status']) ?></b> <?= (int)$shop['featured'] === 1 ? '· In vetrina' : '' ?></div>
                  <a class="link" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>">Apri tenant</a>
                </div>
                <div class="btn-row">
                  <form method="POST"><input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>"><input type="hidden" name="action" value="toggle_shop"><button class="btn btn-ghost" type="submit"><?= $shop['status'] === 'active' ? 'Sospendi' : 'Riattiva' ?></button></form>
                  <form method="POST"><input type="hidden" name="shop_id" value="<?= (int)$shop['id'] ?>"><input type="hidden" name="action" value="toggle_featured"><button class="btn btn-primary" type="submit"><?= (int)$shop['featured'] === 1 ? 'Togli vetrina' : 'Metti in vetrina' ?></button></form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>
</div>
</body>
</html>
