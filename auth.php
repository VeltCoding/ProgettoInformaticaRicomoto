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
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $roles = [];
  while ($row = $res->fetch_assoc()) $roles[] = $row["name"];
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
  $stmt->bind_param("ii", $userId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $perms = [];
  while ($row = $res->fetch_assoc()) $perms[] = $row["code"];
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
