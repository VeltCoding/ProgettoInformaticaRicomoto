<?php
require_once __DIR__ . "/auth.php";

if (!isLoggedIn()) { header("Location: login.php"); exit; }

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) { header("Location: login.php"); exit; }

// JWT in sessione (generato al login)
$jwt = $_SESSION["jwt"] ?? null;
$jwtExp = (int)($_SESSION["jwt_exp"] ?? 0);
$jwtRemaining = $jwtExp > 0 ? max(0, $jwtExp - time()) : 0;

// Dati utente base
$stmt = $conn->prepare("SELECT ID, nome, cognome, email FROM utente WHERE ID=?");
$stmt->bindParam(1, $userId);
$stmt->execute();
$me = $stmt->fetch();

// Ruoli
$ruoli = [];
$stmt = $conn->prepare("
  SELECT r.name
  FROM user_roles ur
  JOIN roles r ON r.id = ur.role_id
  WHERE ur.user_id = ?
");
$stmt->bindParam(1, $userId);
$stmt->execute();
while ($row = $stmt->fetch()) $ruoli[] = $row["name"];

// Permessi
$permessi = [];
$stmt = $conn->prepare("
  (SELECT DISTINCT p.code
   FROM permissions p
   JOIN role_permissions rp ON rp.permission_id = p.id
   JOIN user_roles ur ON ur.role_id = rp.role_id
   WHERE ur.user_id = ?)
  UNION
  (SELECT DISTINCT p.code
   FROM permissions p
   JOIN user_permissions up ON up.permission_id = p.id
   WHERE up.user_id = ?)
");
$stmt->bindParam(1, $userId);
$stmt->bindParam(2, $userId);
$stmt->execute();
while ($row = $stmt->fetch()) $permessi[] = $row["code"];
sort($permessi);

// Se OFFICINA: MONITORA (notifiche acquisto) - filtrate per tenancy
$officinaId = null;
$tenancyId = null;
$notifiche = [];
$stmt = $conn->prepare("SELECT id, tenancy_id FROM officina WHERE utente_id = ?");
$stmt->bindParam(1, $userId);
$stmt->execute();
$r = $stmt->fetch();
if ($r) {
  $officinaId = (int)$r["id"];
  $tenancyId = $r["tenancy_id"] ? (int)$r["tenancy_id"] : null;
}

if ($officinaId) {
  // Filtra per tenancy_id se presente
  if ($tenancyId) {
    $stmt = $conn->prepare("
      SELECT n.created_at,
             n.messaggio AS notifica_messaggio,
             u.nome AS buyer_nome, u.cognome AS buyer_cognome, u.email AS buyer_email,
             p.titolo AS prodotto_titolo
      FROM notifica n
      JOIN utente u ON u.ID = n.utente_id
      JOIN prodotto p ON p.id = n.prodotto_id
      WHERE n.officina_id = ? AND n.tenancy_id = ?
      ORDER BY n.created_at DESC
      LIMIT 50
    ");
    $stmt->bindParam(1, $officinaId);
    $stmt->bindParam(2, $tenancyId);
  } else {
    $stmt = $conn->prepare("
      SELECT n.created_at,
             n.messaggio AS notifica_messaggio,
             u.nome AS buyer_nome, u.cognome AS buyer_cognome, u.email AS buyer_email,
             p.titolo AS prodotto_titolo
      FROM notifica n
      JOIN utente u ON u.ID = n.utente_id
      JOIN prodotto p ON p.id = n.prodotto_id
      WHERE n.officina_id = ?
      ORDER BY n.created_at DESC
      LIMIT 50
    ");
    $stmt->bindParam(1, $officinaId);
  }
  $stmt->execute();
  $notifiche = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h1>Dashboard</h1>

  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
      <div>
        <div style="font-size:18px; font-weight:700;">
          Ciao, <?= htmlspecialchars(($me["nome"] ?? "Utente") . " " . ($me["cognome"] ?? "")) ?>
        </div>
        <div class="small">
          ID utente: <b><?= (int)$userId ?></b> — Email: <?= htmlspecialchars($me["email"] ?? "") ?>
        </div>
      </div>

      <div class="btn-row">
        <?php if (hasPermission("prodotto.crea")): ?>
          <?php if ($tenancyId): ?>
            <!-- Officina con shop → Gestisci Shop -->
            <a class="btn btn-blue" href="gestisci_shop.php">Gestisci Shop</a>
          <?php else: ?>
            <!-- Officina senza shop → Crea Shop -->
            <a class="btn btn-blue" href="crea_shop.php">Crea Shop</a>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (isAdminTenancy()): ?>
          <a class="btn btn-blue" href="admin_tenancy.php">Admin Tenancy</a>
        <?php endif; ?>

        <a class="btn btn-white" href="logout.php">Logout</a>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:800; margin-bottom:8px;">JWT (valido 10 minuti)</div>
    <?php if ($jwt): ?>
      <div class="small" style="opacity:.85; margin-bottom:8px;">
        Scade tra circa: <b><?= (int)ceil($jwtRemaining/60) ?></b> minuti
      </div>
      <textarea readonly style="width:100%; min-height:95px; border-radius:14px; padding:10px; background:rgba(255,255,255,0.03); color:#fff; border:1px solid rgba(255,255,255,0.18);"><?= htmlspecialchars($jwt) ?></textarea>
      <div class="small" style="margin-top:8px; opacity:.85;">
        Per usarlo: <code>Authorization: Bearer &lt;token&gt;</code>
      </div>
    <?php else: ?>
      <div class="alert">JWT non presente in sessione. Rifai login (serve che login.php generi il token).</div>
    <?php endif; ?>
  </div>

  <div class="card" style="margin-top:14px;">
    <div style="font-weight:700; margin-bottom:8px;">Ruoli</div>
    <div class="small"><?= htmlspecialchars($ruoli ? implode(", ", $ruoli) : "Nessun ruolo") ?></div>

    <div style="font-weight:700; margin:14px 0 8px;">Permessi</div>
    <?php if (!$permessi): ?>
      <div class="small">Nessun permesso trovato.</div>
    <?php else: ?>
      <ul style="margin:0; padding-left:18px;">
        <?php foreach ($permessi as $p): ?>
          <li class="small"><?= htmlspecialchars($p) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <?php if ($officinaId): ?>
    <div class="card" style="margin-top:14px;">
      <div style="font-weight:800; margin-bottom:8px;">MONITORA (solo officina)</div>
      <div class="small" style="margin-bottom:10px;">Chi acquista cosa</div>

      <?php if (!$notifiche): ?>
        <div class="small">Nessun acquisto ricevuto finora.</div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($notifiche as $n): ?>
            <div style="border:1px solid rgba(255,255,255,0.18); border-radius:14px; padding:10px 12px; background:rgba(255,255,255,0.03);">
              <div style="font-weight:700;">
                <?= htmlspecialchars($n["buyer_nome"] . " " . $n["buyer_cognome"]) ?>
                <span class="small" style="opacity:.8;">(<?= htmlspecialchars($n["buyer_email"]) ?>)</span>
              </div>
              <div class="small" style="margin-top:4px;">
                Prodotto: <b><?= htmlspecialchars($n["prodotto_titolo"]) ?></b>
              </div>
              <div class="small" style="margin-top:4px;">
                Messaggio: <?= nl2br(htmlspecialchars($n["notifica_messaggio"])) ?>
              </div>
              <div class="small" style="margin-top:4px; opacity:.8;">
                <?= htmlspecialchars($n["created_at"]) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
