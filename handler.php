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
  if(session_id() == '') {//starts a session if it haven't already
    session_start();
  }
  require_once("class.DB.php");//basic database operations
  $db = new DB();//connect to mysql
  
  if(isset($_GET['action'])){//GETs info ie. list of Videos or list of users
    if($_GET['action']=="listVideos"){
      $result=$db->listVideos(1);
    }
  }else if(isset($_POST['action'])){//handles POST requests ie. login or addVideo
    if($_POST['action']=="register"){//registers new user
      $result['registerStat']=$db->createAccount($_POST);//creates an account
      $result['token']=$db->logIn($_POST);//logs into created account
    }else if($_POST['action']=="login"){//logs into an account
      $result['token']=$db->logIn($_POST);//send POST data to log in

    }else if($_POST['action']=="addVideo"){//adds new video to playlist
      require('includes/constants.php');//some basic constants
      $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$_POST['youtubeId'].'&key='.$API_SERVER_KEY;//url to verify data from youtube
      $verify = curl_init($url);//configures cURL with url
      curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);//don't echo returned info
      $verify = json_decode(curl_exec($verify));//returned data from youtube
      if($verify->pageInfo->totalResults==1){//verified to be a real video
        $db->addVideo(array('youtubeId'=>$_POST['youtubeId'], 'userId'=>$_SESSION['userId'], 'title'=>$verify->items[0]->snippet->title));//calls database function to add the video
      }
      $result['status']="success";//was successful
    }
  }
  echo json_encode($result);//return info to client
  

?>