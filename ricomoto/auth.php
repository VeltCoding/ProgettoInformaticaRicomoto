<?php
require_once __DIR__ . "/db.php";
session_start();

function loadUserRoles(int $userId): array {
  global $conn;
  $sql = "SELECT r.name
          FROM roles r
          JOIN user_roles ur ON ur.role_id = r.id
          WHERE ur.user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(1, $userId);
  $stmt->execute();
  $roles = [];
  while ($row = $stmt->fetch()) $roles[] = $row["name"];
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
  $stmt->bindParam(1, $userId);
  $stmt->bindParam(2, $userId);
  $stmt->execute();
  $perms = [];
  while ($row = $stmt->fetch()) $perms[] = $row["code"];
  return $perms;
}

function isLoggedIn(): bool {
  return isset($_SESSION["user_id"]);
}

function hasPermission(string $perm): bool {
  return isset($_SESSION["permissions"]) && in_array($perm, $_SESSION["permissions"], true);
}

function requirePermission(string $perm): void {
  if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
  }
  if (!hasPermission($perm)) {
    http_response_code(403);
    die("Accesso negato (permesso richiesto: $perm)");
  }
}

// =====================================================
// MULTITENANCY FUNCTIONS
// =====================================================

/**
 * Ottiene l'ID del tenancy corrente dalla sessione o dal subdomain
 */
function getCurrentTenancyId(): ?int {
  global $conn;
  
  // Se è impostato in sessione (utente loggato)
  if (isset($_SESSION["tenancy_id"]) && $_SESSION["tenancy_id"] > 0) {
    return (int)$_SESSION["tenancy_id"];
  }
  
  // Altrimenti cerca il subdomain
  $subdomain = getSubdomain();
  if ($subdomain) {
    $stmt = $conn->prepare("SELECT id FROM tenancy WHERE subdomain = ? AND stato = 'attivo'");
    $stmt->bindParam(1, $subdomain);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (int)$row["id"] : null;
  }
  
  return null;
}

/**
 * Estrae il subdomain dall'URL corrente
 */
function getSubdomain(): ?string {
  $host = $_SERVER["HTTP_HOST"] ?? "";
  
  // Per localhost: localhost/tenancy/admin
  if (strpos($host, "localhost") !== false) {
    $uri = $_SERVER["REQUEST_URI"] ?? "";
    // Gestisci path come /tenancy/admin
    if (strpos($uri, "/tenancy/") === 0) {
      $parts = explode("/", trim($uri, "/"));
      if (isset($parts[2]) && $parts[2] === "admin") {
        return "admin";
      }
      return $parts[2] ?? null;
    }
    return null;
  }
  
  // Per dominio reale: subdomain.dominio.com
  $parts = explode(".", $host);
  if (count($parts) >= 3) {
    return $parts[0]; // Prima parte = subdomain
  }
  
  return null;
}

/**
 * Verifica se l'utente corrente è Admin Tenancy
 */
function isAdminTenancy(): bool {
  return isset($_SESSION["roles"]) && in_array("admin_tenancy", $_SESSION["roles"], true);
}

/**
 * Verifica se l'utente corrente è Admin globale
 */
function isAdmin(): bool {
  return isset($_SESSION["roles"]) && in_array("admin", $_SESSION["roles"], true);
}

/**
 * Carica il tenancy_id per un utente officina
 */
function loadUserTenancy(int $userId): ?int {
  global $conn;
  $stmt = $conn->prepare("SELECT tenancy_id FROM officina WHERE utente_id = ?");
  $stmt->bindParam(1, $userId);
  $stmt->execute();
  $row = $stmt->fetch();
  return $row && $row["tenancy_id"] ? (int)$row["tenancy_id"] : null;
}

/**
 * Reindirizza alla dashboard del tenancy appropriata
 */
function redirectToTenancyDashboard(): void {
  if (isAdminTenancy()) {
    header("Location: /tenancy/admin");
    exit;
  }
  
  $tenancyId = getCurrentTenancyId();
  if ($tenancyId) {
    // Redirect allo shop del tenancy
    $subdomain = getSubdomain();
    header("Location: /tenancy/$subdomain");
    exit;
  }
  
  // Default: dashboard normale
  header("Location: dashboard.php");
  exit;
}
