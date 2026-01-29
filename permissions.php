
<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/jwt.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

$token = get_bearer_token();
if (!$token) {
  http_response_code(401);
  echo json_encode(["error" => "Bearer token mancante"]);
  exit;
}

try {
  $payload = jwt_verify($token);
  $userId = (int)($payload["sub"] ?? 0);
  if ($userId <= 0) throw new Exception("Token senza sub valido");

  // ruoli
  $ruoli = [];
  $stmt = $conn->prepare("
    SELECT r.name
    FROM user_roles ur
    JOIN roles r ON r.id = ur.role_id
    WHERE ur.user_id = ?
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $ruoli[] = $row["name"];

  // permessi (da ruoli + permessi diretti)
  $permessi = [];
  $stmt = $conn->prepare("
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
  ");
  $stmt->bind_param("ii", $userId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $permessi[] = $row["code"];
  sort($permessi);

  echo json_encode([
    "user_id" => $userId,
    "roles" => $ruoli,
    "permissions" => $permessi
  ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  http_response_code(401);
  echo json_encode(["error" => $e->getMessage()]);
}
PHP;
