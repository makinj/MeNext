<?php
/*
  interprets calls from javascript and returns status info and other data
  basically a REST API
  but like without .htaccess
  GET commands get info
  and Post commands make changes
  or have sensitive info like passwords
*/
  $result = array();//array to be returned to client
  if (isset($_GET['token'])){
    session_id($_GET['token']);
  }
  if (isset($_POST['token'])){
    session_id($_POST['token']);
  }
  session_start();
  require_once("includes/functions.php");//basic database operations
  $db = connectDb();//connect to mysql

  if(isset($_GET['action'])){//GETs info ie. list of Videos or list of users
    if($_GET['action']=="listVideos"){
      $result = listVideos($db, $_GET);
    }else if($_GET['action']=="getCurrentVideo"){
      $result = getCurrentVideo($db, $_GET);
    }else if($_GET['action']=="listJoinedParties"){
      $result = listJoinedParties($db);
    }else if($_GET['action']=="listUnjoinedParties"){
      $result = listUnjoinedParties($db);
    }
  }else if(isset($_POST['action'])){//handles POST requests ie. login or addVideo
    if($_POST['action']=="register"){//registers new user
      $result['registerStat'] = createAccount($db, $_POST);//creates an account
      $result['token'] = logIn($db, $_POST);//logs into created account
    }else if($_POST['action']=="login"){//logs into an account
      $result['token'] = logIn($db, $_POST);//send POST data to log in
    }else if($_POST['action']=="addVideo"){//adds new video to playlist
      $result['status'] = addVideo($db, $_POST);
    }else if($_POST['action']=="markVideoWatched"){//adds new video to playlist
      $result = markVideoWatched($db, $_POST);
    }else if($_POST['action']=="removeVideo"){//adds new video to playlist
      $result = removeVideo($db, $_POST);
    }else if($_POST['action']=="createParty"){//adds new video to playlist
      $result = createParty($db, $_POST);
    }else if($_POST['action']=="joinParty"){//adds new video to playlist
      $result = joinParty($db, $_POST);
    }else if($_POST['action']=="vote"){//adds new video to playlist
      $result = vote($db, $_POST);
    }
  }
  echo json_encode($result);//return info to client
?>