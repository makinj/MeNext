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
  if(session_id() == '') {//starts a session if it haven't already
    session_start();
  }
  require_once("class.DB.php");//basic database operations
  $db = new DB();//connect to mysql
  
  if(isset($_GET['action'])){//GETs info ie. list of songs or list of users
    /* only a template for how it will be implemented
        so far there are no get functions
    if($_GET['action']=="listStd"){
      if($_SESSION['admin']==1){
        $db->listStd();
      }else{
        echo "cannot load if not admin";
      }
    }
    */
  }else if(isset($_POST['action'])){//handles POST requests ie. login or addsong
    if($_POST['action']=="register"){//registers new user
      $result['registerStat']=$db->createAccount($_POST);//creates an account
      $result['token']=$db->signIn($_POST);//signs into created account
    }else if($_POST['action']=="login"){//logs into an account
      $result['token']=$db->signIn($_POST);//send POST data to sign in
    }else if($_POST['action']=="addSong"){//adds new song to playlist
      require('includes/constants.php');//some basic constants
      $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$_POST['youtubeId'].'&key='.$API_SERVER_KEY;//url to verify data from youtube
      $verify = curl_init($url);//configures cURL with url
      curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);//don't echo returned info
      $verify = json_decode(curl_exec($verify));//returned data from youtube
      if($verify->pageInfo->totalResults==1){//verified to be a real video
        $db->addSong(array('youtubeId'=>$_POST['youtubeId'], 'userId'=>$_SESSION['userId'], 'title'=>$verify->items[0]->snippet->title));//calls database function to add the song
      }
      $result['status']="success";//was successful
    }
  }

  echo json_encode($result);//return info to client
  

?>