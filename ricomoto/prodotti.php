<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/router.php";
requirePermission('prodotto.leggi');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

$msg = $_GET["msg"] ?? null;

// Determina il tipo di pagina e filtra di conseguenza
$pageType = getCurrentPageType();
$tenancyId = getCurrentTenancyId();

if ($pageType === "shop" && $tenancyId) {
  // Shop specifico: mostra solo prodotti di quel tenancy
  $sql = "SELECT p.*, o.nome AS nome_officina, t.nome_shop AS tenancy_nome
          FROM prodotto p
          JOIN officina o ON o.id = p.officina_id
          LEFT JOIN tenancy t ON t.id = p.tenancy_id
          WHERE p.tenancy_id = :tenancy_id
          ORDER BY p.created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(":tenancy_id", $tenancyId, PDO::PARAM_INT);
  $stmt->execute();
  $prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  // Dashboard globale o admin: mostra tutti i prodotti
  $sql = "SELECT p.*, o.nome AS nome_officina, t.nome_shop AS tenancy_nome, t.subdomain AS tenancy_subdomain
          FROM prodotto p
          JOIN officina o ON o.id = p.officina_id
          LEFT JOIN tenancy t ON t.id = p.tenancy_id
          ORDER BY p.created_at DESC";
  $res = $conn->query($sql);
  $prodotti = $res->fetchAll(PDO::FETCH_ASSOC);
}

$canBuy = hasPermission('acquisto.crea');
$canUpload = hasPermission('prodotto.crea');

// Info tenancy corrente (se in shop)
$currentTenancy = getCurrentTenancy();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Prodotti - <?= $currentTenancy ? htmlspecialchars($currentTenancy["nome_shop"]) : "Ricomoto" ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <?php if ($currentTenancy): ?>
    <h1><?= htmlspecialchars($currentTenancy["nome_shop"]) ?></h1>
    <p>Shop ufficiale - Catalogo prodotti</p>
  <?php else: ?>
    <h1>Prodotti</h1>
    <p>Catalogo globale.</p>
  <?php endif; ?>

  <div class="btn-row" style="margin-bottom:14px;">
    <a class="btn btn-blue" href="dashboard.php">Dashboard</a>
    <?php if ($canUpload): ?>
      <a class="btn btn-white" href="carica_prodotto.php">Carica prodotto</a>
    <?php endif; ?>
    <?php if (isAdminTenancy()): ?>
      <a class="btn btn-white" href="admin_tenancy.php">Admin Tenancy</a>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="grid-products">
    <?php foreach ($prodotti as $p): ?>
      <div class="product-card">
        <div class="product-img">
          <img src="<?= htmlspecialchars($p["immagine"]) ?>" alt="img">
        </div>
        <div class="product-body">
          <div class="product-title"><?= htmlspecialchars($p["titolo"]) ?></div>
          <div class="product-sub">
            Officina: <?= htmlspecialchars($p["nome_officina"]) ?>
            <?php if (!$currentTenancy && !empty($p["tenancy_nome"])): ?>
              <span style="color: #667eea;"> | Shop: <?= htmlspecialchars($p["tenancy_nome"]) ?></span>
            <?php endif; ?>
          </div>
          <div class="product-desc"><?= nl2br(htmlspecialchars($p["descrizione"])) ?></div>

          <div class="product-status <?= $p["stato"] === "venduto" ? "sold" : "avail" ?>">
            Stato: <?= htmlspecialchars($p["stato"]) ?>
          </div>

          <?php if ($canBuy && $p["stato"] === "disponibile"): ?>
            <form method="POST" action="acquista.php" style="margin-top:10px;">
              <input type="hidden" name="prodotto_id" value="<?= (int)$p["id"] ?>">
              <button class="btn btn-blue" type="submit">Acquista</button>
            </form>
          <?php endif; ?>

          <?php if ($canBuy && $p["stato"] === "disponibile"): ?>
            <form method="POST" action="notifica.php" style="margin-top:10px; display:flex; flex-direction:column; gap:8px;">
              <input type="hidden" name="prodotto_id" value="<?= (int)$p["id"] ?>">
              <textarea name="messaggio" rows="2" placeholder="Scrivi la tua richiesta per l'officina..." style="width:100%; border-radius:8px; padding:8px; border:1px solid #ccc;" required></textarea>
              <button class="btn btn-blue" type="submit">Chiedi info</button>
            </form>
          <?php endif; ?>
          
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
