<?php
require_once __DIR__ . '/auth.php';

requireLogin();

if (!hasRole('officina')) {
  http_response_code(403);
  die('Solo officina.');
}

$officina = currentOfficina();
$shop = currentShop();

if (!$officina || !$shop) {
  header('Location: ' . appUrl('crea_shop.php'));
  exit;
}

$err = null;
$msg = $_GET['msg'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'save_shop';

  try {
    if ($action === 'save_shop') {
      $nome = trim($_POST['nome'] ?? '');
      $localita = trim($_POST['localita'] ?? '');
      $descrizione = trim($_POST['descrizione'] ?? '');
      $slugInput = trim($_POST['slug'] ?? '');

      if ($nome === '' || $localita === '') {
        throw new Exception('Nome e località sono obbligatori.');
      }

      $slug = findUniqueShopSlug($slugInput !== '' ? $slugInput : $nome, (int)$shop['id']);
      $logoRel = $shop['logo'];

      if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
          throw new Exception('Logo non valido.');
        }

        $logoRel = 'uploads/loghi/shop_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (!move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/' . $logoRel)) {
          throw new Exception('Upload logo fallito.');
        }
      }

      $shopId = (int)$shop['id'];

      $stmt = $conn->prepare('UPDATE shop SET nome = ?, slug = ?, localita = ?, descrizione = ?, logo = ? WHERE id = ?');
      $stmt->bind_param('sssssi', $nome, $slug, $localita, $descrizione, $logoRel, $shopId);
      $stmt->execute();
      $stmt->close();

      header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Shop aggiornato'));
      exit;
    }

    if ($action === 'create_product') {
      $titolo = trim($_POST['titolo'] ?? '');
      $descrizione = trim($_POST['descrizione_prodotto'] ?? '');
      $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');
      $trattabile = isset($_POST['trattabile']) ? 1 : 0;
      $quantita = (int)($_POST['quantita'] ?? 1);

      if ($titolo === '' || $descrizione === '') {
        throw new Exception('Titolo e descrizione prodotto obbligatori.');
      }

      if ($prezzo <= 0) {
        throw new Exception('Inserisci un prezzo valido.');
      }

      if ($quantita < 1 || $quantita > 50) {
        throw new Exception('Seleziona una quantita valida.');
      }

      if (!isset($_FILES['immagine']) || $_FILES['immagine']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Carica un\'immagine prodotto.');
      }

      $ext = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception('Immagine non valida.');
      }

      $destRel = 'uploads/p_' . bin2hex(random_bytes(8)) . '.' . $ext;

      if (!move_uploaded_file($_FILES['immagine']['tmp_name'], __DIR__ . '/' . $destRel)) {
        throw new Exception('Upload prodotto fallito.');
      }

      $officinaId = (int)$officina['id'];
      $shopId = (int)$shop['id'];

      $stmt = $conn->prepare(
        "INSERT INTO prodotto(officina_id, shop_id, titolo, descrizione, prezzo, trattabile, quantita, immagine, stato)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'disponibile')"
      );
      $stmt->bind_param('iissdiis', $officinaId, $shopId, $titolo, $descrizione, $prezzo, $trattabile, $quantita, $destRel);
      $stmt->execute();
      $stmt->close();

      header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Prodotto creato'));
      exit;
    }

    if ($action === 'update_product') {
      $productId = (int)($_POST['product_id'] ?? 0);
      $titolo = trim($_POST['titolo'] ?? '');
      $descrizione = trim($_POST['descrizione'] ?? '');
      $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');
      $trattabile = isset($_POST['trattabile']) ? 1 : 0;
      $quantita = (int)($_POST['quantita'] ?? 0);

      if ($prezzo <= 0) {
        throw new Exception('Inserisci un prezzo valido.');
      }

      if ($quantita < 0 || $quantita > 50) {
        throw new Exception('Seleziona una quantita valida.');
      }

      $stato = $quantita > 0 ? 'disponibile' : 'venduto';

      $shopId = (int)$shop['id'];

      $stmt = $conn->prepare('SELECT id, immagine FROM prodotto WHERE id = ? AND shop_id = ?');
      $stmt->bind_param('ii', $productId, $shopId);
      $stmt->execute();
      $product = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$product) {
        throw new Exception('Prodotto non trovato.');
      }

      $image = $product['immagine'];

      if (isset($_FILES['immagine_' . $productId]) && $_FILES['immagine_' . $productId]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['immagine_' . $productId]['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
          throw new Exception('Immagine non valida.');
        }

        $image = 'uploads/p_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (!move_uploaded_file($_FILES['immagine_' . $productId]['tmp_name'], __DIR__ . '/' . $image)) {
          throw new Exception('Upload immagine fallito.');
        }
      }

      $stmt = $conn->prepare(
        'UPDATE prodotto
         SET titolo = ?, descrizione = ?, prezzo = ?, trattabile = ?, quantita = ?, stato = ?, immagine = ?
         WHERE id = ? AND shop_id = ?'
      );
      $stmt->bind_param('ssdiissii', $titolo, $descrizione, $prezzo, $trattabile, $quantita, $stato, $image, $productId, $shopId);
      $stmt->execute();
      $stmt->close();

      header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Prodotto aggiornato'));
      exit;
    }

    if ($action === 'delete_product') {
      $productId = (int)($_POST['product_id'] ?? 0);
      $shopId = (int)$shop['id'];

      $stmt = $conn->prepare('DELETE FROM prodotto WHERE id = ? AND shop_id = ?');
      $stmt->bind_param('ii', $productId, $shopId);
      $stmt->execute();
      $stmt->close();

      header('Location: ' . appUrl('gestisci_shop.php') . '?msg=' . urlencode('Prodotto eliminato'));
      exit;
    }
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}

$shopId = (int)$shop['id'];

$stmt = $conn->prepare('SELECT * FROM prodotto WHERE shop_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $shopId);
$stmt->execute();
$prodotti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
  'SELECT op.*, p.titolo, u.nome, u.cognome, u.email
   FROM offerta_prezzo op
   JOIN prodotto p ON p.id = op.prodotto_id
   JOIN utente u ON u.ID = op.utente_id
   WHERE op.officina_id = ?
   ORDER BY op.created_at DESC'
);
$officinaId = (int)$officina['id'];
$stmt->bind_param('i', $officinaId);
$stmt->execute();
$offerte = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Gestisci shop - Ricomoto</title>
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
        <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('dashboard.php')) ?>">Dashboard</a>
        <a class="btn btn-primary" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>">Apri storefront</a>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($msg): ?>
      <div class="ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="card">
      <div class="shop-hero">
        <div class="shop-logo-box large">
          <?= !empty($shop['logo'])
            ? '<img class="shop-logo" src="'.htmlspecialchars(mediaUrl($shop['logo'])).'" alt="logo">'
            : '<div class="shop-logo-fallback">'.htmlspecialchars(strtoupper(substr($shop['nome'],0,1))).'</div>' ?>
        </div>

        <div class="shop-hero-copy">
          <div class="kicker">Gestione shop</div>
          <h2><?= htmlspecialchars($shop['nome']) ?></h2>
          <p><?= htmlspecialchars($shop['localita']) ?> · Tenant <span class="accent"><?= htmlspecialchars($shop['slug']) ?></span></p>

          <div class="badge-row">
            <span class="badge"><?= count($prodotti) ?> prodotti</span>
            <span class="badge">Storefront attivo</span>
          </div>
        </div>

        <div class="btn-row">
          <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotti.php')) ?>">Marketplace</a>
        </div>
      </div>
    </section>

    <section class="section grid2">
      <div class="card">
        <div class="section-title">Branding e tenant</div>

        <form method="POST" enctype="multipart/form-data" class="mt-16">
          <input type="hidden" name="action" value="save_shop">

          <div class="field">
            <label>Nome shop</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($shop['nome']) ?>" required>
          </div>

          <div class="grid2">
            <div class="field">
              <label>Località</label>
              <input type="text" name="localita" value="<?= htmlspecialchars($shop['localita']) ?>" required>
            </div>

            <div class="field">
              <label>Slug tenant</label>
              <input type="text" name="slug" value="<?= htmlspecialchars($shop['slug']) ?>">
            </div>
          </div>

          <div class="field">
            <label>Nuovo logo</label>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
          </div>

          <div class="field">
            <label>Descrizione</label>
            <textarea name="descrizione" rows="4"><?= htmlspecialchars($shop['descrizione']) ?></textarea>
          </div>

          <div class="small">
            URL shop:
            <a class="link" target="_blank" href="<?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>">
              <?= htmlspecialchars(tenantStoreUrl($shop['slug'])) ?>
            </a>
          </div>

          <div class="btn-row mt-16">
            <button class="btn btn-primary" type="submit">Salva shop</button>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="section-title">Nuovo prodotto</div>
        <p>Inserisci un nuovo articolo nel catalogo dello shop.</p>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create_product">

          <div class="field">
            <label>Titolo</label>
            <input type="text" name="titolo" required>
          </div>

          <div class="field">
            <label>Prezzo</label>
            <input type="number" name="prezzo" step="0.01" min="0.01" required>
          </div>

          <div class="field">
            <label>Quantita disponibile</label>
            <select name="quantita" required>
              <?php for ($i = 1; $i <= 50; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="field">
            <label>
              <input type="checkbox" name="trattabile" checked>
              Prezzo trattabile
            </label>
          </div>

          <div class="field">
            <label>Descrizione</label>
            <textarea name="descrizione_prodotto" rows="5" required></textarea>
          </div>

          <div class="field">
            <label>Immagine</label>
            <input type="file" name="immagine" accept=".jpg,.jpeg,.png,.webp" required>
          </div>

          <button class="btn btn-primary" type="submit">Aggiungi prodotto</button>
        </form>
      </div>
    </section>

    <section class="section">
      <div class="section-head">
        <div class="section-title">Offerte ricevute</div>
      </div>

      <?php if (!$offerte): ?>
        <div class="card">
          <p>Nessuna offerta ricevuta.</p>
        </div>
      <?php else: ?>
        <div class="stack">
          <?php foreach ($offerte as $o): ?>
            <div class="card">
              <strong><?= htmlspecialchars($o['titolo']) ?></strong>

              <p>
                Offerta di <?= htmlspecialchars($o['nome'] . ' ' . $o['cognome']) ?>:
                <strong>€ <?= number_format((float)$o['prezzo_offerto'], 2, ',', '.') ?></strong>
              </p>

              <p><?= nl2br(htmlspecialchars($o['messaggio'] ?? '')) ?></p>

              <div class="small">
                Stato: <?= htmlspecialchars($o['stato']) ?> · <?= htmlspecialchars($o['email']) ?>
              </div>

              <?php if ($o['stato'] === 'in_attesa'): ?>
                <div class="btn-row mt-16">
                  <form method="POST" action="<?= htmlspecialchars(appUrl('gestisci_offerta.php')) ?>">
                    <input type="hidden" name="offerta_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="azione" value="accetta">
                    <button class="btn btn-primary" type="submit">Accetta offerta</button>
                  </form>

                  <form method="POST" action="<?= htmlspecialchars(appUrl('gestisci_offerta.php')) ?>">
                    <input type="hidden" name="offerta_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="azione" value="rifiuta">
                    <button class="btn btn-danger" type="submit">Rifiuta offerta</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="section">
      <div class="section-head">
        <div class="section-title">Prodotti dello shop</div>
      </div>

      <?php if (!$prodotti): ?>
        <div class="card">
          <p>Ancora nessun prodotto nel catalogo.</p>
        </div>
      <?php else: ?>
        <div class="stack">
          <?php foreach ($prodotti as $p): ?>
            <div class="card">
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                <div class="grid-product-manage">
                  <div>
                    <img src="<?= htmlspecialchars(mediaUrl($p['immagine'])) ?>" alt="img" class="manage-thumb">
                  </div>

                  <div>
                    <div class="grid2">
                      <div class="field">
                        <label>Titolo</label>
                        <input type="text" name="titolo" value="<?= htmlspecialchars($p['titolo']) ?>">
                      </div>

                      <div class="field">
                        <label>Quantita disponibile</label>
                        <select name="quantita">
                          <?php for ($i = 0; $i <= 50; $i++): ?>
                            <option value="<?= $i ?>" <?= (int)$p['quantita'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                          <?php endfor; ?>
                        </select>
                      </div>
                    </div>

                    <div class="grid2">
                      <div class="field">
                        <label>Prezzo</label>
                        <input type="number" name="prezzo" step="0.01" min="0.01" value="<?= htmlspecialchars($p['prezzo']) ?>">
                      </div>

                      <div class="field">
                        <label>
                          <input type="checkbox" name="trattabile" <?= (int)$p['trattabile'] === 1 ? 'checked' : '' ?>>
                          Prezzo trattabile
                        </label>
                      </div>
                    </div>

                    <div class="field">
                      <label>Descrizione</label>
                      <textarea name="descrizione" rows="3"><?= htmlspecialchars($p['descrizione']) ?></textarea>
                    </div>

                    <div class="field">
                      <label>Cambia immagine</label>
                      <input type="file" name="immagine_<?= (int)$p['id'] ?>" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="btn-row">
                      <button class="btn btn-primary" type="submit">Salva prodotto</button>
                      <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('prodotto.php?id=' . (int)$p['id'])) ?>">
                        Apri pagina prodotto
                      </a>
                    </div>
                  </div>
                </div>
              </form>

              <form method="POST" onsubmit="return confirm('Eliminare il prodotto?');" class="mt-12">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-danger" type="submit">Elimina prodotto</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
</body>
</html>
