<?php
/*
  interprets calls from javascript and returns status info and other data
  basically a REST API
  but like without .htaccess
  GET commands get info
  and Post commands make changes
  or have sensitive info like passwords
*/
  $result=array();//array to be returned to client
  if (isset($_GET['token'])){
    session_id($_GET['token']);
  }
  if (isset($_POST['token'])){
    session_id($_POST['token']);
  }
  session_start();
  require_once("class.DB.php");//basic database operations
  $db = new DB();//connect to mysql
  
  if(isset($_GET['action'])){//GETs info ie. list of Videos or list of users
    if($_GET['action']=="listVideos"){
      $result=$db->listVideos($_GET);
    }else if($_GET['action']=="getCurrentVideo"){
      $result=$db->getCurrentVideo($_GET);
    }else if($_GET['action']=="listJoinedParties"){
      $result=$db->listJoinedParties();
    }else if($_GET['action']=="listUnjoinedParties"){
      $result=$db->listUnjoinedParties();
    }
  }else if(isset($_POST['action'])){//handles POST requests ie. login or addVideo
    if($_POST['action']=="register"){//registers new user
      $result['registerStat']=$db->createAccount($_POST);//creates an account
      $result['token']=$db->logIn($_POST);//logs into created account
    }else if($_POST['action']=="login"){//logs into an account
      $result['token']=$db->logIn($_POST);//send POST data to log in
    }else if($_POST['action']=="addVideo"){//adds new video to playlist
      $result['status']= $db->addVideo($_POST);
    }else if($_POST['action']=="markVideoWatched"){//adds new video to playlist
      $result=$db->markVideoWatched($_POST);
    }else if($_POST['action']=="removeVideo"){//adds new video to playlist
      $result=$db->removeVideo($_POST);
    }else if($_POST['action']=="createParty"){//adds new video to playlist
      $result=$db->createParty($_POST);
    }else if($_POST['action']=="joinParty"){//adds new video to playlist
      $result=$db->joinParty($_POST);
    }
  }
  echo json_encode($result);//return info to client
?>