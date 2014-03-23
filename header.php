<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap 101 Template</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <?php
      if(session_id() == '') {
        session_start();
      }
      echo "<a href='/'>home</a>";
      if(isset($_SESSION['logged'])){
        echo" | You are logged in as ".$_SESSION['username']." | <a href='/submit.php'>submit</a> | <a href='/logout.php'>log out</a>";
      }else{
        echo " | <a href='/login.php'>login/register</a>";
        if(isset($restricted)&&$restricted==true){
          header("location: /");
          exit;
        }
      }
      echo "<br>";
    ?>