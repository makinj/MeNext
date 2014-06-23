<?php 
  if(session_id() == '') {
    session_start();
  }
  header('Access-Control-Allow-Origin: https://www.googleapis.com');
  if((!isset($_SESSION['logged']))&&isset($restricted)&&$restricted==true){
    header("location: /");
    exit;
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo "MeNext | ".$title;?></title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/jquery-ui.css" type="text/css" rel="stylesheet"/>

    <!-- Main Stylesheet -->
    <link href="css/main.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>

    <script src="js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="/js/common.js" type="text/javascript"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    
    <nav class="navbar navbar-inverse" role="navigation">
      <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/">MeNext</a>
        </div>
        <?php
          if(isset($_SESSION['logged'])){
        ?>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
              <li<?php if($title=="index"){echo ' class="active"';}?>><a href="/">Home</a></li>
              <li<?php if($title=="submit"){echo ' class="active"';}?>><a href="submit.php">Submit</a></li>
            </ul>

            <ul class="nav navbar-nav navbar-right">
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"> <?php echo $_SESSION['username'];?><b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="logout.php">Log Out</a></li>
                </ul>
              </li>
            </ul>
          </div><!-- /.navbar-collapse -->
        <?php
          }else{
        ?>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
              <li><a href="login.php">Login/Register</a></li>
            </ul>
          </div><!-- /.navbar-collapse -->
        <?php
          }
        ?>

      </div><!-- /.container-fluid -->
    </nav>
    <div class="container">
    
