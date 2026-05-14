<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api/jwt.php';

$err = null;
$next = $_GET['next'] ?? $_POST['next'] ?? '';

$tenantShop = currentTenantShop();

function redirectAfterLogin(string $next): string {
  if ($next !== '' && substr($next, 0, 1) === '/') {
    return $next;
  }

  if (currentTenantShop()) {
    return appUrl('shop.php');
  }

  return dashboardTarget();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($login === '' || $password === '') {
    $err = 'Compila email e password.';
  } else {
    $stmt = $conn->prepare('SELECT ID, password FROM utente WHERE email = ? OR nome = ? LIMIT 1');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
      $uid = (int)$user['ID'];

      session_regenerate_id(true);

      $_SESSION['user_id'] = $uid;
      $_SESSION['roles'] = loadUserRoles($uid);
      $_SESSION['permissions'] = loadPermissions($uid);
      $_SESSION['jwt'] = jwt_sign(['sub' => $uid], 600);
      $_SESSION['jwt_exp'] = time() + 600;

      header('Location: ' . redirectAfterLogin($next));
      exit;
    } else {
      $err = 'Credenziali errate.';
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Login - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="container narrow-container auth-layout">
      <section class="auth-showcase">
        <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
          <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
        </a>

        <div class="eyebrow mt-20">Bentornato</div>

        <?php if ($tenantShop): ?>
          <h2>Accedi per acquistare da <?= htmlspecialchars($tenantShop['nome']) ?>.</h2>
          <p>Effettua il login direttamente nello shop.</p>
        <?php else: ?>
          <h2>Accedi al tuo spazio Ricomoto.</h2>
        <?php endif; ?>
      </section>

      <section class="auth-panel">
        <div class="kicker">Login</div>
        <h2>Accedi al pannello</h2>
        <p>Inserisci le tue credenziali per continuare.</p>

        <?php if ($err): ?>
          <div class="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <div class="field">
            <label>Email o username</label>
            <input name="email" type="text" required>
          </div>

          <div class="field">
            <label>Password</label>
            <input name="password" type="password" required>
          </div>

          <div class="btn-row mt-16">
            <button class="btn btn-primary" type="submit">Entra</button>
            <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('register.php') . ($next ? '?next=' . urlencode($next) : '')) ?>">
              Registrati
            </a>
          </div>

          <div class="small mt-16">
            <?php if ($tenantShop): ?>
              Torna allo <a class="link" href="<?= htmlspecialchars(appUrl('shop.php')) ?>">shop</a>
            <?php else: ?>
              Torna alla <a class="link" href="<?= htmlspecialchars(appUrl('index.php')) ?>">home</a>
            <?php endif; ?>
          </div>
        </form>
      </section>
    </div>
  </div>
</body>
</html>