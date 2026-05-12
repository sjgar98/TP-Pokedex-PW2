<?php
require_once './db/conexion.php';

session_start();
if ($_SESSION['loggedin']) {
  header('Location: ./index.php');
}

$hasError = false;
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

  $loginUsername = $_POST["username"];
  $loginPassword = $_POST["password"];

  $stmtUsuarioExistente = $sql->prepare("SELECT * FROM usuarios WHERE username = ?;");
  $stmtUsuarioExistente->bind_param("s", $loginUsername);
  $stmtUsuarioExistente->execute();
  $stmtUsuarioExistenteResult = $stmtUsuarioExistente->get_result();
  $usuarioExistente = $stmtUsuarioExistenteResult->fetch_object();

  if ($usuarioExistente) {
    $passwordMatches = password_verify($loginPassword, $usuarioExistente->password);
    if ($passwordMatches) {
      $_SESSION['loggedin'] = true;
      $_SESSION['username'] = $usuarioExistente->username;
      $_SESSION['is_admin'] = (bool)$usuarioExistente->is_admin;
      header('Location: ./index.php');
    } else {
      $hasError = true;
      $errorMessage .= "<div>El usuario o la contraseña son incorrectos.</div>";
    }
  } else {
    $hasError = true;
    $errorMessage .= "<div>El usuario o la contraseña son incorrectos.</div>";
  }

  $sql->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>Login - Pokédex</title>
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
              <form action="./login.php" method="post">
                <div class="mb-3">
                  <label for="username" class="form-label">Usuario</label>
                  <input type="text" class="form-control" id="username" name="username">
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <input type="password" class="form-control" id="password" name="password">
                </div>
                <button type="submit" class="btn btn-success">Login</button>
                <a class="btn btn-secondary" href="./signup.php">Registro</a>
                <?php
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