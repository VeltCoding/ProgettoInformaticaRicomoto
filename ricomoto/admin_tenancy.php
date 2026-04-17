<?php
/**
 * Dashboard Admin Tenancy
 * Gestione centralizzata di tutte le tenancy/shop
 * Accesso: solo utente con ruolo admin_tenancy
 */

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/router.php";

// Verifica accesso
requirePermission("tenancy.gestisci");

$err = null;
$success = null;

// Gestione azioni POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  
  try {
    if ($action === "crea_tenancy") {
      $nomeShop = trim($_POST["nome_shop"] ?? "");
      $subdomain = trim($_POST["subdomain"] ?? "");
      
      if (!$nomeShop || !$subdomain) {
        throw new Exception("Compila tutti i campi");
      }
      
      // Pulizia subdomain (solo lettere, numeri, trattini)
      $subdomain = preg_replace("/[^a-z0-9-]/i", "", $subdomain);
      
      // Verifica subdomain unico
      $stmt = $conn->prepare("SELECT id FROM tenancy WHERE subdomain = ?");
      $stmt->bindParam(1, $subdomain);
      $stmt->execute();
      if ($stmt->fetch()) {
        throw new Exception("Subdomain già in uso");
      }
      
      $stmt = $conn->prepare("INSERT INTO tenancy (nome_shop, subdomain, stato) VALUES (?, ?, 'attivo')");
      $stmt->bindParam(1, $nomeShop);
      $stmt->bindParam(2, $subdomain);
      $stmt->execute();
      
      $success = "Shop creato con successo!";
    }
    
    if ($action === "modifica_stato") {
      $tenancyId = (int)$_POST["tenancy_id"];
      $nuovoStato = $_POST["stato"];
      
      if (!in_array($nuovoStato, ["attivo", "sospeso", "disattivato"])) {
        throw new Exception("Stato non valido");
      }
      
      $stmt = $conn->prepare("UPDATE tenancy SET stato = ? WHERE id = ?");
      $stmt->bindParam(1, $nuovoStato);
      $stmt->bindParam(2, $tenancyId);
      $stmt->execute();
      
      $success = "Stato aggiornato!";
    }
    
    if ($action === "collega_officina") {
      $tenancyId = (int)$_POST["tenancy_id"];
      $officinaId = (int)$_POST["officina_id"];
      
      $stmt = $conn->prepare("UPDATE officina SET tenancy_id = ? WHERE id = ?");
      $stmt->bindParam(1, $tenancyId);
      $stmt->bindParam(2, $officinaId);
      $stmt->execute();
      
      $success = "Officina collegata allo shop!";
    }
    
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

// Carica tutte le tenancy
$stmt = $conn->query("
  SELECT t.*, 
         (SELECT COUNT(*) FROM officina WHERE tenancy_id = t.id) AS num_officine,
         (SELECT COUNT(*) FROM prodotto WHERE tenancy_id = t.id) AS num_prodotti
  FROM tenancy t
  ORDER BY t.created_at DESC
");
$tenancies = $stmt->fetchAll();

// Carica officine non collegate
$stmt = $conn->query("SELECT id, nome, utente_id FROM officina WHERE tenancy_id IS NULL");
$officineNonCollegate = $stmt->fetchAll();

// Carica tutte le officine (per statistiche)
$stmt = $conn->query("
  SELECT o.id, o.nome, t.nome_shop AS tenancy_nome, t.subdomain,
         (SELECT COUNT(*) FROM prodotto WHERE officina_id = o.id) AS num_prodotti
  FROM officina o
  LEFT JOIN tenancy t ON t.id = o.tenancy_id
  ORDER BY o.nome
");
$officine = $stmt->fetchAll();

// Statistiche globali
$stmt = $conn->query("
  SELECT 
    (SELECT COUNT(*) FROM tenancy) AS tot_tenancy,
    (SELECT COUNT(*) FROM officina) AS tot_officine,
    (SELECT COUNT(*) FROM prodotto) AS tot_prodotti,
    (SELECT COUNT(*) FROM utente) AS tot_utenti
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Tenancy - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; color: white; }
    .stat-number { font-size: 32px; font-weight: bold; }
    .stat-label { opacity: 0.9; font-size: 14px; }
    .status-attivo { color: #22c55e; font-weight: bold; }
    .status-sospeso { color: #f59e0b; font-weight: bold; }
    .status-disattivato { color: #ef4444; font-weight: bold; }
    .shop-link { color: #3b82f6; text-decoration: none; }
    .shop-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="container">
  <h1>Admin Tenancy</h1>
  <p>Gestione centralizzata di tutti gli shop</p>
  
  <?php if ($err): ?>
    <div class="alert"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  
  <?php if ($success): ?>
    <div class="alert" style="background: #22c55e; color: white;"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Statistiche globali -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
    <div class="stat-card">
      <div class="stat-number"><?= (int)$stats["tot_tenancy"] ?></div>
      <div class="stat-label">Shop Attivi</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= (int)$stats["tot_officine"] ?></div>
      <div class="stat-label">Officine</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= (int)$stats["tot_prodotti"] ?></div>
      <div class="stat-label">Prodotti</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= (int)$stats["tot_utenti"] ?></div>
      <div class="stat-label">Utenti</div>
    </div>
  </div>

  <!-- Crea nuovo shop -->
  <div class="card" style="margin-bottom: 24px;">
    <div style="font-weight: 800; margin-bottom: 12px;">Crea Nuovo Shop</div>
    <form method="POST">
      <input type="hidden" name="action" value="crea_tenancy">
      <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
        <div class="field" style="margin: 0;">
          <label>Nome Shop</label>
          <input type="text" name="nome_shop" placeholder="Moto Service Roma" required>
        </div>
        <div class="field" style="margin: 0;">
          <label>Subdomain</label>
          <input type="text" name="subdomain" placeholder="moto-service" required>
        </div>
        <button type="submit" class="btn btn-blue">Crea Shop</button>
      </div>
      <div class="small" style="margin-top: 8px; opacity: 0.7;">
        Lo shop sarà accessibile all'indirizzo: <code>http://[subdomain].localhost/tenancy/[subdomain]</code>
      </div>
    </form>
  </div>

  <!-- Lista Shop -->
  <div class="card" style="margin-bottom: 24px;">
    <div style="font-weight: 800; margin-bottom: 12px;">Tutti gli Shop</div>
    
    <?php if (!$tenancies): ?>
      <div class="small">Nessuno shop creato.</div>
    <?php else: ?>
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="border-bottom: 1px solid #eee; text-align: left;">
            <th style="padding: 8px;">Nome Shop</th>
            <th style="padding: 8px;">Subdomain</th>
            <th style="padding: 8px;">Officine</th>
            <th style="padding: 8px;">Prodotti</th>
            <th style="padding: 8px;">Stato</th>
            <th style="padding: 8px;">Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tenancies as $t): ?>
            <tr style="border-bottom: 1px solid #f5f5f5;">
              <td style="padding: 8px;"><?= htmlspecialchars($t["nome_shop"]) ?></td>
              <td style="padding: 8px;">
                <a class="shop-link" href="/tenancy/<?= htmlspecialchars($t["subdomain"]) ?>" target="_blank">
                  <?= htmlspecialchars($t["subdomain"]) ?>
                </a>
              </td>
              <td style="padding: 8px;"><?= (int)$t["num_officine"] ?></td>
              <td style="padding: 8px;"><?= (int)$t["num_prodotti"] ?></td>
              <td style="padding: 8px;">
                <span class="status-<?= $t["stato"] ?>"><?= htmlspecialchars($t["stato"]) ?></span>
              </td>
              <td style="padding: 8px;">
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="action" value="modifica_stato">
                  <input type="hidden" name="tenancy_id" value="<?= (int)$t["id"] ?>">
                  <select name="stato" onchange="this.form.submit()" style="padding: 4px;">
                    <option value="attivo" <?= $t["stato"] === "attivo" ? "selected" : "" ?>>Attivo</option>
                    <option value="sospeso" <?= $t["stato"] === "sospeso" ? "selected" : "" ?>>Sospeso</option>
                    <option value="disattivato" <?= $t["stato"] === "disattivato" ? "selected" : "" ?>>Disattivato</option>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Collega Officina a Shop -->
  <?php if ($officineNonCollegate): ?>
    <div class="card" style="margin-bottom: 24px;">
      <div style="font-weight: 800; margin-bottom: 12px;">Collega Officina a Shop</div>
      <form method="POST">
        <input type="hidden" name="action" value="collega_officina">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
          <div class="field" style="margin: 0;">
            <label>Officina</label>
            <select name="officina_id" required>
              <option value="">Seleziona...</option>
              <?php foreach ($officineNonCollegate as $o): ?>
                <option value="<?= (int)$o["id"] ?>"><?= htmlspecialchars($o["nome"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field" style="margin: 0;">
            <label>Shop</label>
            <select name="tenancy_id" required>
              <option value="">Seleziona...</option>
              <?php foreach ($tenancies as $t): ?>
                <option value="<?= (int)$t["id"] ?>"><?= htmlspecialchars($t["nome_shop"]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-blue">Collega</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Tutte le Officine -->
  <div class="card">
    <div style="font-weight: 800; margin-bottom: 12px;">Officine</div>
    <table style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr style="border-bottom: 1px solid #eee; text-align: left;">
          <th style="padding: 8px;">Nome</th>
          <th style="padding: 8px;">Shop Associato</th>
          <th style="padding: 8px;">Prodotti</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($officine as $o): ?>
          <tr style="border-bottom: 1px solid #f5f5f5;">
            <td style="padding: 8px;"><?= htmlspecialchars($o["nome"]) ?></td>
            <td style="padding: 8px;">
              <?php if ($o["tenancy_nome"]): ?>
                <a class="shop-link" href="/tenancy/<?= htmlspecialchars($o["subdomain"]) ?>">
                  <?= htmlspecialchars($o["tenancy_nome"]) ?>
                </a>
              <?php else: ?>
                <span style="color: #999;">Non collegata</span>
              <?php endif; ?>
            </td>
            <td style="padding: 8px;"><?= (int)$o["num_prodotti"] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="margin-top: 24px;">
    <a class="btn btn-white" href="dashboard.php">← Torna alla Dashboard</a>
  </div>
</div>
</body>
</html>