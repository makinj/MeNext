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
      }
    }

  //}
  echo json_encode($result);
  

?>