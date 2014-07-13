<?php
  /*
  Joshua Makinen
  */

  require_once("constants.php");//get system-specific variables

  function sanitizeString($var){//cleans a string up so there are no crazy vulerabilities
    $var = strip_tags($var);
    $var = htmlentities($var);
    return stripslashes($var);
  }

  function connectDb(){
    require_once("constants.php");//get system-specific variables
    $db=0;
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME;//string to connect to database
    try{
      $db = new PDO($dsn, DB_USER, DB_PASS);
    }catch(PDOException $e){//connection failed, set up a new database
      $db = setup();
    }
    return $db;
  }

  function setup(){//creates the database needed to run the application
    $db=0;
    try {
      $db = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);//connect to host
    } catch (PDOException $e) {//probably username or password wrong.  Sometimes problem with PDO class or mysql itself
      error_log('Connection failed: ' . $e->getMessage());
      exit;
    }
    try {
      $db->exec("CREATE DATABASE IF NOT EXISTS ".DB_NAME.";");//creates database in mysql for the app
    } catch (PDOException $e) {//could not make database
      error_log('Database '.DB_NAME.' was unsuccessful: ' . $e->getMessage());
      exit;
    }
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);//connects to new database
    // Temporarily not enforcing foreign key constraints, only noting with "References"
    executeSQL($db,
      'CREATE TABLE User(
        userId int NOT NULL AUTO_INCREMENT,
        username VARCHAR(16) UNIQUE,
        password VARCHAR(128),
        date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX(username),

        PRIMARY KEY(userId)
      )
    ;');//stores each user as a row with relevent info

    // Other ideas for attributes include length, and url
    // May remove videoId in the future, as youtubeId is unique to the video already
    // If so, would make Index(youtubeId) instead of videoId
    executeSQL($db,
      'CREATE TABLE Video(
        videoId int NOT NULL AUTO_INCREMENT,
        youtubeId VARCHAR(11) UNIQUE,
        title VARCHAR(255),
        played BIT(1) DEFAULT 0,

        PRIMARY KEY(videoId),

        INDEX(videoId)
      )
    ;');//specific video, avoids popular selections bloating database

    executeSQL($db,
      'CREATE TABLE Party(
        partyId int NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        creatorId int REFERENCES User(userId),

        PRIMARY KEY(partyId)
      )
    ;');//each party has row right now there is only one

    // Index is on partyId, then rating. This will facilitate searching
    // a certain party for the highest rated video.
    executeSQL($db,
      'CREATE TABLE Submission(
        submissionId int NOT NULL AUTO_INCREMENT,
        videoId int REFERENCES Video(videoId),
        partyId int REFERENCES Party(partyId),
        submitterId int REFERENCES User(userId),
        upvotes int DEFAULT 0,
        downvotes int DEFAULT 0,
        rating int DEFAULT 0,
        wasPlayed BIT(1) DEFAULT 0,
        removed BIT(1) DEFAULT 0,

        INDEX(submissionId, wasPlayed, rating),

        PRIMARY KEY(submissionId)
      )
    ;');//individual actual submission

    // Index is on userId, then partyId. This will facilitate searching
    // a certain user for the highest rated video.
    executeSQL($db,
      'CREATE TABLE PartyUser(
        partyId int REFERENCES Party(partyId),
        userId int REFERENCES User(userId),
        owner BIT(1) DEFAULT 0,

        INDEX(partyId, userId),

        PRIMARY KEY(userId, partyId)
      )
    ;');//Relationship between user and party.  Shows that user has "joined" the party


    executeSQL($db,
      'CREATE TABLE Vote(
        voterId int REFERENCES User(userId),
        submissionId int REFERENCES Submission(submissionId),
        voteValue int,

        PRIMARY KEY(voterId, submissionId)
      )
    ;');//stores votes by users to songs
  }

  function executeSQL($db, $query){//runs a query with PDO's specific syntax
    try{
      $db->exec($query);
    }catch(PDOException $e){//something went wrong...
      error_log('Query failed: ' . $e->getMessage());
      exit;
    }
  }

  function isUser($db, $name){//checks for row in user table corresponding to username provided
    $name = sanitizeString($name);//prevents sql injection attempts
    $result = $db->prepare(
      'SELECT
        *
      FROM
        User
      WHERE
        username=:username
    ;');//performs check
    $result->bindValue(':username', $name);
    $result->execute();
    return ($result->rowCount()>0);//1 if exists
  }

  /*
  Returns 1 or 0 based on whether the user owns the party
  */
  function isPartyOwner($db, $partyId, $userId=0){
    if ($userId==0 && isset($_SESSION['userId'])){
      $userId = $_SESSION['userId'];
    }
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        PartyUser
      Where
        partyId=:partyId AND
        userId=:userId AND
        owner=1
    ;');//makes new row with given info
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':partyId', $partyId);
    $stmt->execute();
    return $stmt->rowCount()>0;
  }

  function createAccount($db, $args){//creates account with an array of user information given
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
      $username = sanitizeString($args['username']);
      $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
      if(isUser($db, $username)){//user already exists
        array_push($results['errors'], "username unavailable");
      } else {
        try {
          $stmt = $db->prepare(
            'INSERT INTO
              User(
                username,
                password
              )
            VALUES(
              :username,
              :password
            )
          ;');//makes new row with given info
          $stmt->bindValue(':username', $username);
          $stmt->bindValue(':password', $password);
          $stmt->execute();
          $results['status'] = "success";
        } catch (PDOException $e) {//something went wrong...
          error_log("Error: " . $e->getMessage());
          array_push($results['errors'], "database error");
        }
      }
    }else{
      array_push($results['errors'], "missing username or password");
    }
    return $results;
  }

  function logIn($db, $args){//sets session data if the user information matches a user's row
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
      $username = sanitizeString($args['username']);
      $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
      try{
        $stmt = $db->prepare(
          'SELECT
            *
          FROM
            User
          WHERE
            username=:username and
            password=:password
        ;');//checks for matching row
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $password);
        $stmt->execute();

        if($stmt->rowCount()==1){//if successfully logged in
          $result = $stmt->fetch(PDO::FETCH_OBJ);
          $_SESSION['username'] = $username;
          $_SESSION['userId'] = $result->userId;
          $_SESSION['logged'] = 1;
          $results['status'] = 'success';
          $results['token'] = session_id();
        }else{
          array_push($results['errors'], "bad username/password combination");
        }
      } catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }

    }else{
      array_push($results['errors'], "missing username or password");
    }
    return $results;
  }

  function addVideo($db, $args){
    $results = array("errors"=>array());
    if (is_array($args)&&array_key_exists("youtubeId", $args)&&array_key_exists("partyId", $args)){
      $youtubeId = sanitizeString($args['youtubeId']);
      $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$youtubeId.'&key='.API_SERVER_KEY;//url to verify data from youtube
      $verify = curl_init($url);//configures cURL with url
      curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);//don't echo returned info
      $verify = json_decode(curl_exec($verify));//returned data from youtube
      if($verify->pageInfo->totalResults==1){//verified to be a real video
        $title = sanitizeString($verify->items[0]->snippet->title);

        // Want to try to insert, but not change the videoId, and
        //   change LAST_INSERT_ID() to be the videoId of the inserted video
        try{
          $stmt = $db->prepare(
            'INSERT INTO
              Video(
                youtubeId,
                title
              )
            VALUES(
              :youtubeId,
              :title
            )
            ON
              DUPLICATE KEY
            UPDATE
              videoId = LAST_INSERT_ID(videoId)
          ;');
          $stmt->bindValue(':youtubeId', $youtubeId);
          $stmt->bindValue(':title', $title);
          // TODO: Add error checking for SQL execution:
          $stmt->execute();

          // Insert into Submissions.
          // LAST_INSERT_ID() returns id of last insertion's (or replace) auto-increment field
          //     First we'll get this working with just 1 party, partyId=1
          $partyId = sanitizeString($args['partyId']);
          $stmt = $db->prepare(
            'INSERT INTO
              Submission(
                videoId,
                partyId,
                submitterId
              )
            VALUES(
              LAST_INSERT_ID(),
              :partyId,
              :submitterId
            )
          ;');
          $stmt->bindValue(':submitterId', $_SESSION['userId']);
          $stmt->bindValue(':partyId', $partyId);
          $stmt->execute();
          $results['status'] = 'success';
        }catch (PDOException $e) {//something went wrong...
          error_log("Error: " . $e->getMessage());
          array_push($results['errors'], "database error");
        }
      }else{
        array_push($results['errors'], "could not verify youtubeId");
      }
    }else{
      array_push($results['errors'], "missing youtubeId or partyId");
    }
    return $results;
  }

  function listVideos($db, $args){
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("partyId", $args)){//valid array was given
      $userId = -1;
      if(isset($_SESSION['userId'])) $userId=sanitizeString($_SESSION['userId']);
      // Find all videos associated with partyId
      $partyId = sanitizeString($args['partyId']);
      try{
        $stmt = $db->prepare(
          'SELECT
            v.youtubeId,
            v.title,
            s.submissionId,
            u.username,
            (
              SELECT
                sum(voteValue)
              FROM
                Vote
              WHERE
                submissionId=s.submissionId
            ) as rating,
            (
              SELECT
                voteValue
              FROM
                Vote
              WHERE
                submissionId=s.submissionId AND
                voterId=:userId
            ) as userRating
          FROM
            Submission s,
            Video v,
            User u
          WHERE
            s.videoId = v.videoId AND
            s.partyId = :partyId AND
            s.wasPlayed=0 AND
            s.removed=0 AND
            s.submitterId = u.userId
          ORDER BY
            s.submissionId ASC
        ;');
        $stmt->bindValue(':userId', $userId);
        $stmt->bindValue(':partyId', $partyId);
        $stmt->execute();
        $results['videos']=array();
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)){//creates an array of the results to return
          array_push($results['videos'], $row);
        }
        $results['status']='success';
      }catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      array_push($results['errors'], "missing partyId");
    }
    return $results;
  }

  function getCurrentVideo($db, $args){
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("partyId", $args)){//valid array was given
      $userId = -1;
      if(isset($_SESSION['userId'])) $userId=sanitizeString($_SESSION['userId']);
      // Find all videos associated with partyId
      $partyId = sanitizeString($args['partyId']);
      try{
        $stmt = $db->prepare(
          'SELECT
            v.youtubeId,
            v.title,
            s.submissionId,
            u.username,
            (
              SELECT
                sum(voteValue)
              FROM
                Vote
              WHERE
                submissionId=s.submissionId
            ) as rating,
            (
              SELECT
                voteValue
              FROM
                Vote
              WHERE
                submissionId=s.submissionId and
                voterId=:userId
            ) as userRating
          FROM
            Submission s,
            Video v,
            User u
          WHERE
            s.videoId = v.videoId AND
            s.partyId = :partyId AND
            s.wasPlayed=0 AND
            s.removed=0 AND
            s.submitterId = u.userId
          ORDER BY
            s.submissionId ASC
          LIMIT 1
        ;');
        $stmt->bindValue(':userId', $userId);
        $stmt->bindValue(':partyId', $partyId);
        $stmt->execute();
        $results['video'] = $stmt->fetch(PDO::FETCH_OBJ);
        $results['status']='success';
      }catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      array_push($results['errors'], "missing partyId");
    }
    return $results;
  }

  function markVideoWatched($db, $args){//takes an array with the submission id of what to mark as watched
    $results = array("errors"=>array());
    if(isset($_SESSION['userId'])&&is_array($args)&&array_key_exists("submissionId", $args)){
      $submissionId = sanitizeString($args['submissionId']);
      try {
      $stmt = $db->prepare(
        'UPDATE
          Submission s,
          User u,
          Party p
        SET
          s.wasPlayed = 1
        WHERE
          s.submissionId = :submissionId AND
          s.partyId=p.partyId AND
          p.creatorId=u.userId AND
          u.userId=:userId
        ;');
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':userId', $_SESSION['userId']);
        $stmt->execute();
        $result['status'] = 'success';
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      array_push($results['errors'], "missing submissionId or not logged in");
    }
    return $results;
  }

  function removeVideo($db, $args){//takes an array of or argument with the submission id of what to mark as watched
    $results = array("errors"=>array());
    if(isset($_SESSION['userId'])&&is_array($args)&&array_key_exists("submissionId", $args)){
      $submissionId = sanitizeString($args['submissionId']);
      try {
      $stmt = $db->prepare(
        'UPDATE
          Submission s,
          User u,
          Party p
        SET
          s.removed = 1
        WHERE
          s.submissionId = :submissionId AND
          s.partyId=p.partyId AND
          p.creatorId=u.userId AND
          u.userId=:userId
        ;');
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':userId', $_SESSION['userId']);
        $stmt->execute();
        $results['status'] = 'success';
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      array_push($results['errors'], "missing submissionId or not logged in");
    }
    return $results;
  }

  /*
  Adds party by the username stored in session and title given
  */
  function createParty($db, $args){
    $results = array("errors"=>array());
    if (isset($_SESSION['userId']) && is_array($args) && array_key_exists("name", $args) && $args['name']!=''){
      $name = sanitizeString($args['name']);
      try{
        $stmt = $db->prepare(
          'INSERT INTO
            Party(
              name,
              creatorId
            )
          VALUES(
            :name,
            :creatorId
          )
        ;');
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':creatorId', $_SESSION['userId']);
        $stmt->execute();
        $partyId = $db->lastInsertId();
        $results = array_merge_recursive($results, joinParty($db, array("partyId"=>$partyId), 1));
        $results['status']='success';
        $results['partyId']=$partyId;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($_SESSION['userId'])){
        array_push($results['errors'], "must be logged in");
      }
      if(!array_key_exists("name", $args) || $args['name']==''){
        array_push($results['errors'], "party name was not specified");
      }
    }
    return $results;
  }

  /*
  Add the current user to the party specified
  */
  function joinParty($db, $args, $owner=0){
    $results = array("errors"=>array());
    if (isset($_SESSION['userId']) && is_array($args) && array_key_exists("partyId", $args)){
      $partyId = sanitizeString($args['partyId']);
      try{
        $stmt = $db->prepare(
          'INSERT INTO
            PartyUser(
              userId,
              partyId,
              owner
            )
          VALUES(
            :userId,
            :partyId,
            :owner
          )
        ;');//makes new row with given info
        $stmt->bindValue(':userId', $_SESSION['userId']);
        $stmt->bindValue(':partyId', $partyId);
        $stmt->bindValue(':owner', $owner, PDO::PARAM_BOOL);
        $stmt->execute();
        $results['status'] = "success";
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($_SESSION['userId'])){
        array_push($results['errors'], "must be logged in");
      }
      if(!array_key_exists("partyId", $args)){
        array_push($results['errors'], "partyId was not specified");
      }
    }
    return $results;
  }

  /*
  List the parties a user is in
  */
  function listJoinedParties($db, $args=0){
    $results = array("errors"=>array());
    if(isset($_SESSION['userId'])){
      try {
        $stmt = $db->prepare(
          'SELECT
            p.partyId,
            p.name,
            u.username
          FROM
            Party p,
            PartyUser pu,
            User u
          WHERE
            p.partyId = pu.partyId AND
            pu.userId = :userId AND
            p.creatorId = u.userId
        ;');
        $stmt->bindValue(':userId', $_SESSION['userId']);
        $stmt->execute();
        $results['parties'] = array();
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {//creates an array of the results to return
          array_push($results['parties'], $row);
        }
        $results['status'] = "success";
      } catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      array_push($results['errors'], "must be logged in");
    }
    return $results;
  }

  /*
  List parties a user hasn't joined
  */
  function listUnjoinedParties($db){
    $results = array("errors"=>array());
    $userId = -1;
    if(isset($_SESSION['userId'])){
      $userId = $_SESSION['userId'];
    }
    try {
      $stmt = $db->prepare(
        'SELECT
          p.partyId,
          p.name,
          u.username
        FROM
          Party p,
          User u
        WHERE
          p.creatorId=u.userId AND
          p.partyId NOT IN (
            SELECT
              partyId
            FROM
              PartyUser
            WHERE
              userId=:userId
          )
      ;');
      $stmt->bindValue(':userId', $userId);
      $stmt->execute();
      $results['parties'] = array();
      while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {//creates an array of the results to return
        array_push($results['parties'], $row);
      }
      $results['status'] = "success";
    } catch (PDOException $e) {//something went wrong...
      error_log("Error: " . $e->getMessage());
      array_push($results['errors'], "database error");
    }
    return $results;
  }


  function vote($db, $args){
    $results = array("errors"=>array());
    if (is_array($args)&&array_key_exists("submissionId", $args)&&array_key_exists("direction", $args)&&isset($_SESSION['userId'])){
      try {
        $voterId = sanitizeString($_SESSION['userId']);
        $submissionId = sanitizeString($args['submissionId']);
        $voteValue = intval(sanitizeString($args['direction']));
        if($voteValue>0)$voteValue=1;
        else if($voteValue<0)$voteValue=-1;
        $stmt = $db->prepare(
          'INSERT INTO
            Vote(
              voterId,
              submissionId,
              voteValue
            )
          VALUES(
            :voterId,
            :submissionId,
            :voteValue
          )
        ;');
        $stmt->bindValue(':voterId', $voterId);
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':voteValue', $voteValue);
        $stmt->execute();
        $results['status'] = "success";//was successful
      }catch(PDOException $e){//something went wrong...
        error_log('Query failed: ' . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($_SESSION['userId'])){
        array_push($results['errors'], "must be logged in");
      }
      if(!array_key_exists("submissionId", $args)){
        array_push($results['errors'], "submissionId was not specified");
      }
      if(!array_key_exists("direction", $args)){
        array_push($results['errors'], "direction was not specified");
      }
    }
    return $results;
  }
?>