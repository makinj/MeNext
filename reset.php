<?php
  /*
  Joshua Makinen
  resets database
  */
  require("includes/constants.php");
  $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS);
  $db->exec("DROP DATABASE $DB_NAME");//destroy table!!!
  if(session_id() == '') {
    session_start();
  }
  session_destroy();//destroy session data!!!
  header("location: /")//Go home!!!

?>