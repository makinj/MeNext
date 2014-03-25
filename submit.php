<?php
  $restricted=true;//only show if logged in
  $title="submit";
  require_once('header.php');//echo top of html
?>

<form action="" name="contact" onsubmit="return searchVideo();">
    <input type="text" name="search" id="search"/>
    <input type="submit" name="submit" value="go!"/>
</form>
<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript">
  var API_KEY =
  <?php 
    require_once("includes/constants.php");
    echo"'".$API_CLIENT_KEY."'";
  ?>;
</script>
<script type="text/javascript" src="/js/ytsearch.js"></script>
<ul id="searchResults"></ul>
<?php
  require_once('footer.php');//close up html
?>
