<?php
require_once './conexion.php';
$sql = mysqli_connect($dbHost, $dbUser, $dbPass);

if ($sql === false) {
  die("<div>Fallo en conectar a base de datos. " . mysqli_connect_error()) . "</div>";
}

if ($dbFreshStart) {
  $sql->query("DROP DATABASE IF EXISTS $dbName;");
  echo "<div>Base de datos \"$dbName\" eliminada";
}

if (!$sql->query("CREATE DATABASE IF NOT EXISTS $dbName;")) {
  die("<div>Fallo en crear la base de datos \"$dbName\". " . mysqli_error($sql)) . "</div>";
}

$sql->select_db("$dbName");
echo "<div>Seleccionada base de datos \"$dbName\"</div>";

$sql->begin_transaction();
try {
  $sql->query("CREATE TABLE IF NOT EXISTS migraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migracion VARCHAR(255),
    creada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );");
  $sql->commit();
} catch (mysqli_sql_exception $exception) {
  $sql->rollback();
  die("<div>" . $exception . "</div>");
}
echo "<div>Tabla de migraciones existe</div>";

$migracionesArchivos = array_diff(scandir(__DIR__ . '/migraciones'), array('..', '.'));

foreach ($migracionesArchivos as $migracionArchivo) {
  $migracion = $sql->query("SELECT * FROM migraciones WHERE migracion = '$migracionArchivo';")->fetch_object();
  if ($migracion) {
    echo "<div>La migración " . $migracion->migracion . " ya existe</div>";
  } else {
    $migracionQuery = file_get_contents(__DIR__ . '/migraciones/' . $migracionArchivo);
    $sql->begin_transaction();
    try {
      $sql->multi_query($migracionQuery);
      while ($sql->more_results()) {
        $sql->next_result();
      }
      $sql->query("INSERT INTO migraciones (migracion) VALUES (\"$migracionArchivo\");");
      $sql->commit();
    } catch (mysqli_sql_exception $exception) {
      $sql->rollback();
      die("<div>" . $exception . "</div>");
    }
    echo "<div>Se ejecutó la migración " . $migracionArchivo . "</div>";
  }
}

$sql->close();
