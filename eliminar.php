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

$sql = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

$sql->begin_transaction();
try {
  $stmtPokemon = $sql->prepare("SELECT * FROM pokemon WHERE id = ?");
  $stmtPokemon->bind_param("i", $pokemonId);
  $stmtPokemon->execute();
  $stmtPokemonResult = $stmtPokemon->get_result();
  $pokemon = $stmtPokemonResult->fetch_object();
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
$sql->close();
