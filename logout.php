<?php
  /*
  Joshua Makinen
  forget current session data and log out
  */
  if(session_id() == '') {
    session_start();
  }
  session_destroy();//leave no trace
  header("Location: login.php");//login again
  exit;
?>