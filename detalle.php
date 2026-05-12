<?php
require_once './db/conexion.php';
session_start();
if (!$_SESSION['loggedin']) {
  header('Location: ./login.php');
}

$pokemonId = $_GET["id"];

if (!$pokemonId) {
  header('Location: ./index.php');
}

$hasError = false;
$errorMessage = "";

$sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

$stmtPokemon = $sql->prepare("SELECT
    p.id,
    p.numero,
    p.nombre,
    p.sprite,
    p.descripcion,
    JSON_ARRAYAGG(
        JSON_OBJECT(
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

$sql->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <title>Document</title>
  <?php include './internal/dependencias.php' ?>
</head>

<body>
  <?php include './internal/header.php' ?>
  <main class="container align-content-center py-3">
    <div class="row">
      <div class="col">
        <div class="card mb-3">
          <div class="row g-0">
            <div class="col-md-auto align-content-center text-center">
              <img src="<?php echo $pokemon->sprite ?>" class="img-fluid rounded-start" alt="...">
            </div>
            <div class="col-md">
              <div class="card-body">
                <h5 class="card-title">#<?php echo $pokemon->numero ?> <?php echo $pokemon->nombre ?></h5>
                <div class="d-flex gap-1 mb-2">
                  <?php
                  foreach ($pokemon->tipos as $pkmn_tipo) {
                    echo "<img src=\"$pkmn_tipo->sprite\" alt=\"$pkmn_tipo->nombre\" title=\"$pkmn_tipo->nombre\" />";
                  }
                  ?>
                </div>
                <p class="card-text"><?php echo $pokemon->descripcion ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>

</html>