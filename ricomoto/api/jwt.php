<?php
declare(strict_types=1);

const JWT_SECRET = "SUPERSegretissimo123"; // DEVE essere uguale ovunque
const JWT_ISSUER = "ricomoto-api";

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign(array $payload, int $ttlSeconds = 600): string {
    $header = ["alg" => "HS256", "typ" => "JWT"];
    $now = time();

    $payload["iat"] = $now;
    $payload["exp"] = $now + $ttlSeconds;
    $payload["iss"] = JWT_ISSUER;

    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    $s = b64url_encode(hash_hmac("sha256", "$h.$p", JWT_SECRET, true));

    return "$h.$p.$s";
}

function jwt_verify(string $jwt): array {
    [$h, $p, $s] = explode('.', $jwt);

    $valid = b64url_encode(hash_hmac("sha256", "$h.$p", JWT_SECRET, true));
    if (!hash_equals($valid, $s)) {
        http_response_code(401);
        exit(json_encode(["error" => "Firma JWT non valida"]));
    }

    $payload = json_decode(b64url_decode($p), true);

    if ($payload["iss"] !== JWT_ISSUER) {
        http_response_code(401);
        exit(json_encode(["error" => "Issuer non valido"]));
    }

    if ($payload["exp"] < time()) {
        http_response_code(401);
        exit(json_encode(["error" => "Token scaduto"]));
    }

    return $payload;
}

