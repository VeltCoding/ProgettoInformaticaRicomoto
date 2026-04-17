<?php
/**
 * Creazione Shop - Permette alle officine di creare il proprio shop online
 */

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/router.php";

$err = null;
$success = null;

// Verifica che l'utente sia un'officina
if (!hasPermission("prodotto.crea")) {
    header("Location: login.php?redirect=crea_shop");
    exit;
}

// Ottieni l'officina dell'utente corrente
$stmt = $conn->prepare("SELECT o.*, u.tenancy_id FROM officina o JOIN utente u ON o.utente_id = u.ID WHERE o.utente_id = ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$officina = $stmt->fetch(PDO::FETCH_ASSOC);

// Se l'officina ha già uno shop, reindirizza alla gestione
if (!empty($officina['tenancy_id'])) {
    header("Location: gestisci_shop.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $nome_shop = trim($_POST["nome_shop"] ?? "");
        $subdomain = trim($_POST["subdomain"] ?? "");
        $descrizione = trim($_POST["descrizione"] ?? "");
        $contatti = trim($_POST["contatti"] ?? "");
        $colore_primario = trim($_POST["colore_primario"] ?? "#35a7ff");
        $colore_secondario = trim($_POST["colore_secondario"] ?? "#0f1115");

        // Validazione
        if (empty($nome_shop) || empty($subdomain)) {
            throw new Exception("Compila tutti i campi richiesti.");
        }

        // Normalizza subdomain (lowercase, solo lettere/numeri/trattini)
        $subdomain = strtolower(preg_replace('/[^a-z0-9-]/', '', $subdomain));
        $subdomain = trim($subdomain, '-');

        if (strlen($subdomain) < 3) {
            throw new Exception("Il subdomain deve essere di almeno 3 caratteri.");
        }

        // Verifica subdomain unico
        $stmt = $conn->prepare("SELECT id FROM tenancy WHERE subdomain = ?");
        $stmt->bindValue(1, $subdomain, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetch()) {
            throw new Exception("Questo subdomain è già in uso. Scegli un altro nome.");
        }

        // Crea la tenancy (shop)
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO tenancy (nome_shop, subdomain, descrizione, contatti, colore_primario, colore_secondario, stato) VALUES (?, ?, ?, ?, ?, ?, 'attivo')");
        $stmt->bindValue(1, $nome_shop, PDO::PARAM_STR);
        $stmt->bindValue(2, $subdomain, PDO::PARAM_STR);
        $stmt->bindValue(3, $descrizione, PDO::PARAM_STR);
        $stmt->bindValue(4, $contatti, PDO::PARAM_STR);
        $stmt->bindValue(5, $colore_primario, PDO::PARAM_STR);
        $stmt->bindValue(6, $colore_secondario, PDO::PARAM_STR);
        $stmt->execute();

        $tenancyId = $conn->lastInsertId();

        // Collega l'officina alla tenancy
        $stmt = $conn->prepare("UPDATE officina SET tenancy_id = ? WHERE id = ?");
        $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
        $stmt->bindValue(2, $officina['id'], PDO::PARAM_INT);
        $stmt->execute();

        // Collega l'utente alla tenancy
        $stmt = $conn->prepare("UPDATE utente SET tenancy_id = ? WHERE ID = ?");
        $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
        $stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        // Assegna ruolo admin_tenancy se non già assegnato
        $stmt = $conn->prepare("SELECT role_id FROM user_roles WHERE user_id = ? AND role_id = (SELECT id FROM roles WHERE name = 'admin_tenancy')");
        $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = 'admin_tenancy'");
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        }

        $conn->commit();

        $success = "Shop creato con successo! Il tuo shop sarà disponibile su: <strong>{$subdomain}.localhost/ricomoto</strong>";

    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $t) {}
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea il tuo Shop - Ricomoto</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #0f1115; color: #e8e8e8; min-height: 100vh; display: block; padding: 40px 20px; }
        .create-shop-card {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(255,255,255,0.18);
            border-radius: 22px;
            padding: 30px;
        }
        .card-title {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }
        .card-subtitle {
            text-align: center;
            color: #b9b9b9;
            margin-bottom: 25px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #b9b9b9;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            background: transparent;
            color: #e8e8e8;
            border: 2px solid rgba(255,255,255,0.18);
            font-size: 15px;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #35a7ff;
            outline: none;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .color-row { display: flex; gap: 15px; }
        .color-row > div { flex: 1; }
        .color-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-input input[type="color"] {
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            padding: 0;
        }
        .preview-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            margin-top: 20px;
        }
        .preview-label { font-size: 12px; color: #b9b9b9; margin-bottom: 8px; }
        .preview-url { 
            background: #1a1d24; 
            padding: 10px 16px; 
            border-radius: 10px; 
            font-family: monospace; 
            color: #35a7ff;
            font-size: 14px;
        }
        .btn-create {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            background: #35a7ff;
            color: #06131f;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-create:hover { background: #5bbfff; }
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #b9b9b9;
            text-decoration: none;
        }
        .btn-back:hover { color: #35a7ff; }
    </style>
</head>
<body>
<div class="create-shop-card">
    <div class="card-title">
        <i class="fas fa-store"></i> Crea il tuo Shop
    </div>
    <div class="card-subtitle">Personalizza il tuo negozio online</div>

    <?php if ($success): ?>
        <div class="ok">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
        <a href="gestisci_shop.php" class="btn-create" style="text-decoration: none; display: block; text-align: center;">
            <i class="fas fa-tachometer-alt"></i> Vai alla Gestione Shop
        </a>
    <?php else: ?>
        <?php if ($err): ?>
            <div class="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nome del tuo Shop</label>
                <input type="text" name="nome_shop" placeholder="Es: MotoGP Racing" required
                       value="<?= htmlspecialchars($_POST['nome_shop'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Sottodominio (nome unico)</label>
                <input type="text" name="subdomain" placeholder="es: motogp" required
                       value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>">
                <small style="color: #666; font-size: 12px;">Il tuo shop sarà: sottodominio.localhost/ricomoto</small>
            </div>

            <div class="form-group">
                <label>Descrizione (opzionale)</label>
                <textarea name="descrizione" placeholder="Descrizione del tuo negozio..."><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Contatti (opzionale)</label>
                <input type="text" name="contatti" placeholder="Telefono, email, indirizzo..."
                       value="<?= htmlspecialchars($_POST['contatti'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Colori del tema</label>
                <div class="color-row">
                    <div>
                        <small style="color: #b9b9b9; display: block; margin-bottom: 5px;">Colore Principale</small>
                        <div class="color-input">
                            <input type="color" name="colore_primario" value="<?= htmlspecialchars($_POST['colore_primario'] ?? '#35a7ff') ?>">
                            <span style="font-size: 12px; color: #666;">Scegli</span>
                        </div>
                    </div>
                    <div>
                        <small style="color: #b9b9b9; display: block; margin-bottom: 5px;">Colore Sfondo</small>
                        <div class="color-input">
                            <input type="color" name="colore_secondario" value="<?= htmlspecialchars($_POST['colore_secondario'] ?? '#0f1115') ?>">
                            <span style="font-size: 12px; color: #666;">Scegli</span>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-create">
                <i class="fas fa-rocket"></i> Crea il tuo Shop
            </button>
        </form>

        <div class="preview-box">
            <div class="preview-label">Il tuo shop sarà disponibile su:</div>
            <div class="preview-url" id="previewUrl">motogp.localhost/ricomoto</div>
        </div>

        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Torna alla Dashboard
        </a>
    <?php endif; ?>
</div>

<script>
document.querySelector('input[name="subdomain"]').addEventListener('input', function(e) {
    const subdomain = e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    document.getElementById('previewUrl').textContent = subdomain + '.localhost/ricomoto';
});
</script>
</body>
</html>