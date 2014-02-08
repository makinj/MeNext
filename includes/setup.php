<?php
  require_once("constants.php");
  require_once("../class.DB.php");
  try {
    $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
  } catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage()."<br>";
    exit;
  }
  try {
    $db->exec("CREATE DATABASE IF NOT EXISTS".DB_NAME.";");
    echo "created database: ".DB_NAME."<br>";
  } catch (PDOException $e) {
    echo 'Database $dbname was unsuccessful: ' . $e->getMessage()."<br>";
    exit;
  }
  $db = new DB();

  $db->createTable($db, 'USER', 'user VARCHAR(16), pass VARCHAR(16), admin BIT(1),  INDEX(user(6))');
?>