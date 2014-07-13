<?php
/*
  interprets calls from javascript and returns status info and other data
  basically a REST API
  but like without .htaccess
  GET commands get info
  and Post commands make changes
  or have sensitive info like passwords
*/
  $results = array();//array to be returned to client
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
      $results = array_merge_recursive($results, listVideos($db, $_GET));
    }else if($_GET['action']=="getCurrentVideo"){
      $results = array_merge_recursive($results, getCurrentVideo($db, $_GET));
    }else if($_GET['action']=="listJoinedParties"){
      $results = array_merge_recursive($results, listJoinedParties($db));
    }else if($_GET['action']=="listUnjoinedParties"){
      $results = array_merge_recursive($results, listUnjoinedParties($db));
    }
  }else if(isset($_POST['action'])){//handles POST requests ie. login or addVideo
    if($_POST['action']=="register"){//registers new user
      $results = array_merge_recursive($results, createAccount($db, $_POST));//creates an account
      if(array_key_exists("status", $results) && $results['status']=='success'){
        $results = array_merge_recursive($results, logIn($db, $_POST));//logs into created account
      }
    }else if($_POST['action']=="login"){//logs into an account
      $results = array_merge_recursive($results, logIn($db, $_POST));//send POST data to log in
    }else if($_POST['action']=="addVideo"){//adds new video to playlist
      $results = array_merge_recursive($results, addVideo($db, $_POST));
    }else if($_POST['action']=="markVideoWatched"){//adds new video to playlist
      $results = array_merge_recursive($results, markVideoWatched($db, $_POST));
    }else if($_POST['action']=="removeVideo"){//adds new video to playlist
      $results = array_merge_recursive($results, removeVideo($db, $_POST));
    }else if($_POST['action']=="createParty"){//adds new video to playlist
      $results = array_merge_recursive($results, createParty($db, $_POST));
    }else if($_POST['action']=="joinParty"){//adds new video to playlist
      $results = array_merge_recursive($results, joinParty($db, $_POST));
    }else if($_POST['action']=="vote"){//adds new video to playlist
      $results = array_merge_recursive($results, vote($db, $_POST));
    }
  }

  //this block makes the status either success or failed and unsets errors if it doesn't exist
  $finalStatus = 'failed';
  if(array_key_exists("status", $results)){
    if(is_array($results['status'])){
      $finalStatus = 'success';
      foreach ($results['status'] as $status){
        if($status != "success"){
          $finalStatus = 'failed';
          break;
        }
      }
    }elseif($results['status']=='success'){
      $finalStatus = 'success';
    }
  }

  if(array_key_exists("errors", $results) && count($results['errors'])==0){
    unset($results['errors']);
  }else{
    $finalStatus = 'failed';
  }
  $results['status'] = $finalStatus;


  echo json_encode($results);//return info to client
?>