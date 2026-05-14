<?php

declare(strict_types=1);

define('RICOMOTO_DISABLE_DB', true);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api/jwt.php';
require_once __DIR__ . '/login.php';

use PHPUnit\Framework\TestCase;

final class RicomotoFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_GET = [];
    }

    public function testIsLoggedInAndRolesPermissions(): void
    {
        $this->assertFalse(isLoggedIn());

        $_SESSION['user_id'] = 42;
        $_SESSION['roles'] = ['user', 'admin'];
        $_SESSION['permissions'] = ['acquisto.crea', 'shop.view'];

        $this->assertTrue(isLoggedIn());
        $this->assertTrue(hasRole('admin'));
        $this->assertFalse(hasRole('owner'));
        $this->assertTrue(hasPermission('acquisto.crea'));
        $this->assertFalse(hasPermission('shop.edit'));
    }

    public function testSafeNextUrl(): void
    {
        $this->assertSame('/dashboard.php', safeNextUrl('/dashboard.php', '/login.php'));
        $this->assertSame('/login.php', safeNextUrl('https://evil.example.com', '/login.php'));
        $this->assertSame('/login.php', safeNextUrl('', '/login.php'));
    }

    public function testAppUrlAssetAndMediaUrl(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/ricomoto/index.php';

        $this->assertSame('/ricomoto', appUrl());
        $this->assertSame('/ricomoto/shop.php', appUrl('shop.php'));
        $this->assertSame('/ricomoto/assets/image.png', assetUrl('assets/image.png'));
        $this->assertSame('/ricomoto/image.png', mediaUrl('image.png'));
        $this->assertSame('https://example.com/image.png', mediaUrl('https://example.com/image.png'));
        $this->assertSame('', mediaUrl(null));
    }

    public function testTenantHostParsing(): void
    {
        $_SERVER['HTTP_HOST'] = 'auto123.localhost';
        $this->assertSame('auto123', extractTenantSlugFromHost(null));

        $_SERVER['HTTP_HOST'] = 'notenant.example.com';
        $this->assertNull(extractTenantSlugFromHost(null));

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['tenant'] = 'MyShop';
        $this->assertSame('myshop', currentTenantSlug());
    }

    public function testSlugifyGeneratesSeoFriendlySlug(): void
    {
        $slug = slugify('Caffè & Moto! Shop 2026');
        $this->assertSame('caff-e-moto-shop-2026', $slug);
        $this->assertSame('shop', slugify('   '));
    }

    public function testDashboardTargetUsesAdminRole(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/ricomoto/index.php';
        $_SESSION['roles'] = ['admin'];

        $this->assertSame('/ricomoto/superdashboard.php', dashboardTarget());

        $_SESSION['roles'] = ['user'];
        $this->assertSame('/ricomoto/dashboard.php', dashboardTarget());
    }

    public function testTenantStoreUrlGeneratesCorrectLocalhostUrl(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/ricomoto/index.php';
        $_SERVER['HTTPS'] = 'on';

        $this->assertSame('https://motoshop.localhost/ricomoto/shop.php', tenantStoreUrl('motoshop', 'shop.php'));
    }

    public function testJwtSignAndVerifyReturnsPayload(): void
    {
        $payload = ['sub' => 7, 'role' => 'user'];
        $jwt = jwt_sign($payload, 10);

        $decoded = jwt_verify($jwt);

        $this->assertSame(7, $decoded['sub']);
        $this->assertSame('user', $decoded['role']);
        $this->assertSame(JWT_ISSUER, $decoded['iss']);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
    }

    public function testRedirectAfterLoginUsesNextUrl(): void
    {
        $this->assertSame('/order.php', redirectAfterLogin('/order.php'));
    }
}
