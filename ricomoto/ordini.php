<?php
require_once __DIR__ . '/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$msg = $_GET['msg'] ?? null;

$stmt = $conn->prepare(
  'SELECT a.*, p.titolo, p.immagine, s.nome AS nome_shop, s.slug AS shop_slug, o.nome AS nome_officina
   FROM acquisto a
   JOIN prodotto p ON p.id = a.prodotto_id
   JOIN officina o ON o.id = a.officina_id
   LEFT JOIN shop s ON s.id = p.shop_id
   WHERE a.utente_id = ?
   ORDER BY a.created_at DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$ordini = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>I miei ordini - Ricomoto</title>
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

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="kicker">Ordini</div>
      <h1>I miei ordini</h1>
      <p>Qui puoi vedere gli acquisti effettuati, lo stato dell’ordine, il codice spedizione e la chat con il venditore.</p>
    </section>

    <section class="section">
      <?php if (!$ordini): ?>
        <div class="card">
          <p>Non hai ancora effettuato ordini.</p>
          <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Vai al marketplace</a>
        </div>
      <?php else: ?>
        <div class="stack">
          <?php foreach ($ordini as $ordine): ?>
            <div class="card">
              <div class="order-row">
                <div>
                  <img class="order-thumb" src="<?= htmlspecialchars(mediaUrl($ordine['immagine'])) ?>" alt="prodotto">
                </div>

                <div>
                  <div class="kicker">Ordine #<?= (int)$ordine['id'] ?></div>
                  <h2><?= htmlspecialchars($ordine['titolo']) ?></h2>

                  <p>
                    Venditore: <?= htmlspecialchars($ordine['nome_officina']) ?><br>
                    Shop:
                    <?php if (!empty($ordine['shop_slug'])): ?>
                      <a class="link" href="<?= htmlspecialchars(tenantStoreUrl($ordine['shop_slug'])) ?>">
                        <?= htmlspecialchars($ordine['nome_shop']) ?>
                      </a>
                    <?php else: ?>
                      <?= htmlspecialchars($ordine['nome_shop'] ?? '-') ?>
                    <?php endif; ?>
                  </p>

                  <div class="price-card">
                    € <?= number_format((float)$ordine['prezzo_pagato'], 2, ',', '.') ?>
                  </div>

                  <div class="badge-row">
                    <span class="badge">Pagamento: <?= htmlspecialchars($ordine['stato_pagamento']) ?></span>
                    <span class="badge">Ordine: <?= htmlspecialchars($ordine['stato_ordine']) ?></span>
                  </div>

                  <?php if (!empty($ordine['codice_spedizione'])): ?>
                    <div class="ok mt-16">
                      Spedito con <?= htmlspecialchars($ordine['corriere'] ?: 'corriere') ?> —
                      Codice tracking: <strong><?= htmlspecialchars($ordine['codice_spedizione']) ?></strong>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($ordine['note_ordine'])): ?>
                    <div class="small mt-12">
                      Note venditore: <?= nl2br(htmlspecialchars($ordine['note_ordine'])) ?>
                    </div>
                  <?php endif; ?>

                  <div class="btn-row mt-16">
                    <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$ordine['prodotto_id'])) ?>">
                      Vedi prodotto
                    </a>

                    <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('chat_ordine.php?id=' . (int)$ordine['id'])) ?>">
                      Chat con venditore
                    </a>
                  </div>

                  <div class="small mt-12">
                    Creato il <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ordine['created_at']))) ?>
                  </div>
                </div>
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