<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api/jwt.php';

$err = null;
$next = $_GET['next'] ?? $_POST['next'] ?? '';

$tenantShop = currentTenantShop();

function loginUserAfterRegister(int $userId): void {
  $_SESSION['user_id'] = $userId;
  $_SESSION['roles'] = loadUserRoles($userId);
  $_SESSION['permissions'] = loadPermissions($userId);
  $_SESSION['jwt'] = jwt_sign(['sub' => $userId], 600);
  $_SESSION['jwt_exp'] = time() + 600;
}

function redirectAfterRegister(string $next): string {
  if ($next !== '' && substr($next, 0, 1) === '/') {
    return $next;
  }

  if (currentTenantShop()) {
    return appUrl('shop.php');
  }

  return dashboardTarget();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $tipo = $_POST['tipo'] ?? 'cliente';

    if ($tipo === 'cliente') {
      $nome = trim($_POST['nome'] ?? '');
      $cognome = trim($_POST['cognome'] ?? '');
      $telefono = trim($_POST['telefono'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $indirizzo = trim($_POST['indirizzo'] ?? '');
      $password = $_POST['password'] ?? '';

      if ($nome === '' || $cognome === '' || $telefono === '' || $email === '' || $indirizzo === '' || $password === '') {
        throw new Exception('Compila tutti i campi richiesti.');
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);

      $conn->begin_transaction();

      $stmt = $conn->prepare('INSERT INTO utente(nome,cognome,telefono,email,indirizzo,password) VALUES (?,?,?,?,?,?)');
      $stmt->bind_param('ssssss', $nome, $cognome, $telefono, $email, $indirizzo, $hash);
      $stmt->execute();

      $userId = (int)$conn->insert_id;

      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO user_roles(user_id, role_id) SELECT ?, id FROM roles WHERE name='cliente'");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $stmt->close();

      $conn->commit();

      session_regenerate_id(true);
      loginUserAfterRegister($userId);

      header('Location: ' . redirectAfterRegister($next));
      exit;
    }

    if ($tipo === 'officina') {
      $nomeOfficina = trim($_POST['nome_officina'] ?? '');
      $iva = trim($_POST['iva'] ?? '');
      $indirizzoOff = trim($_POST['indirizzo_officina'] ?? '');
      $cellulare = trim($_POST['cellulare'] ?? '');
      $email = trim($_POST['email_officina'] ?? '');
      $password = $_POST['password_officina'] ?? '';

      if ($nomeOfficina === '' || $iva === '' || $indirizzoOff === '' || $cellulare === '' || $email === '' || $password === '') {
        throw new Exception('Compila tutti i campi richiesti.');
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);

      $conn->begin_transaction();

      $stmt = $conn->prepare('INSERT INTO utente(nome,cognome,telefono,email,indirizzo,password) VALUES (?,?,?,?,?,?)');

      $nomeFake = 'Officina';
      $cognomeFake = $nomeOfficina;

      $stmt->bind_param('ssssss', $nomeFake, $cognomeFake, $cellulare, $email, $indirizzoOff, $hash);
      $stmt->execute();

      $userId = (int)$conn->insert_id;

      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO user_roles(user_id, role_id) SELECT ?, id FROM roles WHERE name='officina'");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $stmt->close();

      $stmt = $conn->prepare('INSERT INTO officina(nome, iva, indirizzo, cellulare, utente_id) VALUES (?,?,?,?,?)');
      $stmt->bind_param('ssssi', $nomeOfficina, $iva, $indirizzoOff, $cellulare, $userId);
      $stmt->execute();
      $stmt->close();

      $conn->commit();

      session_regenerate_id(true);
      loginUserAfterRegister($userId);

      header('Location: ' . redirectAfterRegister($next));
      exit;
    }

    throw new Exception('Seleziona un tipo account valido.');
  } catch (Throwable $e) {
    try {
      $conn->rollback();
    } catch (Throwable $t) {}

    $err = 'Errore registrazione: ' . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Registrazione - Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="container wide-container auth-layout" style="grid-template-columns: .95fr 1.05fr;">
      <section class="auth-showcase">
        <a class="brand compact" href="<?= htmlspecialchars(appUrl('index.php')) ?>">
          <img src="<?= htmlspecialchars(assetUrl('assets/ricomoto-logo.svg')) ?>" alt="Ricomoto">
        </a>

        <div class="eyebrow mt-20">Crea il tuo account</div>

        <?php if ($tenantShop): ?>
          <h2>Registrati per acquistare da <?= htmlspecialchars($tenantShop['nome']) ?>.</h2>
          <p>Puoi creare un account cliente oppure officina direttamente da questo shop.</p>
        <?php else: ?>
          <h2>Un solo ingresso. Due esperienze diverse.</h2>
          <p>Cliente per acquistare. Officina per aprire il tuo shop, creare il tenant e gestire i prodotti.</p>
        <?php endif; ?>

        <div class="auth-points">
          <div class="auth-point">
            <div class="headline-sm">Cliente</div>
            <div class="small">Compra pezzi e accessori dal catalogo globale o direttamente dagli shop.</div>
          </div>

          <div class="auth-point">
            <div class="headline-sm">Officina</div>
            <div class="small">Crea shop, logo, località, storefront e catalogo prodotti dedicato.</div>
          </div>
        </div>
      </section>

      <section class="auth-panel">
        <div class="kicker">Registrazione</div>
        <h2>Scegli il tipo di account</h2>
        <p>Compila il form e inizia su Ricomoto.</p>

        <?php if ($err): ?>
          <div class="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

          <div class="field">
            <label>Tipo account</label>
            <select name="tipo" id="tipo" onchange="toggleForms()" required>
              <option value="cliente">Utente cliente</option>
              <option value="officina">Officina</option>
            </select>
          </div>

          <div id="formCliente">
            <div class="grid2">
              <div class="field">
                <label>Nome</label>
                <input name="nome" type="text" required>
              </div>

              <div class="field">
                <label>Cognome</label>
                <input name="cognome" type="text" required>
              </div>
            </div>

            <div class="grid2">
              <div class="field">
                <label>Email</label>
                <input name="email" type="email" required>
              </div>

              <div class="field">
                <label>Telefono</label>
                <input name="telefono" type="text" required>
              </div>
            </div>

            <div class="field">
              <label>Indirizzo</label>
              <input name="indirizzo" type="text" required>
            </div>

            <div class="field">
              <label>Password</label>
              <input name="password" type="password" required>
            </div>
          </div>

          <div id="formOfficina" style="display:none;">
            <div class="grid2">
              <div class="field">
                <label>Nome officina</label>
                <input name="nome_officina" type="text">
              </div>

              <div class="field">
                <label>Partita IVA</label>
                <input name="iva" type="text">
              </div>
            </div>

            <div class="field">
              <label>Indirizzo officina</label>
              <input name="indirizzo_officina" type="text">
            </div>

            <div class="grid2">
              <div class="field">
                <label>Cellulare</label>
                <input name="cellulare" type="text">
              </div>

              <div class="field">
                <label>Email login</label>
                <input name="email_officina" type="email">
              </div>
            </div>

            <div class="field">
              <label>Password</label>
              <input name="password_officina" type="password">
            </div>
          </div>

          <div class="btn-row mt-16">
            <button class="btn btn-primary" type="submit">Crea account</button>
            <a class="btn btn-ghost" href="<?= htmlspecialchars(appUrl('login.php') . ($next ? '?next=' . urlencode($next) : '')) ?>">
              Vai al login
            </a>
          </div>
        </form>
      </section>
    </div>
  </div>

<script>
function setRequired(containerId, enabled) {
  const el = document.getElementById(containerId);
  const inputs = el.querySelectorAll('input, select, textarea');

  inputs.forEach(function(input) {
    if (enabled) {
      input.setAttribute('required', 'required');
    } else {
      input.removeAttribute('required');
    }
  });
}

function toggleForms() {
  const tipo = document.getElementById('tipo').value;
  const showCliente = tipo === 'cliente';

  document.getElementById('formCliente').style.display = showCliente ? 'block' : 'none';
  document.getElementById('formOfficina').style.display = showCliente ? 'none' : 'block';

  setRequired('formCliente', showCliente);
  setRequired('formOfficina', !showCliente);
}

toggleForms();
</script>
</body>
</html>