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

$msg = $_GET['msg'] ?? null;
$officinaId = (int)$officina['id'];

$stmt = $conn->prepare(
  'SELECT a.*, p.titolo, p.immagine, u.nome, u.cognome, u.email
   FROM acquisto a
   JOIN prodotto p ON p.id = a.prodotto_id
   JOIN utente u ON u.ID = a.utente_id
   WHERE a.officina_id = ?
   ORDER BY a.created_at DESC'
);
$stmt->bind_param('i', $officinaId);
$stmt->execute();
$ordini = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ordini officina - Ricomoto</title>
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

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="kicker">Vendite</div>
      <h1>Ordini ricevuti</h1>
      <p>Qui puoi gestire gli ordini, annullarli, aggiungere il codice spedizione e chattare con il cliente.</p>
    </section>

    <section class="section">
      <?php if (!$ordini): ?>
        <div class="card">
          <p>Non hai ancora ricevuto ordini.</p>
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
                    Cliente: <?= htmlspecialchars($ordine['nome'] . ' ' . $ordine['cognome']) ?><br>
                    Email: <?= htmlspecialchars($ordine['email']) ?>
                  </p>

                  <div class="mt-16" style="padding-top:14px;border-top:1px solid rgba(255,255,255,.08);">
                    <strong>Dati spedizione</strong>
                    <p class="mt-12">
                      Destinatario: <?= htmlspecialchars($ordine['nome_spedizione'] ?: ($ordine['nome'] . ' ' . $ordine['cognome'])) ?><br>
                      Telefono: <?= htmlspecialchars($ordine['telefono_spedizione'] ?: '-') ?><br>
                      Indirizzo: <?= htmlspecialchars($ordine['indirizzo_spedizione'] ?: '-') ?><br>
                      <?= htmlspecialchars(trim(($ordine['cap_spedizione'] ?? '') . ' ' . ($ordine['citta_spedizione'] ?? '') . ' ' . ($ordine['provincia_spedizione'] ?? '')) ?: '-') ?>
                    </p>

                    <?php if (!empty($ordine['note_spedizione'])): ?>
                      <div class="small">
                        Note consegna: <?= nl2br(htmlspecialchars($ordine['note_spedizione'])) ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="price-card">
                    € <?= number_format((float)$ordine['prezzo_pagato'], 2, ',', '.') ?>
                  </div>

                  <div class="badge-row">
                    <span class="badge">Pagamento: <?= htmlspecialchars($ordine['stato_pagamento']) ?></span>
                    <span class="badge">Ordine: <?= htmlspecialchars($ordine['stato_ordine']) ?></span>
                  </div>

                  <?php if (!empty($ordine['codice_spedizione'])): ?>
                    <div class="ok mt-16">
                      Tracking: <?= htmlspecialchars($ordine['corriere'] ?: 'Corriere') ?> —
                      <strong><?= htmlspecialchars($ordine['codice_spedizione']) ?></strong>
                    </div>
                  <?php endif; ?>

                  <form method="POST" action="<?= htmlspecialchars(appUrl('gestisci_ordine.php')) ?>" class="mt-20">
                    <input type="hidden" name="ordine_id" value="<?= (int)$ordine['id'] ?>">
                    <input type="hidden" name="azione" value="spedisci">

                    <div class="grid2">
                      <div class="field">
                        <label>Corriere</label>
                        <input type="text" name="corriere" value="<?= htmlspecialchars($ordine['corriere'] ?? '') ?>" placeholder="BRT, Poste, DHL...">
                      </div>

                      <div class="field">
                        <label>Codice spedizione</label>
                        <input type="text" name="codice_spedizione" value="<?= htmlspecialchars($ordine['codice_spedizione'] ?? '') ?>" placeholder="Tracking">
                      </div>
                    </div>

                    <div class="field">
                      <label>Note ordine</label>
                      <textarea name="note_ordine" rows="3"><?= htmlspecialchars($ordine['note_ordine'] ?? '') ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Salva spedizione</button>
                  </form>

                  <div class="btn-row mt-16">
                    <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('chat_ordine.php?id=' . (int)$ordine['id'])) ?>">
                      Chat con cliente
                    </a>

                    <?php if ($ordine['stato_ordine'] !== 'annullato'): ?>
                      <form method="POST" action="<?= htmlspecialchars(appUrl('gestisci_ordine.php')) ?>" onsubmit="return confirm('Vuoi annullare questo ordine?');">
                        <input type="hidden" name="ordine_id" value="<?= (int)$ordine['id'] ?>">
                        <input type="hidden" name="azione" value="annulla">
                        <button class="btn btn-danger" type="submit">Annulla ordine</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($ordine['stato_ordine'] === 'spedito'): ?>
                      <form method="POST" action="<?= htmlspecialchars(appUrl('gestisci_ordine.php')) ?>">
                        <input type="hidden" name="ordine_id" value="<?= (int)$ordine['id'] ?>">
                        <input type="hidden" name="azione" value="completa">
                        <button class="btn btn-primary" type="submit">Segna completato</button>
                      </form>
                    <?php endif; ?>
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
