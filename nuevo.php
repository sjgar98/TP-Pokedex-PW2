<?php
require_once './db/conexion.php';

session_start();
if (!$_SESSION['loggedin']) {
  header('Location: ./login.php');
}
if (!$_SESSION['is_admin']) {
  header('Location: ./index.php');
}

$hasError = false;
$errorMessage = "";
$sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

$tipos = [];
$tiposResultado = $sql->query("SELECT * FROM tipos;");
while ($resultado = $tiposResultado->fetch_object()) {
  array_push($tipos, $resultado);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newNombre = $_POST["nombre"];
  $newNumero = $_POST["numero"];
  $newDescripcion = $_POST["descripcion"];
  $newTipos = $_POST["tipos"];
  $newSprite = $_FILES["sprite"];

  $stmtDataInUse = $sql->prepare("SELECT * FROM pokemon WHERE numero = ? OR nombre = ?;");
  $stmtDataInUse->bind_param("is", $newNumero, $newNombre);
  $stmtDataInUse->execute();
  $stmtDataInUseResult = $stmtDataInUse->get_result();
  $pokemonExistente = $stmtDataInUseResult->fetch_object();
  if ($pokemonExistente) {
    $hasError = true;
    $errorMessage .= "<div>El número o nombre del pokémon ya están en uso.</div>";
  }

  if (!$hasError) {
    $sql->begin_transaction();
    try {
      $stmtPokemonInsert = $sql->prepare("INSERT INTO pokemon (numero, nombre, descripcion, sprite) VALUES (?, ?, ?, \"\");");
      $stmtPokemonInsert->bind_param("iss", $newNumero, $newNombre, $newDescripcion);
      $stmtPokemonInsert->execute();

      $newPokemonId = $sql->insert_id;
      $pokemon = $sql->query("SELECT * FROM pokemon WHERE id = $newPokemonId;")->fetch_object();

      $newSpritePath = "./assets/img/pokemon/$newPokemonId.png";
      move_uploaded_file($newSprite['tmp_name'], __DIR__ . '/' . $newSpritePath);

      $stmtPokemonUpdate = $sql->prepare("UPDATE pokemon SET sprite = ? WHERE id = ?");
      $stmtPokemonUpdate->bind_param("si", $newSpritePath, $newPokemonId);
      $stmtPokemonUpdate->execute();

      $stmtPokemonTiposInsertQuery = "INSERT INTO pokemon_tipos (pokemon, tipo) VALUES";
      $stmtPokemonTiposInsertList = [];
      foreach ($newTipos as $newTipo) {
        array_push($stmtPokemonTiposInsertList, "($newPokemonId, " . intval($newTipo) . ")");
      }
      $stmtPokemonTiposInsertQuery .= implode(",\n", $stmtPokemonTiposInsertList) . ";";
      $stmtPokemonTiposInsert = $sql->query($stmtPokemonTiposInsertQuery);

      if ($pokemon) {
        $stmtDelete = $sql->prepare("DELETE FROM pokemon WHERE id = ?");
        $stmtDelete->bind_param("i", $pokemonId);
        $stmtDelete->execute();
        unlink($pokemon->sprite);
      }
      $sql->commit();
      header('Location: ./index.php');
    } catch (mysqli_sql_exception $exception) {
      $sql->rollback();
      $hasError = true;
      $errorMessage .= "Ocurrió un error inesperado.<br/>";
      $errorMessage .= "$exception";
    }
  }
}

$sql->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>Nuevo Pokémon - Pokédex</title>
  <?php include './internal/dependencias.php' ?>
</head>

<body>
  <?php include './internal/header.php' ?>
  <main class="d-flex align-items-center justify-content-center my-3">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col col-sm-6">
          <div class="card">
            <div class="card-body">
              <form action="./nuevo.php" method="post" enctype="multipart/form-data">
                <div class="row">
                  <div class="col-12 col-sm-6">
                    <div class="row mb-3">
                      <div class="col">
                        <label for="numero" class="form-label">Numero</label>
                        <input type="number" class="form-control" id="numero" name="numero" required>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="sprite" class="form-label">Sprite</label>
                        <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                        <input type="file" class="form-control" id="sprite" name="sprite" required>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="row mb-3">
                      <div class="col">
                        <label for="tipos" class="form-label">Tipos</label>
                        <?php
                        foreach ($tipos as $tipo) {
                          echo '
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tipos[]" value="' . $tipo->id . '" id="tipo-' . $tipo->id . '">
                            <label class="form-check-label" for="tipo-' . $tipo->id . '">
                              ' . $tipo->nombre . '
                            </label>
                          </div>
                          ';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-success">Nuevo Pokémon</button>
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