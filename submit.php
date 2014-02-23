<?php
  $restricted=true;
  require_once('header.php');
?>
<script type="text/javascript" src="ytsearch.js"></script>

<form action="" name="contact" onsubmit="return searchVideo();">
    <input type="text" name="search" id="search"/>
    <input type="submit" name="submit" value="go!"/>
</form>
<ul id="list"></ul>