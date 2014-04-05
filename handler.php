<?php
  $result=array();
  if(session_id() == '') {
    session_start();
  }
  //if(isset($_SESSION['logged'])){
    require_once("class.DB.php");//basic database operations
    $db = new DB();//connect to mysql
    
    if(isset($_GET['action'])){
      /*
      if($_GET['action']=="listStd"){
        if($_SESSION['admin']==1){
          $db->listStd();
        }else{
          echo "cannot load if not admin";
        }
      }
      */
    }else if(isset($_POST['action'])){
      if($_POST['action']=="register"){
        $result=array();
        $result['reg']=$db->createAccount($_POST);
        $result['token']=$db->signIn($_POST);
      }else if($_POST['action']=="login"){
        $result['token']=$db->signIn($_POST);
      }else if($_POST['action']=="addSong"){
        require('includes/constants.php');
        $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$_POST['ytid'].'&key='.$API_SERVER_KEY;
        //echo $url;
        $verify = curl_init('https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$_POST['ytid'].'&key='.$API_SERVER_KEY);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);
        $verify = json_decode(curl_exec($verify));
        if($verify->pageInfo->totalResults==1){
          //echo $verify->items[0]->snippet->title;
          $db->addSong(array('youtubeId'=>$_POST['ytId'], 'userId'=>$_SESSION['uid'], 'title'=>$verify->items[0]->snippet->title));
        }
        $result['status']="success";
      }
    }

  //}
  echo json_encode($result);
  

?>