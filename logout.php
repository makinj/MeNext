<?php
  /*
  Joshua Makinen
  forget current session data and log out
  */
  if(session_id() == '') {
    session_start();
  }
  session_destroy();
  header("Location: login.php");
  exit;
?>