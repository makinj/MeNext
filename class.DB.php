<?php

class DB
{
  private $_db;//PDO for class
  
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
    $this->executeSQL('CREATE TABLE User(
     userId int NOT NULL AUTO_INCREMENT,
     username VARCHAR(16) UNIQUE,
     password VARCHAR(128),
     admin BIT(1) DEFAULT 0,
     date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX(username),
     PRIMARY KEY(userId));');
    // Other ideas for attributes include length, and url
    // May remove videoId in the future, as youtubeId is unique to the video already
    // If so, would make Index(youtubeId) instead of videoId
    $this->executeSQL('CREATE TABLE Video( 
     videoId int NOT NULL AUTO_INCREMENT, 
     youtubeId VARCHAR(11) UNIQUE, 
     title VARCHAR(255), 
     played BIT(1) DEFAULT 0, 
     PRIMARY KEY(videoId), 
     INDEX(videoId));');
    $this->executeSQL('CREATE TABLE VideoParty(
     vpid int NOT NULL AUTO_INCREMENT,
     title VARCHAR(255),
     creatorId int REFERENCES User(uid),
     PRIMARY KEY(vpid));');

    // Index is on videoPartyId, then rating. This will facilitate searching
    // a certain video party for the highest rated song.
    $this->executeSQL('CREATE TABLE Submission(
     submissionId int NOT NULL AUTO_INCREMENT,
     videoId int REFERENCES Video(videoId),
     videoPartyId int REFERENCES VideoParty(vpid),
     submitterId int REFERENCES User(userId),
     upvotes int DEFAULT 0,
     downvotes int DEFAULT 0,
     rating int DEFAULT 0,
     wasPlayed BIT(1) DEFAULT 0,
     INDEX(submissionId, wasPlayed, rating),
     PRIMARY KEY(submissionId));');
    $this->executeSQL('CREATE TABLE Vote(
     voterId int REFERENCES User(userId),
    submissionId int REFERENCES Submission(submissionId),
    voteValue int,
    PRIMARY KEY(voterId, submissionId));');
    $this->createAccount(array('username'=>$ADMIN_NAME, 'password'=>$ADMIN_PASS),1);
    // Create videoparty for testing:
    //   (vpid=1, userId=1)
    $this->executeSQL('INSERT INTO VideoParty(title, creatorId) VALUES (1,1)');
  }

  private function executeSQL($query){
    try{
      $this->_db->exec("$query");
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

  public function createAccount($args, $admin=0){
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){
      $username=$args['username'];
      $password=$args['password'];   
      require_once("includes/functions.php");
      require("includes/constants.php");
      $username = sanitizeString($username);
      $password = hash('sha512',$PRE_SALT.sanitizeString($password).$POST_SALT);
      if($this->isUser($username)){
        return "alreadyExists";
      } else {
        $stmt = $this->_db->prepare("insert into User (username, password, admin) VALUES(:username, :password, :admin);");
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $password);
        $stmt->bindValue(':admin', $admin, PDO::PARAM_BOOL);
        $stmt->execute();
        return "success";
      }
    }
  }
  public function signIn($args){
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){
      $username=$args['username'];
      $password=$args['password'];
      require_once("includes/functions.php");
      require("includes/constants.php");
      $username = sanitizeString($username);
      $password = hash('sha512',$PRE_SALT.sanitizeString($password).$POST_SALT);
      $stmt = $this->_db->prepare("SELECT * FROM User WHERE username=:username and password=:password;");
      $stmt->bindValue(':username', $username);
      $stmt->bindValue(':password', $password);
      $stmt->execute();
      $result = $stmt->fetch();
      if($stmt->rowCount()==1){
        if(session_id() == '') {
          session_start();
        }
        $_SESSION['username']=$username;
        $_SESSION['userId']=$result[0];
        $_SESSION['admin']=$result[3];
        $_SESSION['logged']=1;
        return $username;
      }
      else{
        return 0;
      }
    }
  }

  public function addSong($args){
    require_once("includes/functions.php");
    require("includes/constants.php");

    $submitterId = sanitizeString($args['userId']);
    $youtubeId = sanitizeString($args['youtubeId']);
    $title = sanitizeString($args['title']);
    //print "Add Song()<BR>";

    // Want to try to insert, but not change the videoId, and 
    //   change LAST_INSERT_ID() to be the videoId of the inserted video
    $stmt = $this->_db->prepare("
      INSERT INTO Video (youtubeId, title) 
      VALUES (:youtubeId, :title) 
      ON DUPLICATE KEY UPDATE videoId = LAST_INSERT_ID(videoId);");
    $stmt->bindValue(':youtubeId', $youtubeId);
    $stmt->bindValue(':title', $title);
    // TODO: Add error checking for SQL execution:
    $stmt->execute();

    // Insert into Submissions.
    // LAST_INSERT_ID() returns id of last insertion's (or replace) auto-increment field
    //     First we'll get this working with just 1 video party, vpid=1
    $vpid = 1;
    $stmt = $this->_db->prepare("INSERT INTO Submission (videoId, videoPartyId, submitterId) VALUES(LAST_INSERT_ID(), :vpid, :submitterId );");
    $stmt->bindValue(':submitterId', $submitterId);
    $stmt->bindValue(':vpid', $vpid);
    $stmt->execute();
  }

  public function listSongs($vpid){
    // Formatting taken from http://www.php.net/manual/en/ref.pdo-mysql.php, comment by dibakar
    require_once("includes/functions.php");
    try {
      // Find all videos associated with $vpid (set to 1 for testing)
      $stmt = $this->_db->prepare("
        SELECT v.youtubeId, v.title, s.submissionId
        FROM Submission s, Video v
        WHERE s.videoId = v.videoId AND
        s.videoPartyId = :vpid AND
        s.wasPlayed=0;");
      $vpid = sanitizeString($vpid);
      $stmt->bindValue(':vpid', $vpid);
      $stmt->execute();
      $result=array();
      while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        array_push($result, $row);
      }
      return $result;
    } catch (PDOException $e) {
      print "Error!: " . $e->getMessage() . "<br/>";
      exit();
    }
  }
  public function markSongWatched($arr){
    require_once("includes/functions.php");
    try {
      $stmt = $this->_db->prepare("
        UPDATE Submission
        SET s.wasPlayed = 1
        WHERE s.submissionId = :submissionId;");
      $vpid = sanitizeString($vpid);
      $stmt->bindValue(':vpid', $vpid);
      $stmt->execute();
    } catch (PDOException $e) {
      exit();
    }
  }

}

?> 
