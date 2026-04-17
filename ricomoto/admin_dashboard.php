<?php
/**
 * Dashboard Admin - Gestione centralizzata di tutte le tenancy e utenti
 */

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/router.php";

// Solo admin globale può accedere
if (!isAdmin()) {
    header("Location: login.php?redirect=admin_dashboard");
    exit;
}

$err = null;
$success = null;

// Gestione azioni
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["azione"])) {
    try {
        $azione = $_POST["azione"];
        
        if ($azione === "cambia_stato_tenancy") {
            $tenancyId = intval($_POST["tenancy_id"]);
            $nuovoStato = $_POST["stato"];
            
            $stmt = $conn->prepare("UPDATE tenancy SET stato = ? WHERE id = ?");
            $stmt->bindValue(1, $nuovoStato, PDO::PARAM_STR);
            $stmt->bindValue(2, $tenancyId, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Stato tenancy aggiornato con successo!";
        }
        
        if ($azione === "elimina_tenancy") {
            $tenancyId = intval($_POST["tenancy_id"]);
            
            // Non permettere eliminazione tenancy centrale
            if ($tenancyId === 1) {
                throw new Exception("Non puoi eliminare la tenancy centrale!");
            }
            
            $conn->beginTransaction();
            
            // Rimuovi tenancy_id da utenti
            $stmt = $conn->prepare("UPDATE utente SET tenancy_id = NULL WHERE tenancy_id = ?");
            $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Rimuovi tenancy_id da officine
            $stmt = $conn->prepare("UPDATE officina SET tenancy_id = NULL WHERE tenancy_id = ?");
            $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Rimuovi tenancy_id da prodotti
            $stmt = $conn->prepare("UPDATE prodotto SET tenancy_id = NULL WHERE tenancy_id = ?");
            $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Elimina tenancy
            $stmt = $conn->prepare("DELETE FROM tenancy WHERE id = ?");
            $stmt->bindValue(1, $tenancyId, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            
            $success = "Tenancy eliminata (dati collegati preservati)!";
        }
        
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// Carica dati per la dashboard
$stmt = $conn->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM utente WHERE tenancy_id = t.id) as tot_utenti,
           (SELECT COUNT(*) FROM officina WHERE tenancy_id = t.id) as tot_officine,
           (SELECT COUNT(*) FROM prodotto WHERE tenancy_id = t.id) as tot_prodotti
    FROM tenancy t
    ORDER BY t.id ASC
");
$tenancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiche globali
$stats = [
    "tot_tenancy" => $conn->query("SELECT COUNT(*) FROM tenancy")->fetchColumn(),
    "tenancy_attive" => $conn->query("SELECT COUNT(*) FROM tenancy WHERE stato = 'attivo'")->fetchColumn(),
    "tot_utenti" => $conn->query("SELECT COUNT(*) FROM utente")->fetchColumn(),
    "tot_officine" => $conn->query("SELECT COUNT(*) FROM officina")->fetchColumn(),
    "tot_prodotti" => $conn->query("SELECT COUNT(*) FROM prodotto")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ricomoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--light);
        }
        .sidebar {
            min-height: 100vh;
            background: var(--dark);
            color: white;
            padding: 20px;
        }
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 16px;
            display: block;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: var(--primary);
            color: white;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .table-card .card-header {
            background: var(--dark);
            color: white;
            padding: 20px;
            border: none;
        }
        .table thead th {
            background: #f1f5f9;
            border: none;
            font-weight: 600;
            color: var(--dark);
            padding: 16px;
        }
        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }
        .badge-attivo { background: #d1fae5; color: #065f46; }
        .badge-sospeso { background: #fef3c7; color: #92400e; }
        .badge-disattivato { background: #fee2e2; color: #991b1b; }
        .btn-sm { padding: 8px 16px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Admin Panel</h4>
                <a href="admin_dashboard.php" class="active"><i class="fas fa-home me-2"></i>Dashboard</a>
                <a href="#"><i class="fas fa-store me-2"></i>Tenancy</a>
                <a href="#"><i class="fas fa-users me-2"></i>Utenti</a>
                <a href="#"><i class="fas fa-box me-2"></i>Prodotti</a>
                <a href="#"><i class="fas fa-cog me-2"></i>Impostazioni</a>
                <hr>
                <a href="index.php"><i class="fas fa-arrow-left me-2"></i>Torna al sito</a>
            </div>
            
            <!-- Contenuto principale -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Amministratore</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($err): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $err ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistiche -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Totale Shop</h6>
                                    <h3 class="mb-0"><?= $stats["tot_tenancy"] ?></h3>
                                </div>
                                <div class="icon" style="background: #dbeafe; color: #2563eb;">
                                    <i class="fas fa-store"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Shop Attivi</h6>
                                    <h3 class="mb-0"><?= $stats["tenancy_attive"] ?></h3>
                                </div>
                                <div class="icon" style="background: #d1fae5; color: #10b981;">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Officine</h6>
                                    <h3 class="mb-0"><?= $stats["tot_officine"] ?></h3>
                                </div>
                                <div class="icon" style="background: #fef3c7; color: #f59e0b;">
                                    <i class="fas fa-wrench"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Prodotti</h6>
                                    <h3 class="mb-0"><?= $stats["tot_prodotti"] ?></h3>
                                </div>
                                <div class="icon" style="background: #fce7f3; color: #db2777;">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabella Tenancy -->
                <div class="table-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-store me-2"></i>Gestione Shop/Tenancy</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome Shop</th>
                                    <th>Subdomain</th>
                                    <th>Stato</th>
                                    <th>Utenti</th>
                                    <th>Officine</th>
                                    <th>Prodotti</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenancies as $t): ?>
                                <tr>
                                    <td><?= $t["id"] ?></td>
                                    <td><strong><?= htmlspecialchars($t["nome_shop"]) ?></strong></td>
                                    <td><code><?= htmlspecialchars($t["subdomain"]) ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $t["stato"] ?>">
                                            <?= ucfirst($t["stato"]) ?>
                                        </span>
                                    </td>
                                    <td><?= $t["tot_utenti"] ?></td>
                                    <td><?= $t["tot_officine"] ?></td>
                                    <td><?= $t["tot_prodotti"] ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Azioni
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="azione" value="cambia_stato_tenancy">
                                                        <input type="hidden" name="tenancy_id" value="<?= $t["id"] ?>">
                                                        <input type="hidden" name="stato" value="attivo">
                                                        <button type="submit" class="dropdown-item">Attiva</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="azione" value="cambia_stato_tenancy">
                                                        <input type="hidden" name="tenancy_id" value="<?= $t["id"] ?>">
                                                        <input type="hidden" name="stato" value="sospeso">
                                                        <button type="submit" class="dropdown-item"> Sospendi</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="azione" value="cambia_stato_tenancy">
                                                        <input type="hidden" name="tenancy_id" value="<?= $t["id"] ?>">
                                                        <input type="hidden" name="stato" value="disattivato">
                                                        <button type="submit" class="dropdown-item"> Disattiva</button>
                                                    </form>
                                                </li>
                                                <?php if ($t["id"] !== 1): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Sei sicuro di eliminare questa tenancy?');">
                                                        <input type="hidden" name="azione" value="elimina_tenancy">
                                                        <input type="hidden" name="tenancy_id" value="<?= $t["id"] ?>">
                                                        <button type="submit" class="dropdown-item text-danger"> Elimina</button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>