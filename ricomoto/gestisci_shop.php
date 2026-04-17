<?php
/**
 * Gestisci Shop - Pannello per gestire prodotti dello shop
 */

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/router.php";

$err = null;
$success = null;

// Verifica che l'utente sia un'officina con shop
if (!hasPermission("prodotto.crea")) {
    header("Location: login.php?redirect=gestisci_shop");
    exit;
}

// Ottieni l'officina e la tenancy
$stmt = $conn->prepare("SELECT o.*, u.tenancy_id, t.nome_shop, t.subdomain, t.colore_primario, t.colore_secondario 
                        FROM officina o 
                        JOIN utente u ON o.utente_id = u.ID 
                        LEFT JOIN tenancy t ON u.tenancy_id = t.id
                        WHERE o.utente_id = ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$officina = $stmt->fetch(PDO::FETCH_ASSOC);

// Se non ha uno shop, reindirizza alla creazione
if (empty($officina['tenancy_id'])) {
    header("Location: crea_shop.php");
    exit;
}

$tenancyId = $officina['tenancy_id'];

// Gestione azioni (elimina/modifica prodotto)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["azione"])) {
    try {
        $azione = $_POST["azione"];
        
        if ($azione === "elimina_prodotto") {
            $prodottoId = intval($_POST["prodotto_id"]);
            
            // Verifica che il prodotto appartenga a questa officina/tenancy
            $stmt = $conn->prepare("SELECT id FROM prodotto WHERE id = ? AND officina_id = ?");
            $stmt->bindValue(1, $prodottoId, PDO::PARAM_INT);
            $stmt->bindValue(2, $officina['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                throw new Exception("Prodotto non trovato o non appartiene alla tua officina.");
            }
            
            $stmt = $conn->prepare("DELETE FROM prodotto WHERE id = ?");
            $stmt->bindValue(1, $prodottoId, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Prodotto eliminato con successo!";
        }
        
        if ($azione === "cambia_stato") {
            $prodottoId = intval($_POST["prodotto_id"]);
            $nuovoStato = $_POST["stato"];
            
            $stmt = $conn->prepare("UPDATE prodotto SET stato = ? WHERE id = ? AND officina_id = ?");
            $stmt->bindValue(1, $nuovoStato, PDO::PARAM_STR);
            $stmt->bindValue(2, $prodottoId, PDO::PARAM_INT);
            $stmt->bindValue(3, $officina['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Stato prodotto aggiornato!";
        }
        
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// Carica prodotti dell'officina
$stmt = $conn->prepare("SELECT * FROM prodotto WHERE officina_id = ? ORDER BY created_at DESC");
$stmt->bindValue(1, $officina['id'], PDO::PARAM_INT);
$stmt->execute();
$prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiche
$totProdotti = count($prodotti);
$prodottiDisponibili = count(array_filter($prodotti, fn($p) => $p['stato'] === 'disponibile'));
$prodottiVenduti = count(array_filter($prodotti, fn($p) => $p['stato'] === 'venduto'));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestisci Shop - <?= htmlspecialchars($officina['nome_shop']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #0f1115; color: #e8e8e8; min-height: 100vh; }
        .header-shop {
            background: linear-gradient(135deg, #35a7ff 0%, #7c3aed 100%);
            padding: 30px;
            border-radius: 22px;
            margin-bottom: 20px;
        }
        .shop-name { font-size: 28px; font-weight: 700; color: white; }
        .shop-url { color: rgba(255,255,255,0.8); font-size: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-box {
            background: rgba(255,255,255,0.04);
            border: 2px solid rgba(255,255,255,0.18);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
        }
        .stat-num { font-size: 32px; font-weight: 700; }
        .stat-label { font-size: 13px; color: #b9b9b9; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .product-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 16px;
            overflow: hidden;
        }
        .product-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: linear-gradient(135deg, #35a7ff 0%, #2563eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        .product-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-body { padding: 16px; }
        .product-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .product-desc { font-size: 14px; color: #b9b9b9; margin-bottom: 12px; line-height: 1.4; }
        .product-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .status-disponibile { background: rgba(0,255,160,0.15); color: #50ffa0; border: 1px solid rgba(0,255,160,0.35); }
        .status-venduto { background: rgba(255,80,80,0.15); color: #ffb3b3; border: 1px solid rgba(255,80,80,0.35); }
        .product-actions { display: flex; gap: 10px; margin-top: 12px; }
        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-add { background: #35a7ff; color: #06131f; }
        .btn-add:hover { background: #5bbfff; }
        .btn-edit { background: rgba(255,255,255,0.1); color: #e8e8e8; border: 1px solid rgba(255,255,255,0.2); }
        .btn-edit:hover { background: rgba(255,255,255,0.2); }
        .btn-delete { background: rgba(255,80,80,0.15); color: #ffb3b3; border: 1px solid rgba(255,80,80,0.3); }
        .btn-delete:hover { background: rgba(255,80,80,0.3); }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #b9b9b9;
        }
        .empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
    </style>
</head>
<body>
<div class="container" style="padding: 30px 20px; max-width: 1000px;">
    
    <!-- Header Shop -->
    <div class="header-shop">
        <div class="shop-name">
            <i class="fas fa-store"></i> <?= htmlspecialchars($officina['nome_shop']) ?>
        </div>
        <div class="shop-url">
            <i class="fas fa-link"></i> <?= htmlspecialchars($officina['subdomain']) ?>.localhost/ricomoto
        </div>
    </div>

    <!-- Messaggi -->
    <?php if ($success): ?>
        <div class="ok"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($err): ?>
        <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Statistiche -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-num"><?= $totProdotti ?></div>
            <div class="stat-label">Totale Prodotti</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color: #50ffa0;"><?= $prodottiDisponibili ?></div>
            <div class="stat-label">Disponibili</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color: #ffb3b3;"><?= $prodottiVenduti ?></div>
            <div class="stat-label">Venduti</div>
        </div>
    </div>

    <!-- Azioni -->
    <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
        <a href="carica_prodotto.php" class="btn btn-blue" style="flex: 1; text-align: center;">
            <i class="fas fa-plus"></i> Carica Nuovo Prodotto
        </a>
        <a href="dashboard.php" class="btn btn-white" style="flex: 1; text-align: center;">
            <i class="fas fa-arrow-left"></i> Torna alla Dashboard
        </a>
    </div>

    <!-- Prodotti -->
    <?php if (empty($prodotti)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Nessun prodotto ancora</h3>
            <p>Carica il tuo primo prodotto per iniziare a vendere!</p>
            <a href="carica_prodotto.php" class="btn btn-blue" style="display: inline-block; margin-top: 15px;">
                <i class="fas fa-plus"></i> Carica Prodotto
            </a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($prodotti as $p): ?>
                <div class="product-card">
                    <div class="product-img">
                        <?php if (!empty($p['immagine']) && file_exists(__DIR__ . '/' . $p['immagile'])): ?>
                            <img src="<?= htmlspecialchars($p['immagile']) ?>" alt="<?= htmlspecialchars($p['titolo']) ?>">
                        <?php else: ?>
                            <i class="fas fa-motorcycle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="product-title"><?= htmlspecialchars($p['titolo']) ?></div>
                        <div class="product-desc"><?= htmlspecialchars(mb_substr($p['descrizione'], 0, 100)) ?>...</div>
                        <span class="product-status <?= $p['stato'] === 'disponibile' ? 'status-disponibile' : 'status-venduto' ?>">
                            <?= ucfirst($p['stato']) ?>
                        </span>
                        <div class="product-actions">
                            <a href="modifica_prodotto.php?id=<?= $p['id'] ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Modifica
                            </a>
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Sei sicuro di eliminare questo prodotto?');">
                                <input type="hidden" name="azione" value="elimina_prodotto">
                                <input type="hidden" name="prodotto_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i> Elimina
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>