<?php
  require_once("includes/functions.php"); //basic database operations

  header('Access-Control-Allow-Origin: https://www.googleapis.com');
  if ((!isset($_SESSION['logged'])) && isset($restricted) && $restricted == true) {
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
    <title><?php echo "MeNext | " . $title; ?></title>

    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>

    <!-- Bootstrap -->
    <link href="css/sandstone/bootstrap.min.css" rel="stylesheet"/>
    <link href="css/jquery-ui.css" type="text/css" rel="stylesheet"/>
    <script src="js/bootstrap.min.js"></script>

    <!-- Main Stylesheet -->
    <link href="css/main.css" rel="stylesheet"/>


    <!-- QR Code generation from github user davidshimjs -->
    <script src="js/qrcode.min.js" type="text/javascript"></script>

    <script src="js/jquery-ui.min.js" type="text/javascript"></script>
    <script src="js/common.js" type="text/javascript"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div class="navbar navbar-default navbar-static-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="index.php"><img src="images/headerLogoSmall.png" id="headerLogo"/></a>
        </div>
        <div class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <?php if (isset($_SESSION['logged'])) { ?>
                    <li><a href="index.php">Dashboard</a></li>
                <?php } else { ?>
                    <li><a href="index.php">Home</a></li>
                <?php } ?>
                <li><a href="#about">About</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <?php if (isset($_SESSION['logged'])) { ?>
                    <li><a href="handler.php?action=logOut">Log Out</a></li>
                <?php } else { ?>
                    <li><a href="login.php">Login/Register</a></li>
                <?php } ?>
            </ul>
        </div>
        <!--/.nav-collapse -->
    </div>
</div>
<div class="container">
