<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api/jwt.php';

requireLogin();

if (hasRole('admin')) {
  header('Location: ' . appUrl('superdashboard.php'));
  exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$me = currentUser();
$ruoli = $_SESSION['roles'] ?? [];
$permessi = $_SESSION['permissions'] ?? [];
sort($permessi);

if (empty($_SESSION['jwt']) || (int)($_SESSION['jwt_exp'] ?? 0) <= time()) {
  $_SESSION['jwt'] = jwt_sign(['sub' => $userId], 600);
  $_SESSION['jwt_exp'] = time() + 600;
}

$jwt = $_SESSION['jwt'];
$jwtExp = (int)$_SESSION['jwt_exp'];

$shop = currentShop();
$officina = currentOfficina();

$stats = [
  'notifications' => 0,
  'user_notifications' => 0,
  'orders' => 0,
  'products' => 0,
  'available' => 0,
  'sold' => 0
];

$notifiche = [];

if ($officina) {
  $officinaId = (int)$officina['id'];

  if ($res = $conn->query("SELECT COUNT(*) c FROM acquisto WHERE officina_id = {$officinaId}")) {
    $stats['orders'] = (int)$res->fetch_assoc()['c'];
  }

  if ($res = $conn->query("SELECT COUNT(*) c FROM notifica WHERE officina_id = {$officinaId} AND letto = 0")) {
    $stats['notifications'] = (int)$res->fetch_assoc()['c'];
  }

  if ($res = $conn->query("SELECT COUNT(*) c FROM prodotto WHERE officina_id = {$officinaId}")) {
    $stats['products'] = (int)$res->fetch_assoc()['c'];
  }

  if ($res = $conn->query("SELECT COUNT(*) c FROM prodotto WHERE officina_id = {$officinaId} AND stato='disponibile'")) {
    $stats['available'] = (int)$res->fetch_assoc()['c'];
  }

  if ($res = $conn->query("SELECT COUNT(*) c FROM prodotto WHERE officina_id = {$officinaId} AND stato='venduto'")) {
    $stats['sold'] = (int)$res->fetch_assoc()['c'];
  }

  $stmt = $conn->prepare(
    "SELECT n.created_at, n.messaggio AS notifica_messaggio,
            u.nome AS buyer_nome, u.cognome AS buyer_cognome, u.email AS buyer_email,
            p.titolo AS prodotto_titolo
     FROM notifica n
     JOIN utente u ON u.ID = n.utente_id
     JOIN prodotto p ON p.id = n.prodotto_id
     WHERE n.officina_id = ?
     ORDER BY n.created_at DESC
     LIMIT 6"
  );
  $stmt->bind_param('i', $officinaId);
  $stmt->execute();
  $notifiche = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  if ($res = $conn->query("SELECT COUNT(*) c FROM acquisto WHERE utente_id = {$userId}")) {
    $stats['orders'] = (int)$res->fetch_assoc()['c'];
  }

  if ($res = $conn->query("SELECT COUNT(*) c FROM notifica_utente WHERE utente_id = {$userId} AND letto = 0")) {
    $stats['user_notifications'] = (int)$res->fetch_assoc()['c'];
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container wide-container dashboard-shell">
    <aside class="sidebar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>

      <div class="sidebar-menu">
        <a class="active" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">Dashboard</a>
        <a href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>

        <?php if ($officina): ?>
          <a href="<?= htmlspecialchars(appUrl('ordini_officina.php')) ?>">Ordini ricevuti</a>
          <a href="<?= htmlspecialchars(appUrl('notifiche_officina.php')) ?>">
            Notifiche<?= $stats['notifications'] > 0 ? ' (' . $stats['notifications'] . ')' : '' ?>
          </a>

          <?php if ($shop): ?>
            <a href="<?= htmlspecialchars(appUrl('gestisci_shop.php')) ?>">Gestisci shop</a>
          <?php else: ?>
            <a href="<?= htmlspecialchars(appUrl('crea_shop.php')) ?>">Crea shop</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="<?= htmlspecialchars(appUrl('ordini.php')) ?>">I miei ordini</a>
          <a href="<?= htmlspecialchars(appUrl('notifiche_utente.php')) ?>">
            Notifiche<?= $stats['user_notifications'] > 0 ? ' (' . $stats['user_notifications'] . ')' : '' ?>
          </a>
        <?php endif; ?>

        <a href="<?= htmlspecialchars(appUrl('logout.php')) ?>">Logout</a>
      </div>
    </aside>

    <main class="content-stack">
      <section class="header-card card">
        <div>
          <div class="kicker">Dashboard</div>
          <div class="headline">
            Ciao, <?= htmlspecialchars(($me['nome'] ?? 'Utente') . ' ' . ($me['cognome'] ?? '')) ?>
          </div>
          <div class="small">Email: <?= htmlspecialchars($me['email'] ?? '') ?> · ID utente <?= $userId ?></div>
        </div>
      </section>

      <section class="metric-grid">
        <?php if ($officina): ?>
          <div class="metric-card">
            <div class="small">Ordini ricevuti</div>
            <div class="stat-number"><?= $stats['orders'] ?></div>
          </div>

          <div class="metric-card">
            <div class="small">Prodotti totali</div>
            <div class="stat-number"><?= $stats['products'] ?></div>
          </div>

          <div class="metric-card">
            <div class="small">Disponibili</div>
            <div class="stat-number"><?= $stats['available'] ?></div>
          </div>

          <div class="metric-card">
            <div class="small">Venduti</div>
            <div class="stat-number"><?= $stats['sold'] ?></div>
          </div>
        <?php else: ?>
          <div class="metric-card">
            <div class="small">I miei ordini</div>
            <div class="stat-number"><?= $stats['orders'] ?></div>
          </div>

          <div class="metric-card">
            <div class="small">Notifiche non lette</div>
            <div class="stat-number"><?= $stats['user_notifications'] ?></div>
          </div>

          <a class="metric-card metric-card-link metric-card-primary" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">
            <div class="small">Marketplace</div>
            <div class="stat-number">→</div>
          </a>
        <?php endif; ?>
      </section>

      <section class="card">
        <div class="section-head">
          <div>
            <div class="section-title">Accesso API JWT</div>
            <p>Token Bearer per chiamare gli endpoint API protetti del tuo account.</p>
          </div>
        </div>

        <div class="btn-row mt-16">
          <button class="btn btn-primary" type="button" id="copyJwtBtn" data-token="<?= htmlspecialchars($jwt) ?>">
            Copia JWT
          </button>
          <span class="small" id="copyJwtStatus" aria-live="polite"></span>
        </div>

        <div class="small mt-12">
          Scade alle <?= htmlspecialchars(date('H:i:s', $jwtExp)) ?>.
          Header: <code>Authorization: Bearer &lt;JWT&gt;</code>
        </div>
      </section>

      <section class="card">
        <div class="section-head">
          <div>
            <div class="section-title">Il tuo spazio</div>
            <p>Ruoli, accesso e tenant Ricomoto.</p>
          </div>
        </div>

        <?php if ($officina): ?>
          <?php if ($shop): ?>
            <div class="topbar-flex">
              <div>
                <div class="headline-sm"><?= htmlspecialchars($shop['nome']) ?></div>
                <div class="small">Slug: <?= htmlspecialchars($shop['slug']) ?> · <?= htmlspecialchars($shop['localita']) ?></div>
                <div class="small">
                  Storefront:
                  <a class="link" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>">
                    <?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>
                  </a>
                </div>
              </div>

              <div class="btn-row">
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('gestisci_shop.php')) ?>">Pannello shop</a>
                <a class="btn btn-ghost" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>">Apri storefront</a>
              </div>
            </div>
          <?php else: ?>
            <div class="feature-card">
              <div class="headline-sm">Nessuno shop creato</div>
              <p>Crea il tuo tenant per aggiungere logo, località, descrizione e prodotti gestiti dall'officina.</p>
              <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('crea_shop.php')) ?>">Crea shop</a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="feature-card">
            <div class="headline-sm">Account cliente</div>
            <p>Puoi navigare il catalogo globale, acquistare prodotti dagli shop attivi, vedere i tuoi ordini e ricevere notifiche.</p>
          </div>
        <?php endif; ?>

        <div class="badge-row mt-16">
          <?php foreach ($ruoli as $r): ?>
            <span class="badge"><?= htmlspecialchars($r) ?></span>
          <?php endforeach; ?>

          <?php foreach ($permessi as $p): ?>
            <span class="badge"><?= htmlspecialchars($p) ?></span>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($officina): ?>
        <section class="card">
          <div class="section-title">Attività recenti</div>

          <?php if (!$notifiche): ?>
            <p class="mt-12">Nessuna richiesta, ordine o messaggio recente.</p>
          <?php else: ?>
            <div class="stack mt-12">
              <?php foreach ($notifiche as $n): ?>
                <div class="list-item">
                  <div class="headline-sm"><?= htmlspecialchars($n['buyer_nome'] . ' ' . $n['buyer_cognome']) ?></div>
                  <div class="small"><?= htmlspecialchars($n['buyer_email']) ?> · <?= htmlspecialchars($n['created_at']) ?></div>
                  <div class="small mt-12">Prodotto: <b><?= htmlspecialchars($n['prodotto_titolo']) ?></b></div>
                  <div class="small"><?= nl2br(htmlspecialchars($n['notifica_messaggio'])) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </main>
  </div>
</div>
<script>
document.getElementById('copyJwtBtn')?.addEventListener('click', async function () {
  const status = document.getElementById('copyJwtStatus');
  try {
    await navigator.clipboard.writeText(this.dataset.token || '');
    if (status) status.textContent = 'Copiato.';
  } catch (error) {
    if (status) status.textContent = 'Copia non riuscita.';
  }
});
</script>
</body>
</html>
