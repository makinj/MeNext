<?php
  $restricted=true;//only show if logged in
  $title="submit";
  require_once('header.php');//echo top of html
?>
<h3>list of submitted videos: </h3>

<ul id="videoList"></ul>
<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="/js/watch.js"></script>
<?php
  require_once('footer.php');//close up html
?>
