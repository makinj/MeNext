<?php
  /*
  Joshua Makinen
  last updated 2/22/2014
  */
  if (isset($_GET['search'])){
    require_once("includes/ytfunctions.php");//youtube related functions
    searchAndPrint($_GET['search']);//search whatever was sent to GET
  }
?>