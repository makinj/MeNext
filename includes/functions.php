<?php
  function sanitizeString($var){
    $var = strip_tags($var);
    $var = htmlentities($var);
    return stripslashes($var);
  }
?>