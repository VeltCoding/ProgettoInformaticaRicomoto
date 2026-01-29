
<?php
require_once __DIR__ . "/../db.php";   // usa la tua connessione mysqli $conn
require_once __DIR__ . "/jwt.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed"]);
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
  http_response_code(400);
  echo json_encode(["error" => "email e password richiesti"]);
  exit;
}

// verifica credenziali
$stmt = $conn->prepare("SELECT ID, password FROM utente WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user["password"])) {
  http_response_code(401);
  echo json_encode(["error" => "Credenziali non valide"]);
  exit;
}

$userId = (int)$user["ID"];

// Access token: 10 minuti
$accessToken = jwt_sign([
  "sub" => $userId
], 600);

// (Opzionale) Refresh token: valido 7 giorni
$refreshToken = bin2hex(random_bytes(32));
$refreshHash = hash("sha256", $refreshToken);
$expiresAt = (new DateTime("+7 days"))->format("Y-m-d H:i:s");

$stmt = $conn->prepare("INSERT INTO refresh_token(user_id, token_hash, expires_at) VALUES (?,?,?)");
$stmt->bind_param("iss", $userId, $refreshHash, $expiresAt);
$stmt->execute();

echo json_encode([
  "access_token" => $accessToken,
  "token_type" => "Bearer",
  "expires_in" => 600,
  "refresh_token" => $refreshToken
], JSON_UNESCAPED_SLASHES);
PHP;