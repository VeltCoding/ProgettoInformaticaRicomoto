<?php
require_once __DIR__ . "/auth.php";
requirePermission('prodotto.leggi');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

$msg = $_GET["msg"] ?? null;

$sql = "SELECT p.*, o.nome AS nome_officina
        FROM prodotto p
        JOIN officina o ON o.id = p.officina_id
        ORDER BY p.created_at DESC";
$res = $conn->query($sql);
$prodotti = $res->fetch_all(MYSQLI_ASSOC);

$canBuy = hasPermission('acquisto.crea');
$canUpload = hasPermission('prodotto.crea');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Prodotti</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h1>Prodotti</h1>
  <p>Catalogo</p>

  <div class="btn-row" style="margin-bottom:14px;">
    <a class="btn btn-blue" href="dashboard.php">Dashboard</a>
    <?php if ($canUpload): ?>
      <a class="btn btn-white" href="carica_prodotto.php">Carica prodotto</a>
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
          <div class="product-sub">Officina: <?= htmlspecialchars($p["nome_officina"]) ?></div>
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
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
