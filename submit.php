<?php
  $restricted=true;
  $title="submissions";
  require_once('header.php');
?>

<form action="" name="contact" onsubmit="return searchVideo();">
    <input type="text" name="search" id="search"/>
    <input type="submit" name="submit" value="go!"/>
</form>
<script type="text/javascript" src="/js/ytsearch.js"></script>
<ul id="list"></ul>
<?php
  require_once('footer.php');
?>