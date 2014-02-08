<?php
  require("class.DB.php");
  $db = new DB();
  $db->createAccount("Joshua", "dopeness");
  $db->signIn("Joshua", "dopeness");
  echo "<h1>home</h1>";
  echo "<a href='reset.php'>reset</a>";
?>