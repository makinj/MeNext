<?php
  require_once("class.DB.php");
  $title="login";
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
<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="js/login.js"></script>
<?php
  require_once("footer.php");
?>
