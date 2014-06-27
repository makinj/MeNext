<?php
  /*
  Joshua Makinen
  resets database
  */
  require_once("includes/functions.php");
  $db = connectDb();
  $db->exec("DROP DATABASE ".DB_NAME);//destroy table!!!
  if(session_id() == '') {
    session_start();
  }
  session_destroy();//destroy session data!!!
  header("location: /")//Go home!!!

?>