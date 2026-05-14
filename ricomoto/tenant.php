<?php
if (!defined('RICOMOTO_DISABLE_DB')) {
    require_once __DIR__ . '/db.php';
}

function appBasePath(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $dir === '/' ? '' : $dir;
}

function appUrl(string $path = ''): string {
    $base = appBasePath();
    $path = ltrim($path, '/');
    return $base . ($path !== '' ? '/' . $path : '');
}


function assetUrl(string $path): string {
    return appUrl(ltrim($path, '/'));
}

function mediaUrl(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return assetUrl($path);
}

function currentHost(): string {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = strtolower(trim(explode(':', $host)[0]));
    return $host;
}

function extractTenantSlugFromHost(?string $host = null): ?string {
    $host = strtolower($host ? trim(explode(':', $host)[0]) : currentHost());
    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
        return null;
    }

    if (preg_match('/^([a-z0-9-]+)\.localhost$/', $host, $m)) {
        return $m[1];
    }

    if (preg_match('/^([a-z0-9-]+)\.ricomoto\.com$/', $host, $m)) {
        return $m[1];
    }

    return null;
}

function currentTenantSlug(): ?string {
    $slug = extractTenantSlugFromHost();
    if ($slug) {
        return $slug;
    }
    $fallback = trim((string)($_GET['tenant'] ?? ''));
    return $fallback !== '' ? strtolower($fallback) : null;
}

function slugify(string $value): string {
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'shop';
}

function findUniqueShopSlug(string $baseSlug, ?int $ignoreShopId = null): string {
    global $conn;
    $slug = slugify($baseSlug);
    $candidate = $slug;
    $counter = 1;

    while (true) {
        if ($ignoreShopId) {
            $stmt = $conn->prepare('SELECT id FROM shop WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $candidate, $ignoreShopId);
        } else {
            $stmt = $conn->prepare('SELECT id FROM shop WHERE slug = ? LIMIT 1');
            $stmt->bind_param('s', $candidate);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) {
            return $candidate;
        }
        $counter++;
        $candidate = $slug . '-' . $counter;
    }
}

function findShopBySlug(string $slug): ?array {
    global $conn;
    $stmt = $conn->prepare(
        'SELECT s.*, o.nome AS officina_nome, o.utente_id, u.email AS owner_email
         FROM shop s
         JOIN officina o ON o.id = s.officina_id
         JOIN utente u ON u.ID = o.utente_id
         WHERE s.slug = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $shop = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $shop;
}

function currentTenantShop(): ?array {
    static $cached = false;
    static $shop = null;
    if ($cached) {
        return $shop;
    }
    $cached = true;
    $slug = currentTenantSlug();
    if (!$slug) {
        return null;
    }
    $shop = findShopBySlug($slug);
    return $shop;
}

function tenantStoreUrl(string $slug, string $path = 'shop.php'): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $basePath = appBasePath();
    $path = ltrim($path, '/');
    return $scheme . '://' . $slug . '.localhost' . $basePath . '/' . $path;
}

function ensureTenantContextOrRedirect(): array {
    $shop = currentTenantShop();
    if (!$shop) {
        header('Location: ' . appUrl('index.php'));
        exit;
    }
    return $shop;
}
