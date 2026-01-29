<?php
require_once __DIR__ . "/auth.php";

$err = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    $tipo = $_POST["tipo"] ?? "";

    if ($tipo === "cliente") {
      $nome = trim($_POST["nome"] ?? "");
      $cognome = trim($_POST["cognome"] ?? "");
      $email = trim($_POST["email"] ?? "");
      $telefono = trim($_POST["telefono"] ?? "");
      $indirizzo = trim($_POST["indirizzo"] ?? "");
      $password = $_POST["password"] ?? "";

      if ($nome === "" || $cognome === "" || $email === "" || $telefono === "" || $indirizzo === "" || $password === "") {
        throw new Exception("Compila tutti i campi richiesti (cliente).");
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);

      $conn->begin_transaction();

      $stmt = $conn->prepare("INSERT INTO utente(nome,cognome,telefono,email,indirizzo,password) VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("ssssss", $nome, $cognome, $telefono, $email, $indirizzo, $hash);
      $stmt->execute();
      $userId = $conn->insert_id;

      $stmt = $conn->prepare("INSERT INTO user_roles(user_id, role_id) SELECT ?, id FROM roles WHERE name='cliente'");
      $stmt->bind_param("i", $userId);
      $stmt->execute();

      $conn->commit();

      header("Location: login.php?registered=1");
      exit;
    }

    if ($tipo === "officina") {
      $nomeOfficina = trim($_POST["nome_officina"] ?? "");
      $iva = trim($_POST["iva"] ?? "");
      $indirizzoOff = trim($_POST["indirizzo_officina"] ?? "");
      $cellulare = trim($_POST["cellulare"] ?? "");
      $email = trim($_POST["email_officina"] ?? "");
      $password = $_POST["password_officina"] ?? "";

      if ($nomeOfficina === "" || $iva === "" || $indirizzoOff === "" || $cellulare === "" || $email === "" || $password === "") {
        throw new Exception("Compila tutti i campi richiesti (officina).");
      }

      $hash = password_hash($password, PASSWORD_DEFAULT);

      $conn->begin_transaction();

      $nomeFake = "Officina";
      $cognomeFake = $nomeOfficina;

      $stmt = $conn->prepare("INSERT INTO utente(nome,cognome,telefono,email,indirizzo,password) VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("ssssss", $nomeFake, $cognomeFake, $cellulare, $email, $indirizzoOff, $hash);
      $stmt->execute();
      $userId = $conn->insert_id;

      $stmt = $conn->prepare("INSERT INTO user_roles(user_id, role_id) SELECT ?, id FROM roles WHERE name='officina'");
      $stmt->bind_param("i", $userId);
      $stmt->execute();

      $stmt = $conn->prepare("INSERT INTO officina(nome, iva, indirizzo, cellulare, utente_id) VALUES (?,?,?,?,?)");
      $stmt->bind_param("ssssi", $nomeOfficina, $iva, $indirizzoOff, $cellulare, $userId);
      $stmt->execute();

      $conn->commit();

      header("Location: login.php?registered=1");
      exit;
    }

    throw new Exception("Seleziona un tipo account valido (cliente/officina).");

  } catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    $err = "Errore registrazione: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Registrazione</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h1>Registrazione</h1>
    <p>Scegli il tipo di account</p>

    <div class="card">
      <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Tipo account</label>
          <select name="tipo" id="tipo" onchange="toggleForms()" required>
            <option value="cliente">Utente (cliente)</option>
            <option value="officina">Officina</option>
          </select>
        </div>

        <!-- FORM CLIENTE -->
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
              <label>Numero telefono</label>
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

        <!-- FORM OFFICINA -->
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
              <label>Email (per login)</label>
              <input name="email_officina" type="email">
            </div>
          </div>

          <div class="field">
            <label>Password (per login)</label>
            <input name="password_officina" type="password">
          </div>
        </div>

        <div class="btn-row" style="margin-top:14px;">
          <button class="btn btn-white" type="submit">Crea account</button>
          <a class="btn btn-blue" href="login.php">Vai al login</a>
        </div>

        <div class="small">
          Torna alla <a class="link" href="index.php">home</a>
        </div>
      </form>
    </div>
  </div>

<script>
function setRequired(containerId, enabled){
  const el = document.getElementById(containerId);
  const inputs = el.querySelectorAll("input, select, textarea");
  inputs.forEach(i => {
    if (enabled) i.setAttribute("required","required");
    else i.removeAttribute("required");
  });
}

function toggleForms(){
  const tipo = document.getElementById("tipo").value;
  const showCliente = (tipo === "cliente");

  document.getElementById("formCliente").style.display = showCliente ? "block" : "none";
  document.getElementById("formOfficina").style.display = showCliente ? "none" : "block";

  setRequired("formCliente", showCliente);
  setRequired("formOfficina", !showCliente);
}
toggleForms();
</script>
</body>
</html>
