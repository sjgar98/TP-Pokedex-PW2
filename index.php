<?php
require_once './db/conexion.php';
session_start();
if (!$_SESSION['loggedin']) {
    header('Location: ./login.php');
}

$queryTerm = $_GET["query"];
$queryTermLike = "";
$resultados = [];

$sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

$stmtBusquedaQuery = "SELECT
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
" . ($queryTerm ? "WHERE LOWER(p.nombre) LIKE ?" : "") . "
GROUP BY p.id;
";
$stmtBusqueda = $sql->prepare($stmtBusquedaQuery);
if ($queryTerm) {
    $queryTermLike = "%" . strtolower($queryTerm) . "%";
    $stmtBusqueda->bind_param("s", $queryTermLike);
}
$stmtBusqueda->execute();
$stmtBusquedaResult = $stmtBusqueda->get_result();

$resultados = [];
while ($resultado = $stmtBusquedaResult->fetch_object()) {
    $resultado->tipos = json_decode($resultado->tipos);
    array_push($resultados, $resultado);
}
$sql->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Pokedex</title>
    <?php include './internal/dependencias.php' ?>
</head>

<body>
    <?php include './internal/header.php' ?>
    <main class="container py-3">
        <div class="row">
            <div class="col">
                <form action="./index.php" method="get">
                    <label class="visually-hidden" for="inlineFormInputGroupUsername">Username</label>
                    <div class="input-group">
                        <div class="input-group-text">
                            <i class="material-symbols-outlined">search</i>
                        </div>
                        <input type="text" class="form-control" id="query" name="query"
                            <?php if ($queryTerm) echo "value=\"$queryTerm\""; ?>>
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <table class="table my-3 d-none d-sm-table">
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">Tipo</th>
                            <th scope="col">#</th>
                            <th scope="col">Nombre</th>
                            <?php if ($_SESSION['is_admin']) echo '<th scope="col">Acciones</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($resultados as $pokemon) {
                            echo "
                        <tr>
                            <td class=\"align-middle\">
                                <img src=\"$pokemon->sprite\" alt=\"$pokemon->nombre\" title=\"$pokemon->nombre\">
                            </td>
                            <td class=\"align-middle\">";
                            foreach ($pokemon->tipos as $pkmn_tipo) {
                                echo "<img src=\"$pkmn_tipo->sprite\" alt=\"$pkmn_tipo->nombre\" title=\"$pkmn_tipo->nombre\" />";
                            }
                            echo "  </td>
                            <td class=\"align-middle\">$pokemon->numero</td>
                            <td class=\"align-middle\">$pokemon->nombre</td>" . (
                                $_SESSION['is_admin'] ? '
                            <td class="align-middle">
                                <div class="d-flex gap-2">
                                    <a class="btn btn-warning d-flex width-max-content" type="button" href="./editar.php?id=' . $pokemon->id . '">
                                        <i class="material-symbols-outlined">edit</i>
                                        <span>Editar</span>
                                    </a>
                                    <a class="btn btn-danger d-flex width-max-content" type="button" href="./eliminar.php?id=' . $pokemon->id . '">
                                        <i class="material-symbols-outlined">delete</i>
                                        <span>Eliminar</span>
                                    </a>
                                </div>
                            </td>' : ''
                            ) . "
                        </tr>
                        ";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="container d-block d-sm-none my-3">
                    <?php
                    foreach ($resultados as $pokemon) {
                        echo "
                        <div class=\"row\">
                            <div class=\"col\">
                                <div class=\"card mb-3\" style=\"max-width: 540px;\">
                                    <div class=\"row g-0\">
                                        <div class=\"col-4 align-content-center\">
                                            <img src=\"$pokemon->sprite\" class=\"img-fluid rounded-start\" alt=\"...\">
                                        </div>
                                        <div class=\"col-8\">
                                            <div class=\"card-body\">
                                                <h5 class=\"card-title d-flex align-items-center gap-2\">
                                                    <span>#$pokemon->numero $pokemon->nombre</span>
                                                </h5>
                                                <div class=\"d-flex gap-1 mb-2\">";
                        foreach ($pokemon->tipos as $pkmn_tipo) {
                            echo "<img src=\"$pkmn_tipo->sprite\" alt=\"$pkmn_tipo->nombre\" title=\"$pkmn_tipo->nombre\" />";
                        }
                        echo "                  </div>" . ($_SESSION['is_admin'] ? '
                                                <div class="d-flex gap-2">
                                                    <a class="btn btn-warning d-flex width-max-content" type="button" href="./editar.php?id=' . $pokemon->id . '">
                                                        <i class="material-symbols-outlined">edit</i>
                                                    </a>
                                                    <a class="btn btn-danger d-flex width-max-content" type="button" href="./eliminar.php?id=' . $pokemon->id . '">
                                                        <i class="material-symbols-outlined">delete</i>
                                                    </a>
                                                </div>
                            ' : '') . "     </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ";
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        if ($_SESSION['is_admin']) {
            echo "
            <div class=\"row\">
                <div class=\"col\">
                    <a class=\"btn btn-success w-100\" href=\"./nuevo.php\">Nuevo Pokémon</a>
                </div>
            </div>
            ";
        }
        ?>
    </main>
</body>

</html>