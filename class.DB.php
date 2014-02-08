<?php


class DB
{

  private $_db;
  
  public function __construct($db=NULL){
    require("/includes/constants.php");

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
    require("/includes/constants.php");
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
      echo "created database: $DB_NAME<br>";
    $this->_db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS); 
    $this->createTable('USERS', 'id int NOT NULL AUTO_INCREMENT, user VARCHAR(16), pass VARCHAR(32), admin BIT(1), INDEX(user(6)), PRIMARY KEY(id)');
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

  public function createAccount($username, $password, $admin=0){

    require_once("includes/functions.php");
    require("includes/constants.php");
    $username = sanitizeString($username);
    $password = md5($pre_salt.sanitizeString($password).$post_salt);
    $result = $this->_db->prepare("SELECT * FROM USERS WHERE user=:username;");
    $result->bindValue(':username', $username);
    $result->execute();
    if($result->rowCount()>0){
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
    $password = md5($pre_salt.sanitizeString($password).$post_salt);

    $stmt = $this->_db->prepare("SELECT * FROM USERS WHERE user=:username;");
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    if($stmt->rowCount()==1){
      $result = $stmt->fetch();
      if($result[2]==$password){
        echo "welcome!!!";
      }
    }
  }
  public function makevote($sid){
    
  }
}

?>