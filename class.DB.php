<?php

class DB
{
  private $_db;//PDO for class
  
  public function __construct($db=NULL){
    require("includes/constants.php");//get system-specific variables

    if(is_object($db)){//user specifies new database
      $this->_db = $db;
    }else{
      $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME";//string to connect to database
      try{
        $this->_db = new PDO($dsn, $DB_USER, $DB_PASS);
      }catch(PDOException $e){//connection failed, set up a new database
        $this->setup();
      }
    }
  }


  private function setup(){//creates the database needed to run the application
    require("includes/constants.php");//get system-specific variables
    try {
      $this->_db = new PDO("mysql:host=$DB_HOST", $DB_USER, $DB_PASS);//connect to host
    } catch (PDOException $e) {//probably username or password wrong.  Sometimes problem with PDO class or mysql itself
      error_log('Connection failed: ' . $e->getMessage());
      exit;
    }
    try {
      $this->_db->exec("CREATE DATABASE IF NOT EXISTS $DB_NAME;");//creates database in mysql for the app
    } catch (PDOException $e) {//could not make database
      error_log('Database $dbname was unsuccessful: ' . $e->getMessage());
      exit;
    }
    $this->_db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASS);//connects to new database 
    // Temporarily not enforcing foreign key constraints, only noting with "References"
    $this->executeSQL('CREATE TABLE User(
     userId int NOT NULL AUTO_INCREMENT,
     username VARCHAR(16) UNIQUE,
     password VARCHAR(128),
     admin BIT(1) DEFAULT 0,
     date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX(username),
     PRIMARY KEY(userId));');//stores each user as a row with relevent info
    // Other ideas for attributes include length, and url
    // May remove videoId in the future, as youtubeId is unique to the video already
    // If so, would make Index(youtubeId) instead of videoId
    $this->executeSQL('CREATE TABLE Video( 
     videoId int NOT NULL AUTO_INCREMENT, 
     youtubeId VARCHAR(11) UNIQUE, 
     title VARCHAR(255), 
     played BIT(1) DEFAULT 0, 
     PRIMARY KEY(videoId), 
     INDEX(videoId));');//specific video, avoids popular selections bloating database
    $this->executeSQL('CREATE TABLE VideoParty(
     vpid int NOT NULL AUTO_INCREMENT,
     title VARCHAR(255),
     creatorId int REFERENCES User(uid),
     PRIMARY KEY(vpid));');//each party has row right now there is only one

    // Index is on videoPartyId, then rating. This will facilitate searching
    // a certain video party for the highest rated video.
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
     PRIMARY KEY(submissionId));');//individual actual submission
    $this->executeSQL('CREATE TABLE Vote(
     voterId int REFERENCES User(userId),
    submissionId int REFERENCES Submission(submissionId),
    voteValue int,
    PRIMARY KEY(voterId, submissionId));');//stores votes by users to songs
    $this->createAccount(array('username'=>$ADMIN_NAME, 'password'=>$ADMIN_PASS),1);//makes default admin user
    // Create videoparty for testing:
    //   (vpid=1, userId=1)
    $this->executeSQL('INSERT INTO VideoParty(title, creatorId) VALUES (1,1)');
  }

  private function executeSQL($query){//runs a query with PDO's specific syntax
    try{
      $this->_db->exec("$query");
    }catch(PDOException $e){//something went wrong...
      error_log('Query failed: ' . $e->getMessage());
      exit;
    }
  }

  public function isUser($name){//checks for row in user table corresponding to username provided
    require_once("includes/functions.php");//popular functions
    require("includes/constants.php");//get system-specific variables
    $name = sanitizeString($name);//prevents sql injection attempts
    $result = $this->_db->prepare("SELECT * FROM User WHERE username=:username;");//performs check
    $result->bindValue(':username', $name);
    $result->execute();
    return ($result->rowCount()>0);//1 if exists
  }

  public function createAccount($args, $admin=0){//creates account with an array of user information given
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
      require_once("includes/functions.php");
      require("includes/constants.php");//get system-specific variables
      $username = sanitizeString($args['username']);
      $password = hash('sha512',$PRE_SALT.sanitizeString($args['username']).$POST_SALT);
      if($this->isUser($username)){//user already exists
        return "alreadyExists";
      } else {
        $stmt = $this->_db->prepare("insert into User (username, password, admin) VALUES(:username, :password, :admin);");//makes new row with given info
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $password);
        $stmt->bindValue(':admin', $admin, PDO::PARAM_BOOL);
        $stmt->execute();
        return "success";
      }
    }
  }
  public function logIn($args){//sets session data if the user information matches a user's row
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
      require_once("includes/functions.php");
      require("includes/constants.php");//get system-specific variables
      $username = sanitizeString($args['username']);
      $password = hash('sha512',$PRE_SALT.sanitizeString($args['username']).$POST_SALT);
      $stmt = $this->_db->prepare("SELECT * FROM User WHERE username=:username and password=:password;");//checks for matching row
      $stmt->bindValue(':username', $username);
      $stmt->bindValue(':password', $password);
      $stmt->execute();
      if($stmt->rowCount()==1){//if successfully logged in
        $result = $stmt->fetch();
        if(session_id() == '') {
          session_start();
        }
        $_SESSION['username']=$username;
        $_SESSION['userId']=$result[0];
        $_SESSION['admin']=$result[3];
        $_SESSION['logged']=1;
        return session_id();
        /*
          eventually, I would like to see a token unique to the user returned that 
          is updated at each log in so that we may check for a token instead of
          handling cookies and everything on mobile.
          -Josh
        */
      }
      else{//unsuccessful login attempt
        return -1;
      }
    }
  }

  public function addVideo($args){
    if (is_array($args)&&array_key_exists("userId", $args)&&array_key_exists("youtubeId", $args)&&array_key_exists("title", $args)){
      require_once("includes/functions.php");
      require("includes/constants.php");//get system-specific variables

      $submitterId = sanitizeString($args['userId']);
      $youtubeId = sanitizeString($args['youtubeId']);
      $title = sanitizeString($args['title']);

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
  }

  public function listVideos($vpid){
    require_once("includes/functions.php");
    try {
      // Find all videos associated with $vpid (set to 1 for testing)
      $stmt = $this->_db->prepare("
        SELECT v.youtubeId, v.title, s.submissionId, u.username
        FROM Submission s, Video v, User u
        WHERE s.videoId = v.videoId AND
        s.videoPartyId = :vpid AND
        s.wasPlayed=0 AND
        s.submitterId = u.userId
        ORDER BY s.submissionId ASC;");
      $vpid = sanitizeString($vpid);
      $stmt->bindValue(':vpid', $vpid);
      $stmt->execute();
      $result=array();
      while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {//creates an array of the results to return
        array_push($result, $row);
      }
      return $result;
    } catch (PDOException $e) {//something went wrong...
      error_log("Error: " . $e->getMessage());
      exit();
    }
  }
  public function markVideoWatched($arr){//takes an array of or argument with the submission id of what to delete
    require_once("includes/functions.php");
    try {
      $stmt = $this->_db->prepare("
        UPDATE Submission
        SET s.wasPlayed = 1
        WHERE s.submissionId = :submissionId;");
      $submissionId = sanitizeString($arr['submissionId']);
      $stmt->bindValue(':submissionId', $submissionId);
      $stmt->execute();
    } catch (PDOException $e) {
      //something went wrong...
      error_log("Error: " . $e->getMessage());
      exit();
    }
  }

}

?> 
