<?php
require_once __DIR__ . '/auth.php';

requireLogin();

if (!hasRole('officina')) {
  http_response_code(403);
  die('Solo officina.');
}

$officina = currentOfficina();

if (!$officina) {
  http_response_code(403);
  die('Officina non trovata.');
}

$officinaId = (int)$officina['id'];

$stmt = $conn->prepare(
  'SELECT n.*, p.titolo, u.nome, u.cognome, u.email
   FROM notifica n
   JOIN prodotto p ON p.id = n.prodotto_id
   JOIN utente u ON u.ID = n.utente_id
   WHERE n.officina_id = ?
   ORDER BY n.created_at DESC'
);
$stmt->bind_param('i', $officinaId);
$stmt->execute();
$notifiche = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare('UPDATE notifica SET letto = 1 WHERE officina_id = ?');
$stmt->bind_param('i', $officinaId);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notifiche officina - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container wide-container">
    <div class="brandbar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">
        <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
      </a>

      <div class="btn-row">
        <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('gestisci_shop.php')) ?>">Gestisci shop</a>
        <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">Dashboard</a>
      </div>
    </div>

    <section class="card">
      <div class="kicker">Officina</div>
      <h1>Notifiche ricevute</h1>
      <p>Qui trovi richieste, offerte e aggiornamenti collegati ai tuoi prodotti.</p>

      <?php if (!$notifiche): ?>
        <p>Non hai notifiche.</p>
      <?php else: ?>
        <div class="stack mt-20">
          <?php foreach ($notifiche as $n): ?>
            <div class="card">
              <div class="badge-row">
                <span class="badge"><?= (int)$n['letto'] === 1 ? 'Letta' : 'Nuova' ?></span>
                <span class="badge"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($n['created_at']))) ?></span>
              </div>

              <p class="mt-16"><?= nl2br(htmlspecialchars($n['messaggio'])) ?></p>

              <div class="small">
                Prodotto: <b><?= htmlspecialchars($n['titolo']) ?></b><br>
                Cliente: <?= htmlspecialchars($n['nome'] . ' ' . $n['cognome']) ?> · <?= htmlspecialchars($n['email']) ?>
              </div>

              <div class="btn-row mt-16">
                <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$n['prodotto_id'])) ?>">
                  Vai al prodotto
                </a>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('gestisci_shop.php')) ?>">
                  Vedi offerte
                </a>
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
