<?php
  require_once("class.DB.php");//database funcitonality 
  $title="login";//to be displayed in tab
  include("header.php");//open html bar
?>

<h1>login</h1>
<div id="problem"></div>
<form id="login">
  Username: <input type="text" name="username" id="name"><br>
  Password: <input type="password" name="password" id="password">
  <input type="submit" name="submit" value="Submit">
  <input type="hidden" name="action" value='login'>
</form>

<h2>register</h2>
<form id="register">
  Username: <input type="text" name="username" id="name"><br>
  Password: <input type="password" name="password" id="password">
  <input type="submit" name="submit" value="Submit">
  <input type="hidden" name="action" value='register'>
</form>
<script src="js/common.js"></script>
<?php
  require_once("footer.php");//closes html
?>
