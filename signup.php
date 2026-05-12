<?php
require_once './db/conexion.php';

session_start();
if ($_SESSION['loggedin']) {
  header('Location: ./index.php');
}

$hasError = false;
$errorMessage = "";

$isSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

  $newUsername = $_POST["username"];
  $newPassword = $_POST["password"];
  $newPasswordRepeat = $_POST["repeatPassword"];

  $stmtUsuarioExistente = $sql->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE username = ?;");
  $stmtUsuarioExistente->bind_param("s", $newUsername);
  $stmtUsuarioExistente->execute();
  $stmtUsuarioExistenteResult = $stmtUsuarioExistente->get_result();
  $usuarioExistente = $stmtUsuarioExistenteResult->fetch_object()->total > 0;

  if (strlen($newUsername) < 4) {
    $hasError = true;
    $errorMessage .= "El usuario debe tener al menos 4 caracteres.<br/>";
  }
  if ($usuarioExistente) {
    $hasError = true;
    $errorMessage .= "El usuario ya existe.<br/>";
  }
  if (strlen($newPassword) < 8) {
    $hasError = true;
    $errorMessage .= "La contraseña debe tener al menos 8 caracteres.<br/>";
  }
  if ($newPassword !== $newPasswordRepeat) {
    $hasError = true;
    $errorMessage .= "Las contraseñas no coinciden.<br/>";
  }

  if (!$hasError) {
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $existingUsers = $sql->query("SELECT COUNT(*) as total FROM usuarios")->fetch_object()->total;
    $isFirstUser = $existingUsers == 0;
    $newIsAdmin = $isFirstUser ? 1 : 0;

    $sql->begin_transaction();
    try {
      $stmt = $sql->prepare("INSERT INTO usuarios(username, password, is_admin) VALUES (?, ?, ?);");
      $stmt->bind_param("ssi", $newUsername, $newPasswordHash, $newIsAdmin);
      $stmt->execute();
      $sql->commit();
      $isSuccess = true;
    } catch (mysqli_sql_exception $exception) {
      $sql->rollback();
      $hasError = true;
      $errorMessage .= "Ocurrió un error inesperado.<br/>";
      $errorMessage .= "$exception";
    }
  }
  $sql->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>Registro - Pokédex</title>
  <?php include './internal/dependencias.php' ?>
</head>

<body>
  <?php include './internal/header.php' ?>
  <main class="d-flex align-items-center justify-content-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col col-sm-6">
          <div class="card">
            <div class="card-body">
              <form action="./signup.php" method="post">
                <div class="mb-3">
                  <label for="username" class="form-label">Usuario</label>
                  <input type="text" class="form-control" id="username" name="username">
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="mb-3">
                  <label for="repeatPassword" class="form-label">Repetir Contraseña</label>
                  <input type="password" class="form-control" id="repeatPassword" name="repeatPassword">
                </div>
                <button type="submit" class="btn btn-success">Registrarse</button>
                <?php
                if ($isSuccess) {
                  echo "<div class=\"alert alert-success mt-3\" role=\"alert\">Registro existoso</div>";
                }
                if ($hasError) {
                  echo "<div class=\"alert alert-danger mt-3\" role=\"alert\">$errorMessage</div>";
                }
                ?>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>

</html>