<?php
  if (isset($_GET['search'])){
    require_once("includes/ytfunctions.php");
    searchAndPrint($_GET['search']);
  }
    //echo "test";
?>