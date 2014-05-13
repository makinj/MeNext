
<?php
  $restricted=true;//only show if logged in
  $title="submit";
  require_once('header.php');//echo top of html
?>
<h3>Search for videos here: 
  <form id="searchForm">
    <input type="text" name="searchText" id="searchText"/>
    <input type="submit"/>
  </form>
</h3>
<ul id="searchResults"></ul>


<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js" type="text/javascript"></script>
<script src="/js/jquery.googleSuggest.js" type="text/javascript"></script>



<script type="text/javascript">
  var API_KEY =
  <?php 
    require_once("includes/constants.php");
    echo"'".$API_CLIENT_KEY."'";
  ?>;
  $("#searchText").googleSuggest({ service: "youtube" });

</script>
<script type="text/javascript" src="/js/submit.js"></script>
<?php
  require_once('footer.php');//close up html
?>