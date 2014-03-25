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
    // Temporarily not enforcing foreign key constraints, only noting with "References"
    $this->createTable('User',
    'userId int NOT NULL AUTO_INCREMENT,
     username VARCHAR(16) UNIQUE,
     password VARCHAR(128),
     admin BIT(1) DEFAULT 0,
     date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX(username),
     PRIMARY KEY(userId)');
    $this->createTable('Video', 
    'videoId int NOT NULL AUTO_INCREMENT, 
     submitterId int REFERENCES User(userId),
     ytid VARCHAR(11), 
     title VARCHAR(255), 
     length INT, 
     url VARCHAR(255), 
     played BIT(1) DEFAULT 0, 
     PRIMARY KEY(videoId), 
     INDEX(submitterId, title)');
    $this->createTable('VideoParty',
    'vpid int NOT NULL AUTO_INCREMENT,
     title VARCHAR(255),
     creatorId int REFERENCES User(uid),
     PRIMARY KEY(vpid)');

    // This table links videos to video parties. This way, we don't need
    // to store the same video's information more than once if it's played
    // several times across several video parties.
    // NOTE: duplicate (videoId, vpId) tuples will be allowed to
    //   exist so the same song can be submitted to a single video
    //   video party twice.
    // Index is on videoPartyId, then rating. This will facilitate searching
    // a certain video party for the highest rated song.
    $this->createTable('Video_VideoParty',
    'videoId int REFERENCES Video(videoId),
     videoPartyId int REFERENCES VideoParty(vpid),
     upvotes int,
     downvotes int,
     rating int,
     wasPlayed BIT(1) DEFAULT 0,
     INDEX(videoPartyId, wasPlayed, rating) ');
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
    $result = $this->_db->prepare("SELECT * FROM User WHERE username=:username;");
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
      $stmt = $this->_db->prepare("insert into User (username, password, admin) VALUES(:username, :password, :admin);");
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
    $stmt = $this->_db->prepare("SELECT * FROM User WHERE username=:username and password=:password;");
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

  // Fields for song include:
  //  Title, Length, url (ytid??), submitterId (uid??)
  public function addSong($uid, $ytid){
    require_once("includes/functions.php");
    require("includes/constants.php");
    $uid = sanitizeString($uid);
    $ytid = sanitizeString($ytid);
    $stmt = $this->_db->prepare("INSERT INTO Video (uid, ytid) VALUES(:uid, :ytid);");
    $stmt->bindValue(':uid', $uid);
    $stmt->bindValue(':ytid', $ytid);
    $stmt->execute();
  }

  public function listSongs(){
    echo "list of songs goes here";
  }
}

?> 
