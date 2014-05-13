
<?php
  $restricted=true;//only show if logged in
  $title="submit";
  require_once('header.php');//echo top of html
?>

<ul id="searchResults"></ul>


<script type="text/javascript">
  var API_KEY =
  <?php 
    require_once("includes/constants.php");
    echo"'".$API_CLIENT_KEY."';\n";
    if (isset($_GET['q'])){
      echo "$('#searchText').val('".$_GET['q']."');\n";
    }
  ?>
  searchYouTube();
</script>
<script type="text/javascript" src="/js/common.js"></script>
<?php
  require_once('footer.php');//close up html
?>