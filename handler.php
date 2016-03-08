<?php
/*
  interprets calls from javascript and returns status info and other data
  basically a REST API
  but like without .htaccess
  GET commands get info
  and Post commands make changes
  or have sensitive info like passwords
*/
  require_once("includes/constants.php");//basic database operations
  require_once("includes/functions.php");//basic database operations
  require_once("includes/User.php");//basic database operations
  require_once("includes/Party.php");//basic database operations
  if(session_id() == '') {
    session_start();
  }

  $user = new User($db);
  $user->initAuth($fb);
  $response = array();//array to be returned to client
  $errors = array();
  $version = 0;

  $_GET=sanitizeInputs($_GET);
  $_POST=sanitizeInputs($_POST);

  //error_log(json_encode($_GET));
  //error_log(json_encode($_POST));
  //error_log(json_encode($_SESSION));

  if (isset($_GET['v'])||isset($_POST['v'])){
    if (isset($_GET['v'])){
      $version= intval($_GET['v']);
    }else{
      $version = intval($_POST['v']);
    }
  }

  if (isset($_GET['action'])||isset($_POST['action'])){
    $method = '';
    $action = '';
    if (isset($_GET['action'])){
      $method = "GET";
      $action = $_GET['action'];
    }
    if(isset($_POST['action'])){
      $method = "POST";
      $action = $_POST['action'];
    }

    if(($method=="GET" && in_array($action, $getActions))||($method=="POST" && in_array($action, $postActions))){

      if($user->logged||in_array($action, $unsecuredActions)){

        switch ($action) {
          case "addVideo":
          case "addTrack":
            list($contentType, $contentId) = contentInfoFromRequest($_POST, $errors);
            if($contentType==''){
              break;
            }
            if(checkRequiredParameters($_POST, array("partyId"), $errors)){
              $party = new Party($db, $_POST['partyId']);
              $party->addVideo($user, $contentType, $contentId, $errors);
            }
            break;

          case 'createParty':
            if(checkRequiredParameters($_POST, array("name"), $errors)){
              $privacyId=FULLY_PUBLIC;
              if(isset($_POST['privacyId'])){
                $privacyId=$_POST['privacyId'];
              }
              $password='';
              if(isset($_POST['password'])){
                $password=$_POST['password'];
              }
              $response['partyId'] = $user->createParty($_POST['name'], $password, $privacyId, $errors);
            }
            break;

          case 'deleteParty':
            if(checkRequiredParameters($_POST, array("partyId"), $errors)){
              $party = new Party($db, $_POST['partyId']);
              $party->deleteParty($user, $errors);
            }
            break;

          case 'fbLogin':
            if(checkRequiredParameters($_POST, array("accessToken"), $errors)){
              fbLogin($_POST['accessToken']);
            }
            break;

          case 'getCurrentVideo':
          case 'getCurrentTrack':
            if(checkRequiredParameters($_GET, array("partyId"), $errors)){
              $party = new Party($db, $_GET['partyId']);
              $video = $party->getCurrentVideo($user, $version, $errors);
              if ($action == "getCurrentVideo"){
                $response['video']=$video;
              }else{
                $response['track']=$video;
              }
            }
            break;

          case 'joinParty':
            if(checkRequiredParameters($_POST, array("partyId"), $errors)){
              $password='';
              if(isset($_POST['password'])){
                $password=$_POST['password'];
              }
              $user->joinParty($_POST['partyId'], $password, 0, $errors);
            }
            break;

          case 'listJoinedParties':
            $response['parties'] = $user->listJoinedParties($errors);
            break;

          case 'listUnjoinedParties':
            $response['parties'] = $user->listUnjoinedParties($errors);
            break;

          case "listVideos":
          case "listTracks":
            if(checkRequiredParameters($_GET, array("partyId"), $errors)){
              $party = new Party($db, $_GET['partyId']);
              $videos = $party->listVideos($user, $version, $errors);
              if ($action == "listVideos"){
                $response['videos']=$videos;
              }else{
                $response['tracks']=$videos;
              }
            }
            break;

          case 'loginStatus':
            $response = loginStatus($user);
            break;

          case 'login':

            if(checkRequiredParameters($_POST, array("username", "password"), $errors)){
              login($db, $_POST['username'], $_POST['password'], $errors);
            }
            break;

          case 'logOut':
            logOut();
            header("Location: /");
            break;

          case 'markVideoWatched':
            if(checkRequiredParameters($_POST, array("submissionId"), $errors)){
              $party = new Party($db);
              $party->initFromSubmissionId($_POST['submissionId']);
              $party->markVideoWatched($user, $_POST['submissionId'], $errors);
            }
            break;

          case 'register':
            if(checkRequiredParameters($_POST, array("username", "password"), $errors)){
              if(createAccount($db, $_POST['username'], $_POST['password'], $errors)){
                login($db, $_POST['username'], $_POST['password'], $errors);
              }
            }
            break;

          case 'removeVideo':
          case 'removeTrack':
            if(checkRequiredParameters($_POST, array("submissionId"), $errors)){
              $party = new Party($db);
              $party->initFromSubmissionId($_POST['submissionId']);
              $party->removeVideo($user, $_POST['submissionId'], $errors);
            }
            break;

          case 'unjoinParty':
            if(checkRequiredParameters($_POST, array("partyId"), $errors)){
              $user->unjoinParty($_POST['partyId'], $errors);
            }
            break;

          case 'updateParty':
            if(checkRequiredParameters($_POST, array("partyId"), $errors)){
              $party = new Party($db, $_POST['partyId']);
              $response['updated'] = $party->updateParty($user, $_POST, $errors);
            }
            break;

          case 'vote':
            if(checkRequiredParameters($_POST, array("submissionId", "direction"), $errors)){
              $party = new Party($db);
              $party->initFromSubmissionId($_POST['submissionId']);
              $party->Vote($user, $_POST['submissionId'], $_POST['direction'], $errors);
            }
            break;
        }
      }else{
        array_push($errors, "user must be logged in to perform this action");
      }
    }elseif(($method=="POST" && in_array($actions, $getAction))||($method=="GET" && in_array($actions, $postAction))){
      array_push($errors, "malformed request: This request was sent using the wrong http method for the action");
    }else{
      array_push($errors, "malformed request: this action does not exist");
    }
  }else{
    array_push($errors, "malformed request: must have 'action' parameter");
  }

  if(count($errors)>0){
    $response = array('errors' => $errors, 'status'=>'failed');
  }else{
    $response['status']='success';
  }
  //error_log(json_encode($_SESSION));

  //error_log(json_encode($response));
  echo json_encode($response);//return info to client
?>
