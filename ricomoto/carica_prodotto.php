<?php
require_once __DIR__ . "/auth.php";
requirePermission('prodotto.crea');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

// trovo officina collegata (serve per sapere a chi appartiene il prodotto)
$officinaId = null;
$stmt = $conn->prepare("SELECT id FROM officina WHERE utente_id = ?");
$stmt->bindParam(1, $userId);
$stmt->execute();
if ($row = $stmt->fetch()) $officinaId = (int)$row['id'];

if (!$officinaId && !hasPermission('utenti.gestisci')) {
  die("Solo un account officina (o admin) può caricare prodotti.");
}

$err = null;
$ok = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    $titolo = trim($_POST["titolo"] ?? "");
    $descrizione = trim($_POST["descrizione"] ?? "");

    if ($titolo === "" || $descrizione === "") {
      throw new Exception("Compila titolo e descrizione.");
    }

    if (!isset($_FILES["immagine"]) || $_FILES["immagine"]["error"] !== UPLOAD_ERR_OK) {
      throw new Exception("Carica un'immagine valida.");
    }

    $tmp = $_FILES["immagine"]["tmp_name"];
    $name = $_FILES["immagine"]["name"];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ["jpg","jpeg","png","webp"];
    if (!in_array($ext, $allowed, true)) {
      throw new Exception("Formato non valido. Usa JPG/PNG/WEBP.");
    }

    // nome file unico
    $filename = "p_" . bin2hex(random_bytes(8)) . "." . $ext;
    $destRel = "uploads/" . $filename;
    $destAbs = __DIR__ . "/" . $destRel;

    if (!move_uploaded_file($tmp, $destAbs)) {
      throw new Exception("Upload fallito.");
    }

    // inserisco prodotto
    $stmt = $conn->prepare("INSERT INTO prodotto(officina_id, titolo, descrizione, immagine, stato) VALUES (?,?,?,?, 'disponibile')");
    $stmt->bindParam(1, $officinaId);
    $stmt->bindParam(2, $titolo);
    $stmt->bindParam(3, $descrizione);
    $stmt->bindParam(4, $destRel);
    $stmt->execute();

    $ok = "Prodotto caricato!";
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Carica prodotto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h1>Carica prodotto</h1>
  <p>Solo officina/admin</p>

  <div class="card">
    <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="field">
        <label>Titolo</label>
        <input type="text" name="titolo" required>
      </div>

      <div class="field">
        <label>Descrizione</label>
        <textarea name="descrizione" rows="5" required></textarea>
      </div>

      <div class="field">
        <label>Immagine</label>
        <input type="file" name="immagine" accept=".jpg,.jpeg,.png,.webp" required>
      </div>

      <div class="btn-row" style="margin-top:14px;">
        <button class="btn btn-white" type="submit">Carica</button>
        <a class="btn btn-blue" href="prodotti.php">Vai ai prodotti</a>
      </div>

      <div class="small">
        Torna alla <a class="link" href="dashboard.php">dashboard</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
