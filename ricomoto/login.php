<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/api/jwt.php";

$err = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $password = $_POST["password"] ?? "";

  if ($email === "" || $password === "") {
    $err = "Compila email e password.";
  } else {
    $stmt = $conn->prepare("SELECT ID, password FROM utente WHERE email = ?");
    $stmt->bindParam(1, $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
      $uid = (int)$user["ID"];

      // sessione utente
      $_SESSION["user_id"] = $uid;

      // carica ruoli/permessi in sessione (funzioni in auth.php)
      $_SESSION["roles"] = loadUserRoles($uid);
      $_SESSION["permissions"] = loadPermissions($uid);

      // carica tenancy_id per officina
      $_SESSION["tenancy_id"] = loadUserTenancy($uid);

      // genera JWT (10 minuti) e salva in sessione
      $_SESSION["jwt"] = jwt_sign(["sub" => $uid], 600);
      $_SESSION["jwt_exp"] = time() + 600;

      // Reindirizza admin_tenancy alla dashboard admin
      if (isAdminTenancy()) {
        header("Location: admin_tenancy.php");
        exit;
      }

      header("Location: dashboard.php");
      exit;
    } else {
      $err = "Credenziali errate.";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h1>Login</h1>
    <p>Accedi al tuo account</p>

    <div class="card">
      <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Email</label>
          <input name="email" type="email" required>
        </div>

        <div class="field">
          <label>Password</label>
          <input name="password" type="password" required>
        </div>

        <div class="btn-row" style="margin-top:14px;">
          <button class="btn btn-blue" type="submit">Login</button>
          <a class="btn btn-white" href="register.php">Registrazione</a>
        </div>

        <div class="small">
          Torna alla <a class="link" href="index.php">home</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
