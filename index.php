<?php
  $title="index";
  require_once('header.php');//bar at the top of the page
  require_once("class.DB.php");//basic database operations
  $db = new DB();//connect to mysql
  echo "<h1>home</h1>";
  echo "<a href='reset.php'>reset</a>";//deletes database and replaces it with new one
  require_once('footer.php');//bar at the top of the page
?>