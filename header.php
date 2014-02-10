<?php
  session_start();
  echo "<a href='/''>home</a>";
  if(isset($_SESSION['logged'])){
    echo" | You are logged in as ".$_SESSION['username']." | <a href='/submit.php'>submit</a> | <a href='/logout.php'>log out</a>";
  }else{
    echo " | <a href='/login.php'>login/register</a>";
  }
  echo "<br>";
?>