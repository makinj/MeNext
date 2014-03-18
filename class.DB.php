<?php


class DB
{
  private $_db;
  
  public function __construct($db=NULL){
    require("includes/constants.php");

    if(is_object($db)){
      $this->_db = $db;
    }else{
      $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME";
      try{
        $this->_db = new PDO($dsn, $DB_USER, $DB_PASS);
      }catch(PDOException $e){
        $this->setup();
      }
    }
  }




  private function setup(){
    require("includes/constants.php");
    try {
      $this->_db = new PDO("mysql:host=$DB_HOST", $DB_USER, $DB_PASS);
    } catch (PDOException $e) {
      echo 'Connection failed: ' . $e->getMessage()."<br>";
      exit;
    }
    try {
      $this->_db->exec("CREATE DATABASE IF NOT EXISTS $DB_NAME;");
    } catch (PDOException $e) {
      echo 'Database $dbname was unsuccessful: ' . $e->getMessage()."<br>";
      exit;
    }
    $this->_db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS); 
    $this->createTable('USERS', 'uid int NOT NULL AUTO_INCREMENT, user VARCHAR(16), pass VARCHAR(128), admin BIT(1), INDEX(user(6)), PRIMARY KEY(uid)');
    $this->createTable('SUBMISSIONS', 'sid int NOT NULL AUTO_INCREMENT, uid int, ytid VARCHAR(11), played BIT(1), INDEX(uid,sid), PRIMARY KEY(sid)');
    $this->createAccount($ADMIN_NAME, $ADMIN_PASS, 1);
  }


  private function createTable($name, $query){
    try{

      $this->_db->exec("create table $name($query);");
    }catch(PDOException $e){
      echo 'Connection failed: ' . $e->getMessage()."<br>";
      exit;
    }
  }
  public function isUser($name){
    require_once("includes/functions.php");
    require("includes/constants.php");
    $name = sanitizeString($name);
    $result = $this->_db->prepare("SELECT * FROM USERS WHERE user=:username;");
    $result->bindValue(':username', $name);
    $result->execute();
    return ($result->rowCount()>0);
  }

  public function createAccount($username, $password, $admin=0){

    require_once("includes/functions.php");
    require("includes/constants.php");
    $username = sanitizeString($username);
    $password = hash('sha512',$pre_salt.sanitizeString($password).$post_salt);
    if($this->isUser($username)){
      echo "player $username already exists";
    } else {
      $stmt = $this->_db->prepare("insert into USERS (user, pass, admin) VALUES(:username, :password, :admin);");
      $stmt->bindValue(':username', $username);
      $stmt->bindValue(':password', $password);
      $stmt->bindValue(':admin', $admin, PDO::PARAM_BOOL);
      $stmt->execute();
    }
  }
  public function signIn($username, $password){
    require_once("includes/functions.php");
    require("includes/constants.php");
    $username = sanitizeString($username);
    $password = hash('sha512',$pre_salt.sanitizeString($password).$post_salt);
    $stmt = $this->_db->prepare("SELECT * FROM USERS WHERE user=:username and pass=:password;");
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':password', $password);
    $stmt->execute();
    $result = $stmt->fetch();
    if($stmt->rowCount()==1){
      echo "welcome $username!!!";
      if(session_id() == '') {
        session_start();
      }
      $_SESSION['username']=$username;
      $_SESSION['uid']=$result[0];
      $_SESSION['admin']=$result[3];
      $_SESSION['logged']=1;
      return 1;
    }
    else{
      return 0;
    }
    
  }
  public function addSong($uid, $ytid){
    require_once("includes/functions.php");
    require("includes/constants.php");
    $uid = sanitizeString($uid);
    $ytid = sanitizeString($ytid);
    $stmt = $this->_db->prepare("insert into SUBMISSIONS (uid, ytid) VALUES(:uid, :ytid);");
    $stmt->bindValue(':uid', $uid);
    $stmt->bindValue(':ytid', $ytid);
    $stmt->execute();
  }

  public function listSongs(){
    echo "list of songs goes here";
  }
}

?>