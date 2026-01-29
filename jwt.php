
<?php
// Cambia questa chiave con una lunga e casuale (non pubblicarla)
const JWT_SECRET = "CAMBIA_QUESTA_CHIAVE_SUPER_SEGRETA_LUNGA_123456789";
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

  // campi standard
  $payload["iat"] = $payload["iat"] ?? $now;
  $payload["exp"] = $payload["exp"] ?? ($now + $ttlSeconds);
  $payload["iss"] = $payload["iss"] ?? JWT_ISSUER;

  $h = b64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
  $p = b64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
  $sig = hash_hmac('sha256', "$h.$p", JWT_SECRET, true);
  $s = b64url_encode($sig);
  return "$h.$p.$s";
}

function jwt_verify(string $jwt): array {
  $parts = explode('.', $jwt);
  if (count($parts) !== 3) throw new Exception("Token malformato");

  [$h, $p, $s] = $parts;
  $sigCheck = b64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));

  if (!hash_equals($sigCheck, $s)) throw new Exception("Firma non valida");

  $payload = json_decode(b64url_decode($p), true);
  if (!is_array($payload)) throw new Exception("Payload non valido");

  if (isset($payload["exp"]) && time() > (int)$payload["exp"]) {
    throw new Exception("Token scaduto");
  }

  if (isset($payload["iss"]) && $payload["iss"] !== JWT_ISSUER) {
    throw new Exception("Issuer non valido");
  }

  return $payload;
}

function get_bearer_token(): ?string {
  $auth = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
  if (stripos($auth, "Bearer ") === 0) return trim(substr($auth, 7));
  return null;
}
PHP;
