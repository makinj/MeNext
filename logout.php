<?php
  /*
  Joshua Makinen
  forget current session data and log out
  */
  require_once("includes/functions.php");
  logOut();
  header("Location: login.php");//login again
  exit;
?>