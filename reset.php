<?php
  require("includes/constants.php");
  $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS);
  $db->exec("DROP DATABASE $DB_NAME");
  header("location: index.php")
?>