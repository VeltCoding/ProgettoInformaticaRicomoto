<?php
/**
 * Modifica Prodotto - Modifica un prodotto esistente
 */

require_once __DIR__ . "/auth.php";

$err = null;
$success = null;

// Verifica che l'utente sia un'officina
if (!hasPermission("prodotto.crea")) {
    header("Location: login.php");
    exit;
}

$prodottoId = intval($_GET["id"] ?? 0);

if ($prodottoId <= 0) {
    header("Location: gestisci_shop.php");
    exit;
}

// Ottieni l'officina dell'utente
$stmt = $conn->prepare("SELECT o.*, u.tenancy_id FROM officina o JOIN utente u ON o.utente_id = u.ID WHERE o.utente_id = ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$officina = $stmt->fetch(PDO::FETCH_ASSOC);

// Carica il prodotto
$stmt = $conn->prepare("SELECT * FROM prodotto WHERE id = ? AND officina_id = ?");
$stmt->bindValue(1, $prodottoId, PDO::PARAM_INT);
$stmt->bindValue(2, $officina['id'], PDO::PARAM_INT);
$stmt->execute();
$prodotto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prodotto) {
    header("Location: gestisci_shop.php");
    exit;
}

// Gestione modifica
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $titolo = trim($_POST["titolo"] ?? "");
        $descrizione = trim($_POST["descrizione"] ?? "");
        $stato = $_POST["stato"] ?? "disponibile";

        if (empty($titolo)) {
            throw new Exception("Il titolo è obbligatorio.");
        }

        $stmt = $conn->prepare("UPDATE prodotto SET titolo = ?, descrizione = ?, stato = ? WHERE id = ? AND officina_id = ?");
        $stmt->bindValue(1, $titolo, PDO::PARAM_STR);
        $stmt->bindValue(2, $descrizione, PDO::PARAM_STR);
        $stmt->bindValue(3, $stato, PDO::PARAM_STR);
        $stmt->bindValue(4, $prodottoId, PDO::PARAM_INT);
        $stmt->bindValue(5, $officina['id'], PDO::PARAM_INT);
        $stmt->execute();

        $success = "Prodotto aggiornato con successo!";

        // Ricarica i dati
        $stmt = $conn->prepare("SELECT * FROM prodotto WHERE id = ?");
        $stmt->bindValue(1, $prodottoId, PDO::PARAM_INT);
        $stmt->execute();
        $prodotto = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Prodotto - Ricomoto</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #0f1115; color: #e8e8e8; min-height: 100vh; display: block; padding: 40px 20px; }
        .edit-card {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(255,255,255,0.18);
            border-radius: 22px;
            padding: 30px;
        }
        .card-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #b9b9b9;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            background: transparent;
            color: #e8e8e8;
            border: 2px solid rgba(255,255,255,0.18);
            font-size: 15px;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #35a7ff;
            outline: none;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .btn-save {
            width: 100%;
            padding: 16px;
            border-radius: 14px;
            background: #35a7ff;
            color: #06131f;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }
        .btn-save:hover { background: #5bbfff; }
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #b9b9b9;
            text-decoration: none;
        }
        .btn-back:hover { color: #35a7ff; }
        .current-img {
            margin-bottom: 15px;
        }
        .current-img img {
            max-width: 200px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.18);
        }
    </style>
</head>
<body>
<div class="edit-card">
    <div class="card-title">
        <i class="fas fa-edit"></i> Modifica Prodotto
    </div>

    <?php if ($success): ?>
        <div class="ok"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($err): ?>
        <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Titolo</label>
            <input type="text" name="titolo" required value="<?= htmlspecialchars($prodotto['titolo']) ?>">
        </div>

        <div class="form-group">
            <label>Descrizione</label>
            <textarea name="descrizione"><?= htmlspecialchars($prodotto['descrizione']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Stato</label>
            <select name="stato">
                <option value="disponibile" <?= $prodotto['stato'] === 'disponibile' ? 'selected' : '' ?>>Disponibile</option>
                <option value="venduto" <?= $prodotto['stato'] === 'venduto' ? 'selected' : '' ?>>Venduto</option>
            </select>
        </div>

        <div class="form-group">
            <label>Immagine attuale</label>
            <div class="current-img">
                <?php if (!empty($prodotto['immagile'])): ?>
                    <img src="<?= htmlspecialchars($prodotto['immagile']) ?>" alt="Immagine prodotto">
                <?php else: ?>
                    <span style="color: #666;">Nessuna immagine</span>
                <?php endif; ?>
            </div>
            <small style="color: #666;">Per cambiare immagine, carica un nuovo prodotto</small>
        </div>

        <button type="submit" class="btn-save">
            <i class="fas fa-save"></i> Salva Modifiche
        </button>
    </form>

    <a href="gestisci_shop.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Torna alla Gestione Shop
    </a>
</div>
</body>
</html>