<?php
  /*
  Joshua Makinen
  common functions required by many
  */
  function sanitizeString($var){//cleans a string up so there are no crazy vulerabilities
    $var = strip_tags($var);
    $var = htmlentities($var);
    return stripslashes($var);
  }

?>