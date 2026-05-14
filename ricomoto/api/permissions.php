<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

//CONFIG 
$JWT_SECRET = "SUPERSegretissimo123"; // stesso usato in login
$JWT_ISSUER = "ricomoto-api";

//JWT VERIFY
function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_verify(string $jwt, string $secret): array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        http_response_code(401);
        echo json_encode(["error" => "Token malformato"]);
        exit;
    }

    [$h, $p, $s] = $parts;

    $signature = hash_hmac('sha256', "$h.$p", $secret, true);
    $expected = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    if (!hash_equals($expected, $s)) {
        http_response_code(401);
        echo json_encode(["error" => "Firma non valida"]);
        exit;
    }

    $payload = json_decode(base64url_decode($p), true);

    if (!$payload || !isset($payload["sub"], $payload["exp"])) {
        http_response_code(401);
        echo json_encode(["error" => "Payload non valido"]);
        exit;
    }

    if ($payload["exp"] < time()) {
        http_response_code(401);
        echo json_encode(["error" => "Token scaduto"]);
        exit;
    }

    return $payload;
}

//HEADER AUTH
$headers = getallheaders();

if (!isset($headers["Authorization"])) {
    http_response_code(401);
    echo json_encode(["error" => "Authorization header mancante"]);
    exit;
}

if (!preg_match('/Bearer\s+(.*)$/i', $headers["Authorization"], $matches)) {
    http_response_code(401);
    echo json_encode(["error" => "Formato Authorization non valido"]);
    exit;
}

$jwt = $matches[1];
$payload = jwt_verify($jwt, $JWT_SECRET);

$userId = (int)$payload["sub"];

//DB (PDO)
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=ricomoto;charset=utf8mb4",
        "utente_phpmyadmin",
        "ringraziandoPENNETTA",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

//QUERY PERMESSI
$sql = "
SELECT DISTINCT p.code
FROM permissions p
JOIN role_permissions rp ON rp.permission_id = p.id
JOIN user_roles ur ON ur.role_id = rp.role_id
WHERE ur.user_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);

$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

//RESPONSE
echo json_encode([
    "user_id" => $userId,
    "permissions" => $permissions
]);
