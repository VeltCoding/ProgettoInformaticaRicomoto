<?php
if (!defined('RICOMOTO_DISABLE_DB')) {
    require_once __DIR__ . '/db.php';
}
require_once __DIR__ . '/tenant.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function loadUserRoles(int $userId): array {
  global $conn;

  $sql = "SELECT r.name
          FROM roles r
          JOIN user_roles ur ON ur.role_id = r.id
          WHERE ur.user_id = ?";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $userId);
  $stmt->execute();

  $res = $stmt->get_result();
  $roles = [];

  while ($row = $res->fetch_assoc()) {
    $roles[] = $row['name'];
  }

  $stmt->close();
  return $roles;
}

function loadPermissions(int $userId): array {
  global $conn;

  $sql = "
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
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ii', $userId, $userId);
  $stmt->execute();

  $res = $stmt->get_result();
  $perms = [];

  while ($row = $res->fetch_assoc()) {
    $perms[] = $row['code'];
  }

  $stmt->close();
  return $perms;
}

function isLoggedIn(): bool {
  return isset($_SESSION['user_id']);
}

function hasRole(string $role): bool {
  return isset($_SESSION['roles']) && in_array($role, $_SESSION['roles'], true);
}

function hasPermission(string $perm): bool {
  return isset($_SESSION['permissions']) && in_array($perm, $_SESSION['permissions'], true);
}

function safeNextUrl(string $next, string $fallback): string {
  $next = trim($next);

  if ($next !== '' && substr($next, 0, 1) === '/') {
    return $next;
  }

  return $fallback;
}

function requireLogin(): void {
  if (!isLoggedIn()) {
    $next = $_SERVER['REQUEST_URI'] ?? appUrl('shop.php');
    header('Location: ' . appUrl('login.php') . '?next=' . urlencode($next));
    exit;
  }
}

function requirePermission(string $perm): void {
  if (!isLoggedIn()) {
    $next = $_SERVER['REQUEST_URI'] ?? appUrl('shop.php');
    header('Location: ' . appUrl('login.php') . '?next=' . urlencode($next));
    exit;
  }

  if (!hasPermission($perm)) {
    http_response_code(403);
    die('Accesso negato (permesso richiesto: ' . htmlspecialchars($perm) . ')');
  }
}

function currentUser(): ?array {
  global $conn;

  if (!isLoggedIn()) {
    return null;
  }

  static $user = false;

  if ($user !== false) {
    return $user;
  }

  $userId = (int)$_SESSION['user_id'];

  $stmt = $conn->prepare('SELECT ID, nome, cognome, email, telefono, indirizzo FROM utente WHERE ID = ?');
  $stmt->bind_param('i', $userId);
  $stmt->execute();

  $user = $stmt->get_result()->fetch_assoc() ?: null;

  $stmt->close();
  return $user;
}

function currentOfficina(): ?array {
  global $conn;

  if (!isLoggedIn()) {
    return null;
  }

  static $officina = false;

  if ($officina !== false) {
    return $officina;
  }

  $userId = (int)$_SESSION['user_id'];

  $stmt = $conn->prepare('SELECT * FROM officina WHERE utente_id = ? LIMIT 1');
  $stmt->bind_param('i', $userId);
  $stmt->execute();

  $officina = $stmt->get_result()->fetch_assoc() ?: null;

  $stmt->close();
  return $officina;
}

function currentShop(): ?array {
  global $conn;

  if (!isLoggedIn()) {
    return null;
  }

  $officina = currentOfficina();

  if (!$officina) {
    return null;
  }

  static $shop = false;

  if ($shop !== false) {
    return $shop;
  }

  $officinaId = (int)$officina['id'];

  $stmt = $conn->prepare('SELECT * FROM shop WHERE officina_id = ? LIMIT 1');
  $stmt->bind_param('i', $officinaId);
  $stmt->execute();

  $shop = $stmt->get_result()->fetch_assoc() ?: null;

  $stmt->close();
  return $shop;
}

function dashboardTarget(): string {
  if (hasRole('admin')) {
    return appUrl('superdashboard.php');
  }

  return appUrl('dashboard.php');
}