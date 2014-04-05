<?php
  $restricted=true;//only show if logged in
  $title="watch";
  require_once('header.php');//echo top of html
  require_once("class.DB.php");
?>
<h3>List of Submitted Videos: </h3>

<?php
  $db = new DB();
  $db->listSongs(1);
?>

<?php
  require_once('footer.php');//close up html
?>
