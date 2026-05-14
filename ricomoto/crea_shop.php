<?php
require_once __DIR__ . '/auth.php';
requireLogin();
if (!hasRole('officina')) {
    http_response_code(403);
    die('Solo officina.');
}
if (currentShop()) {
    header('Location: ' . appUrl('gestisci_shop.php'));
    exit;
}
$officina = currentOfficina();
if (!$officina) {
    die('Officina non trovata.');
}
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $localita = trim($_POST['localita'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        if ($nome === '' || $localita === '') {
            throw new Exception('Nome shop e località sono obbligatori.');
        }
        $slug = findUniqueShopSlug($_POST['slug'] ?? $nome);
        $logoRel = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['logo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                throw new Exception('Logo non valido.');
            }
            $file = 'uploads/loghi/shop_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($tmp, __DIR__ . '/' . $file)) {
                throw new Exception('Upload logo fallito.');
            }
            $logoRel = $file;
        }
        $stmt = $conn->prepare('INSERT INTO shop(officina_id, nome, slug, localita, logo, descrizione, status, featured) VALUES (?,?,?,?,?,?,\'active\',0)');
        $officinaId = (int)$officina['id'];
        $stmt->bind_param('isssss', $officinaId, $nome, $slug, $localita, $logoRel, $descrizione);
        $stmt->execute();
        $stmt->close();
        header('Location: ' . appUrl('gestisci_shop.php') . '?created=1');
        exit;
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Crea Shop - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-shell">
  <div class="container narrow-container">
    <div class="brandbar">
      <a class="brand compact" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>"><img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto"></a>
      <div class="btn-row"><a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">Torna alla dashboard</a></div>
    </div>
    <section class="card">
      <div class="kicker">Nuovo shop</div>
      <h2>Crea il tuo tenant</h2>
      <p>Imposta branding, slug, località e descrizione del negozio che rappresenterà la tua officina.</p>
      <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="grid2">
          <div class="field"><label>Nome shop</label><input name="nome" type="text" required></div>
          <div class="field"><label>Località</label><input name="localita" type="text" required></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Slug tenant</label><input name="slug" type="text" placeholder="es. moto-bergamo"></div>
          <div class="field"><label>Logo shop</label><input name="logo" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
        </div>
        <div class="field"><label>Descrizione</label><textarea name="descrizione" rows="5" placeholder="Racconta cosa vende il tuo shop"></textarea></div>
        <div class="btn-row mt-16">
          <button class="btn btn-primary" type="submit">Crea shop</button>
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">Annulla</a>
        </div>
      </form>
    </section>
  </div>
</div>
</body>
</html>
