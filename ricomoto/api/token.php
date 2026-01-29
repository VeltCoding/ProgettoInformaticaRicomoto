<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../db.php";   // $conn (mysqli)
require_once __DIR__ . "/jwt.php";     // jwt_sign()

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["error" => "Method not allowed. Use POST"]);
  exit;
}

// accetta JSON o form
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = [];

$email = trim((string)($data["email"] ?? ($_POST["email"] ?? "")));
$password = (string)($data["password"] ?? ($_POST["password"] ?? ""));

if ($email === "" || $password === "") {
  http_response_code(400);
  echo json_encode(["error" => "Missing email or password"]);
  exit;
}

// 🔥 tabella/colonne REALI
$stmt = $conn->prepare("SELECT ID, password FROM utente WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
  http_response_code(401);
  echo json_encode(["error" => "Invalid credentials"]);
  exit;
}

if (!password_verify($password, $user["password"])) {
  http_response_code(401);
  echo json_encode(["error" => "Invalid credentials"]);
  exit;
}

$userId = (int)$user["ID"];

// JWT valido 10 minuti = 600 secondi
$token = jwt_sign(["sub" => $userId], 600);

echo json_encode([
  "user_id" => $userId,
  "token" => $token,
  "token_type" => "Bearer",
  "expires_in" => 600
], JSON_UNESCAPED_UNICODE);
