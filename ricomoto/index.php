<?php
require_once __DIR__ . "/auth.php";
if (isLoggedIn()) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Ricomoto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h1>Ricomoto</h1>
    <p>Accedi o crea un account</p>

    <div class="btn-row">
      <a class="btn btn-blue" href="login.php">Login</a>
      <a class="btn btn-white" href="register.php">Registrazione</a>
    </div>
  </div>
</body>
</html>
