<?php
require_once __DIR__ . '/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
  'SELECT n.*, p.titolo
   FROM notifica_utente n
   LEFT JOIN prodotto p ON p.id = n.prodotto_id
   WHERE n.utente_id = ?
   ORDER BY n.created_at DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifiche = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare('UPDATE notifica_utente SET letto = 1 WHERE utente_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notifiche - Ricomoto</title>
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
        <a class="btn btn-primary" href="<?= htmlspecialchars(dashboardTarget()) ?>">Dashboard</a>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Notifiche</div>
      <h1>Le tue notifiche</h1>

      <?php if (!$notifiche): ?>
        <p>Non hai notifiche.</p>
      <?php else: ?>
        <div class="stack mt-20">
          <?php foreach ($notifiche as $n): ?>
            <div class="card">
              <p><?= htmlspecialchars($n['messaggio']) ?></p>

              <?php if (!empty($n['prodotto_id'])): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$n['prodotto_id'])) ?>">
                  Vai al prodotto
                </a>
              <?php endif; ?>

              <div class="small mt-12">
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($n['created_at']))) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
</body>
</html>