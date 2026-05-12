<?php
require_once './db/conexion.php';

session_start();
if (!$_SESSION['loggedin']) {
  header('Location: ./login.php');
}
if (!$_SESSION['is_admin']) {
  header('Location: ./index.php');
}

$pokemonId = $_GET["id"];
if (!$pokemonId) {
  header('Location: ./index.php');
}

$hasError = false;
$errorMessage = "";
$isSuccess = false;

$sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

$stmtPokemon = $sql->prepare("SELECT
    p.id,
    p.numero,
    p.nombre,
    p.sprite,
    p.descripcion,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'id', t.id,
            'nombre', t.nombre,
            'sprite', t.sprite
        )
    ) as tipos
FROM pokemon p
LEFT JOIN pokemon_tipos pt ON p.id = pt.pokemon
LEFT JOIN tipos t ON t.id = pt.tipo
WHERE p.id = ?
GROUP BY p.id;
");
$stmtPokemon->bind_param("i", $pokemonId);
$stmtPokemon->execute();
$stmtPokemonResult = $stmtPokemon->get_result();
$pokemon = $stmtPokemonResult->fetch_object();

if ($pokemon) {
  $pokemon->tipos = json_decode($pokemon->tipos);
} else {
  header('Location: ./index.php');
}

$tipos = [];
$tiposResultado = $sql->query("SELECT * FROM tipos;");
while ($resultado = $tiposResultado->fetch_object()) {
  array_push($tipos, $resultado);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $editNombre = $_POST["nombre"];
  $editNumero = $_POST["numero"];
  $editDescripcion = $_POST["descripcion"];
  $editTipos = $_POST["tipos"];
  $editSprite = $_FILES["sprite"];

  $stmtDataInUse = $sql->prepare("SELECT * FROM pokemon WHERE (numero = ? OR nombre = ?) AND id != ?;");
  $stmtDataInUse->bind_param("isi", $editNumero, $editNombre, $pokemon->id);
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
      $stmtPokemonUpdate = $sql->prepare("UPDATE pokemon SET numero = ?, nombre = ?, descripcion = ? WHERE id = ?;");
      $stmtPokemonUpdate->bind_param("issi", $editNumero, $editNombre, $editDescripcion, $pokemon->id);
      $stmtPokemonUpdate->execute();

      if ($editSprite['tmp_name']) {
        move_uploaded_file($editSprite['tmp_name'], __DIR__ . "/assets/img/pokemon/$pokemon->id.png");
      }

      $stmtPokemonTiposDelete = $sql->prepare("DELETE FROM pokemon_tipos WHERE pokemon = ?;");
      $stmtPokemonTiposDelete->bind_param("i", $pokemon->id);
      $stmtPokemonTiposDelete->execute();

      if ($editTipos && count($editTipos) > 0) {
        $stmtPokemonTiposInsertQuery = "INSERT INTO pokemon_tipos (pokemon, tipo) VALUES";
        $stmtPokemonTiposInsertList = [];
        foreach ($editTipos as $editTipo) {
          array_push($stmtPokemonTiposInsertList, "($pokemon->id, " . intval($editTipo) . ")");
        }
        $stmtPokemonTiposInsertQuery .= implode(",\n", $stmtPokemonTiposInsertList) . ";";
        $stmtPokemonTiposInsert = $sql->query($stmtPokemonTiposInsertQuery);
      }

      $sql->commit();
      header('Location: ./index.php');
      $isSuccess = true;
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
              <form action="./editar.php?id=<?php echo $pokemon->id ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $pokemonId; ?>" />
                <div class="row">
                  <div class="col-12 col-sm-6">
                    <div class="row mb-3">
                      <div class="col">
                        <label for="numero" class="form-label">Numero</label>
                        <input type="number" class="form-control" id="numero" name="numero" value="<?php echo $pokemon->numero ?>" required>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $pokemon->nombre ?>" required>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="sprite" class="form-label">Sprite</label>
                        <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                        <input type="file" class="form-control" id="sprite" name="sprite">
                        <img src="<?php echo $pokemon->sprite ?>" alt="<?php echo $pokemon->nombre ?>" title="<?php echo $pokemon->nombre ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion"><?php echo $pokemon->descripcion ?></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="row mb-3">
                      <div class="col">
                        <label for="tipos" class="form-label">Tipos</label>
                        <?php
                        foreach ($tipos as $tipo) {
                          $tipoSeleccionado = false;
                          foreach ($pokemon->tipos as $tipoExistente) {
                            if ($tipo->id == $tipoExistente->id) {
                              $tipoSeleccionado = true;
                            }
                          }
                          echo '
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tipos[]" value="' . $tipo->id . '" id="tipo-' . $tipo->id . '" ' . ($tipoSeleccionado ? 'checked' : '') . '>
                            <label class="form-check-label" for="tipo-' . $tipo->id . '">
                              <img src="' . $tipo->sprite . '" />
                              <span>' . $tipo->nombre . '</span>
                            </label>
                          </div>
                          ';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-success">Editar Pokémon</button>
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