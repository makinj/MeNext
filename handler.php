<?php
/*
  interprets calls from javascript and returns status info and other data
  basically a REST API
  but like without .htaccess
  GET commands get info
  and Post commands make changes
  or have sensitive info like passwords
*/
  require_once("includes/functions.php");//basic database operations

  $results = array();//array to be returned to client

  if(isset($_GET['action'])){//GETs info ie. list of Videos or list of users
    if($_GET['action']=="listVideos"){
      $results = array_merge_recursive($results, listVideos($db, $_GET));
    }else if($_GET['action']=="getCurrentVideo"){
      $results = array_merge_recursive($results, getCurrentVideo($db, $_GET));
    }else if($_GET['action']=="listJoinedParties"){
      $results = array_merge_recursive($results, listJoinedParties($db));
    }else if($_GET['action']=="listUnjoinedParties"){
      $results = array_merge_recursive($results, listUnjoinedParties($db));
    }else if($_GET['action']=="logOut"){
      $results = array_merge_recursive($results, logOut($db));
      header("Location: login.php");//login again
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
    }else if($_POST['action']=="markVideoWatched"){//marks video as watched
      $results = array_merge_recursive($results, markVideoWatched($db, $_POST));
    }else if($_POST['action']=="removeVideo"){//removes video
      $results = array_merge_recursive($results, removeVideo($db, $_POST));
    }else if($_POST['action']=="createParty"){//creates a party
      $results = array_merge_recursive($results, createParty($db, $_POST));
    }else if($_POST['action']=="joinParty"){//joins current user to party
      $results = array_merge_recursive($results, joinParty($db, $_POST));
    }else if($_POST['action']=="vote"){//votes on video
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