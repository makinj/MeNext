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

    <!-- Bootstrap 
    <link href="css/bootstrap.min.css" rel="stylesheet" />-->
    <link href="css/jquery-ui.css" type="text/css" rel="stylesheet"/>
    
    <!-- BASE -->
    <link href="css/base.css" type="text/css" rel="stylesheet"/>

    <!-- Main Stylesheet -->
    <link href="css/main.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>

    <!-- QR Code generation from github user davidshimjs -->
    <script src="js/qrcode.min.js" type="text/javascript"></script>
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
    
      <div class="header dark-blue-row">
        <div class="row clear">
          <div class="col col-2 tablet-col-10 mobile-col-3-4 colHeader">
            <a class="logo left mobile-no-float" href="/"><img src="/images/headerLogoSmall.png" id="headerLogo" /></a>
          </div>
          <div class="col col-10 tablet-col-2 mobile-col-1-4 colHeader">
          
            <?php
              if(isset($_SESSION['logged'])){
            ?>
            
            <a class="right mobile-no-float logLink" href="logout.php">Log Out</a>
            
            <?php
              }else{
            ?>
            
            <a class="right mobile-no-float logLink" href="login.php">Login/Register</a>
            
            <?php
              }
            ?>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    <div class="container row-2">
    
