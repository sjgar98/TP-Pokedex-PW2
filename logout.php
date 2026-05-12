<?php
session_start();
$_SESSION['loggedin'] = false;
$_SESSION['username'] = "";
$_SESSION['is_admin'] = false;
header('Location: ./login.php');
