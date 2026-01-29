
<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/jwt.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$refreshToken = $data["refresh_token"] ?? "";

if ($refreshToken === "") {
  http_response_code(400);
  echo json_encode(["error" => "refresh_token richiesto"]);
  exit;
}

$hash = hash("sha256", $refreshToken);

// cerca token valido
$stmt = $conn->prepare("
  SELECT id, user_id, expires_at
  FROM refresh_token
  WHERE token_hash = ?
  LIMIT 1
");
$stmt->bind_param("s", $hash);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  http_response_code(401);
  echo json_encode(["error" => "refresh token non valido"]);
  exit;
}

if (strtotime($row["expires_at"]) < time()) {
  http_response_code(401);
  echo json_encode(["error" => "refresh token scaduto"]);
  exit;
}

$userId = (int)$row["user_id"];

// nuovo access token (10 minuti)
$accessToken = jwt_sign(["sub" => $userId], 600);

echo json_encode([
  "access_token" => $accessToken,
  "token_type" => "Bearer",
  "expires_in" => 600
], JSON_UNESCAPED_SLASHES);
PHP;
