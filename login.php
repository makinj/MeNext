<?php
  require_once("class.DB.php");
  include("header.php");
  if(session_id() == '') {
    session_start();
  }
  if(isset($_SESSION['logged'])){
    echo "<h1>already signed in as ".$_SESSION['username']."</h1>
        <a href='/logout.php'>Log out</a>";
  }elseif(isset($_POST['username'])&&isset($_POST['password'])&&isset($_POST['action'])){
    $db = new DB();
    if ($_POST['action']=='register') {
      $db->createAccount($_POST['username'],$_POST['password']);
      $db->signIn($_POST['username'],$_POST['password']);
    }
    if($_POST['action']=="login"||$_POST['action']=='register'){
      if($db->signIn($_POST['username'],$_POST['password'])){
        header("Location: /");
        exit();
      }else{
        echo "Failed to sign in";
      }
    }
  }
    echo <<<END
<h1>login</h1>
<form action="login.php" method="post" enctype="multipart/form-data">
  Username: <input type="text" name="username" id="name"><br>
  Password: <input type="password" name="password" id="password">
  <input type="submit" name="submit" value="Submit">
  <input type="hidden" name="action" value='login'>
</form>
<h2>register</h2>
<form action="login.php" method="post" enctype="multipart/form-data">
  Username: <input type="text" name="username" id="name"><br>
  Password: <input type="password" name="password" id="password">
  <input type="submit" name="submit" value="Submit">
  <input type="hidden" name="action" value='register'>
</form>
<script type="text/javascript">
  var register= document.getElementById("register");
  function expand_register(){
    console.log("register");
    register.innerHTML='';
  }
</script>
</body>
</html>
END;
?>
