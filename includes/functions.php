<?php
  /*
  Joshua Makinen
  */
  require_once("constants.php");//get system-specific variables
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  $db = connectDb();//connect to mysql

  $fb = new Facebook(array(
    'appId'  => FB_APP_ID,
    'secret' => FB_APP_SECRET,
  ));
  /*
  if(!isset($userData['userId']) && isset($_COOKIE['seriesId']) && isset($_COOKIE['token'])){
    checkSeriesTokenPair($db, sanitizeString($_COOKIE['seriesId']), sanitizeString($_COOKIE['token']));
  }
  */
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
        fbId BIGINT UNSIGNED UNIQUE,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(128),
        date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX(username, userId, fbId),

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
        thumbnail VARCHAR(255),
        description VARCHAR(255),
        played BIT(1) DEFAULT 0,

        PRIMARY KEY(videoId),

        INDEX(videoId)
      )
    ;');//specific video, avoids popular selections bloating database

    executeSQL($db,
      'CREATE TABLE Party(
        partyId int NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        passwordProtected BIT(1) DEFAULT 0,
        password VARCHAR(255),
        privacyId int DEFAULT 0,
        creatorId int REFERENCES User(userId),
        removed BIT(1) DEFAULT 0,

        PRIMARY KEY(partyId, name)
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
        started int DEFAULT 0,
        wasPlayed BIT(1) DEFAULT 0,
        removed BIT(1) DEFAULT 0,


        INDEX(submissionId, wasPlayed),

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
        unjoined BIT(1) DEFAULT 0,

        INDEX(partyId, userId),

        PRIMARY KEY(userId, partyId)
      )
    ;');//Relationship between user and party.  Shows that user has "joined" the party


    executeSQL($db,
      'CREATE TABLE Vote(
        voterId int REFERENCES User(userId),
        submissionId int REFERENCES Submission(submissionId),
        voteValue tinyint,

        PRIMARY KEY(voterId, submissionId)
      )
    ;');//stores votes by users to songs

    executeSQL($db,
      'CREATE TABLE Session(
        seriesId VARCHAR(128),
        userId int REFERENCES User(userId),
        sessionId VARCHAR(128),
        token VARCHAR(128),
        dateStarted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        loggedOut BIT(1) DEFAULT 0,
        PRIMARY KEY(seriesId, userId)
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
  function isPartyOwner($db, $userData, $partyId, $userId=0){
    if ($userId==0 && isset($userData['userId'])){
      $userId = $userData['userId'];
    }
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        PartyUser pu,
        Party p
      Where
        p.partyId=pu.partyId AND
        p.removed=0 AND
        pu.partyId=:partyId AND
        pu.userId=:userId AND
        pu.unjoined=0 AND
        pu.owner=1
    ;');//makes new row with given info
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':partyId', $partyId);
    $stmt->execute();
    return $stmt->rowCount()>0;
  }

  /*
  Returns party object with partyName, ownerId, and ownerUsername for a party with a given id
  */
  function getPartyObject($db, $partyId){
    $stmt = $db->prepare(
      'SELECT
        p.name as partyName,
        u.username as ownerUsername,
        u.userid as ownerId
      FROM Party p,
        PartyUser pu,
        User u
      WHERE
        p.partyid=:partyId  AND
        p.removed=0 AND
        pu.partyid=p.partyid AND
        pu.owner=1 AND
        pu.unjoined=0 AND
        u.userid=pu.userid
    ;');
    $stmt->bindValue(':partyId', $partyId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ);
  }

  /*
  Returns 1 or 0 based on whether the user has permission to write to the party
  */
  function canWriteParty($db, $userData, $partyId, $userId=-1){
    if ($userId==-1 && isset($userData['userId'])){
      $userId = $userData['userId'];
    }
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        PartyUser pu,
        Party p
      Where
        pu.partyId=p.partyId AND
        pu.unjoined=0 AND
        p.removed=0 AND
        p.partyId=:partyId AND
        (
          pu.userId=:userId
          OR
          p.privacyId>='.FULLY_PUBLIC.'
        )
    ;');
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':partyId', $partyId);
    $stmt->execute();
    return $stmt->rowCount()>0;
  }

  /*
  Returns 1 or 0 based on whether the user has permission to read a party
  */
  function canReadParty($db, $userData, $partyId, $userId=-1){
    if ($userId==-1 && isset($userData['userId'])){
      $userId = $userData['userId'];
    }
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        PartyUser pu,
        Party p
      Where
        pu.partyId=p.partyId AND
        pu.unjoined=0 AND
        p.removed=0 AND
        p.partyId=:partyId AND
        (
          pu.userId=:userId
          OR
          p.privacyId>='.VIEW_ONLY.'
        )
    ;');
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':partyId', $partyId);
    $stmt->execute();
    return $stmt->rowCount()>0;
  }

  /*
  Returns 1 or 0 based on whether the user must provide a password to join a party
  */
  function isPasswordProtected($db, $partyId){
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        Party p
      Where
        p.partyId=:partyId AND
        p.removed=0 AND
        p.passwordProtected
    ;');
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
          //startSeries($db, $result->userId);
          $results['status'] = 'success';
          $results['token'] = session_id();
          session_regenerate_id();
          $_SESSION['userId'] = $result->userId;
          $_SESSION['logged'] = 1;
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

  function addVideo($db, $userData, $args){
    $results = array("errors"=>array());
    if (is_array($args)&&array_key_exists("youtubeId", $args)&&array_key_exists("partyId", $args)){
      $userId=-1;
      if (isset($userData['userId'])){
        $userId=$userData['userId'];
      }
      $youtubeId = sanitizeString($args['youtubeId']);
      $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$youtubeId.'&key='.YT_API_SERVER_KEY;//url to verify data from youtube
      $verify = curl_init($url);//configures cURL with url
      curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);//don't echo returned info
      $verify = json_decode(curl_exec($verify));//returned data from youtube
      if($verify->pageInfo->totalResults==1){//verified to be a real video
        $title = sanitizeString($verify->items[0]->snippet->title);
        $thumbnail = sanitizeString($verify->items[0]->snippet->thumbnails->default->url);
        $description = sanitizeString($verify->items[0]->snippet->description);

        // Want to try to insert, but not change the videoId, and
        //   change LAST_INSERT_ID() to be the videoId of the inserted video
        try{
          $stmt = $db->prepare(
            'INSERT INTO
              Video(
                youtubeId,
                title,
                thumbnail,
                description
              )
            VALUES(
              :youtubeId,
              :title,
              :thumbnail,
              :description
            )
            ON
              DUPLICATE KEY
            UPDATE
              videoId = LAST_INSERT_ID(videoId)
          ;');
          $stmt->bindValue(':youtubeId', $youtubeId);
          $stmt->bindValue(':title', $title);
          $stmt->bindValue(':thumbnail', $thumbnail);
          $stmt->bindValue(':description', $description);
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
          $stmt->bindValue(':submitterId', $userId);
          $stmt->bindValue(':partyId', $partyId);
          $stmt->execute();
          vote($db, $userData, array('submissionId'=>$db->lastInsertId(), 'direction'=>1));
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

  function listVideos($db, $userData, $args){
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("partyId", $args)){//valid array was given
      $userId = -1;
      if(isset($userData['userId'])) $userId=sanitizeString($userData['userId']);
      // Find all videos associated with partyId
      $partyId = sanitizeString($args['partyId']);
      try{
        $stmt = $db->prepare(
          'SELECT
            v.youtubeId,
            v.title,
            v.thumbnail,
            s.submissionId,
            s.submitterId,
            u.username,
            s.started,
            IFNULL(
              (SELECT
                sum(voteValue)
              FROM
                Vote
              WHERE
                submissionId=s.submissionId
              ), 0
            ) as rating,
            IFNULL(
              (SELECT
                voteValue
              FROM
                Vote
              WHERE
                submissionId=s.submissionId AND
                voterId=:userId
              ), 0
            ) as userRating,
            (p.creatorId=:userId OR
             s.submitterId=:userId)as canRemove
          FROM
            Submission s,
            Video v,
            User u,
            Party p
          WHERE
            p.removed=0 AND
            s.videoId = v.videoId AND
            s.partyId = :partyId AND
            s.wasPlayed=0 AND
            s.removed=0 AND
            s.submitterId = u.userId AND
            p.partyId=s.partyId
          ORDER BY
            started DESC,
            rating DESC,
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

  function getCurrentVideo($db, $userData, $args){
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("partyId", $args)){//valid array was given
      $userId = -1;
      if(isset($userData['userId'])) $userId=sanitizeString($userData['userId']);
      // Find all videos associated with partyId
      $partyId = sanitizeString($args['partyId']);
      try{
        $stmt = $db->prepare(
          'SELECT
            v.youtubeId,
            v.title,
            v.thumbnail,
            v.description,
            s.submissionId,
            u.username,
            IFNULL(
              (SELECT
                sum(voteValue)
              FROM
                Vote
              WHERE
                submissionId=s.submissionId
              ), 0
            ) as rating,
            IFNULL(
              (SELECT
                voteValue
              FROM
                Vote
              WHERE
                submissionId=s.submissionId and
                voterId=:userId
              ), 0
            ) as userRating
          FROM
            Submission s,
            Video v,
            User u,
            Party p
          WHERE
            p.partyId=:partyId AND
            p.removed=0 AND
            s.videoId = v.videoId AND
            s.partyId = :partyId AND
            s.wasPlayed=0 AND
            s.removed=0 AND
            s.submitterId = u.userId
          ORDER BY
            started DESC,
            rating DESC,
            s.submissionId ASC
          LIMIT 1
        ;');
        $stmt->bindValue(':userId', $userId);
        $stmt->bindValue(':partyId', $partyId);
        $stmt->execute();
        $results['video'] = $stmt->fetch(PDO::FETCH_OBJ);
        if($results['video']){
          $stmt = $db->prepare(
            'UPDATE
              Submission
            SET
              started=1
            WHERE
              submissionId=:submissionId
          ;');
          $stmt->bindValue(':submissionId', $results['video']->submissionId);
          $stmt->execute();
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

  function markVideoWatched($db, $userData, $args){//takes an array with the submission id of what to mark as watched
    $results = array("errors"=>array());
    if(isset($userData['userId'])&&is_array($args)&&array_key_exists("submissionId", $args)){
      $submissionId = sanitizeString($args['submissionId']);
      try {
      $stmt = $db->prepare(
        'UPDATE
          Submission s,
          Party p,
          PartyUser pu
        SET
          s.wasPlayed = 1
        WHERE
          s.submissionId = :submissionId AND
          s.partyId=p.partyId AND
          p.removed=0 AND
          p.partyId=pu.partyId AND
          pu.unjoined=0 AND
          pu.userId=:userId
        ;');
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':userId', $userData['userId']);
        $stmt->execute();
        //sendToWebsocket(json_encode(array('action' =>'updateParty', 'submissionId' => $submissionId)));
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

  function removeVideo($db, $userData, $args){//takes an array of or argument with the submission id of what to mark as watched
    $results = array("errors"=>array());
    if(isset($userData['userId'])&&is_array($args)&&array_key_exists("submissionId", $args)){
      $submissionId = sanitizeString($args['submissionId']);
      try {
      $stmt = $db->prepare(
        'UPDATE
          Submission s,
          User u,
          Party p,
          PartyUser pu
        SET
          s.removed = 1
        WHERE
          s.submissionId = :submissionId AND
          s.partyId=p.partyId AND
          p.removed=0 AND
          (
            (
              p.partyId = pu.partyid AND
              pu.owner=1 AND
              pu.userId=u.userId
            ) OR
            s.submitterId=u.userId
          )AND
          u.userId=:userId
        ;');
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':userId', $userData['userId']);
        $stmt->execute();
        //sendToWebsocket(json_encode(array('action' =>'updateParty', 'submissionId' => $submissionId)));
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
  function createParty($db, $userData, $args){
    $results = array("errors"=>array());
    if (isset($userData['userId']) && is_array($args) && array_key_exists("name", $args) && $args['name']!=''){
      $name = sanitizeString($args['name']);
      $password = '';
      $passwordProtected = 0;
      if (array_key_exists("passwordProtected", $args) && $args['passwordProtected']){
        $passwordProtected = 1;
        if (array_key_exists("password", $args) && $args['password'] != ''){
          $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
        }else{
          $password = hash('sha512',PRE_SALT.POST_SALT);
        }
      }
      $privacyId = FULLY_PUBLIC;
      if (array_key_exists("privacy", $args)){
        $privacyId = $args['privacy'];
      }

      try{
        $stmt = $db->prepare(
          'SELECT
            *
          FROM
            Party
          WHERE
            name=:name
          ;');
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        if($stmt->rowCount()>0){
          $results['status']='failed';
          array_push($results['errors'], "Party name already exists");
        }else{
          $stmt = $db->prepare(
            'INSERT INTO
              Party(
                name,
                creatorId,
                passwordProtected,
                password,
                privacyId
              )
            VALUES(
              :name,
              :creatorId,
              :passwordProtected,
              :password,
              :privacyId
            )
          ;');
          $stmt->bindValue(':name', $name);
          $stmt->bindValue(':creatorId', $userData['userId']);
          $stmt->bindValue(':passwordProtected', $passwordProtected, PDO::PARAM_BOOL);
          $stmt->bindValue(':password', $password);
          $stmt->bindValue(':privacyId', $privacyId);
          $stmt->execute();
          $partyId = $db->lastInsertId();
          $results = array_merge_recursive($results, joinParty($db, $userData, array("partyId"=>$partyId, "password"=>$password), 1));
          $results['status']='success';
          $results['partyId']=$partyId;
        }
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($userData['userId'])){
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
  function joinParty($db, $userData, $args, $owner=0){
    $results = array("errors"=>array());
    if (isset($userData['userId']) && is_array($args) && array_key_exists("partyId", $args)){
      $partyId = sanitizeString($args['partyId']);
      $password = '';
      if (array_key_exists("password", $args)){
        $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
      }

      try{
        $stmt = $db->prepare(
          'SELECT
            *
          FROM
            Party
          WHERE
            partyId=:partyId AND
            removed=0 AND
            (
              passwordProtected=0 OR
              password=:password
            )
        ;');//checks for matching row
        $stmt->bindValue(':partyId', $partyId);
        $stmt->bindValue(':password', $password);
        $stmt->execute();
        if($stmt->rowCount()==1){//if successfully logged in
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
            ON
              DUPLICATE KEY
            UPDATE
              unjoined = 0
          ;');//makes new row with given info
          $stmt->bindValue(':userId', $userData['userId']);
          $stmt->bindValue(':partyId', $partyId);
          $stmt->bindValue(':owner', $owner, PDO::PARAM_BOOL);
          $stmt->execute();
          $results['status'] = "success";
        }else{
          array_push($results['errors'], "bad authentication");
        }
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($userData['userId'])){
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
  function listJoinedParties($db, $userData, $args=0){
    $results = array("errors"=>array());
    if(isset($userData['userId'])){
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
            p.removed=0 AND
            pu.unjoined=0 AND
            p.creatorId = u.userId
        ;');
        $stmt->bindValue(':userId', $userData['userId']);
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
  function listUnjoinedParties($db, $userData, $args=0){
    $results = array("errors"=>array());
    $userId = -1;
    if(isset($userData['userId'])){
      $userId = $userData['userId'];
    }
    try {
      $stmt = $db->prepare(
        'SELECT
          p.partyId,
          p.name,
          u.username,
          p.passwordProtected
        FROM
          Party p,
          User u
        WHERE
          p.creatorId=u.userId AND
          p.removed=0 AND
          p.partyId NOT IN (
            SELECT
              partyId
            FROM
              PartyUser
            WHERE
              userId=:userId AND
              unjoined=0
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


  function vote($db, $userData, $args){
    $results = array("errors"=>array());
    if (is_array($args)&&array_key_exists("submissionId", $args)&&array_key_exists("direction", $args)&&isset($userData['userId'])){
      try {
        $voterId = sanitizeString($userData['userId']);
        $submissionId = sanitizeString($args['submissionId']);
        $voteValue = intval(sanitizeString($args['direction']));
        if(canWriteParty($db, $userData, getPartyIdFromSubmission($db, $submissionId))){
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
            ON DUPLICATE KEY UPDATE
              voteValue = :voteValue
          ;');
          $stmt->bindValue(':voterId', $voterId);
          $stmt->bindValue(':submissionId', $submissionId);
          $stmt->bindValue(':voteValue', $voteValue);
          $stmt->execute();
          //sendToWebsocket(json_encode(array('action' =>'updateParty', 'submissionId' => $submissionId)));
          $results['status'] = "success";//was successful
        }else{
          array_push($results['errors'], "must join party");
        }
      }catch(PDOException $e){//something went wrong...
        error_log('Query failed: ' . $e->getMessage());
        array_push($results['errors'], "database error");
      }
    }else{
      if(!isset($userData['userId'])){
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

  function logOut($db, $sessionId=0){
    if($sessionId==0){
      $sessionId=session_id();
    }
    $results = array("errors"=>array());
    $oldId=session_id();
    session_write_close();
    session_id($sessionId);
    session_start();
    session_destroy();//leave no trace
    if($sessionId != $oldId){
      session_write_close();
      session_id($oldId);
      session_start();
    }
    /*
    $stmt = $db->prepare(
      'UPDATE
        Session
      SET
        loggedOut = 1
      WHERE
        sessionId=:sessionId
    ;');
    $stmt->bindValue(':sessionId', $sessionId);
    $stmt->execute();
    */
    $results['status'] = "success";//was successful
    return $results;
  }
/*
  function setSessionData($db, $userId){
    try{
      $stmt = $db->prepare(
        'SELECT
          *
        FROM
          User
        WHERE
          userId=:userId
      ;');//checks for matching row
      $stmt->bindValue(':userId', $userId);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_OBJ);
      $userData['username'] = $result->username;
      $userData['userId'] = $userId;
      $userData['logged'] = 1;
    } catch (PDOException $e) {//something went wrong...
      error_log("Error: " . $e->getMessage());
    }
  }

  function startSeries($db, $userId){
    $seriesId = substr(str_shuffle(md5(time())),0,128);
    setcookie('seriesId', $seriesId, time()+60*60*24*365);
    $seriesIdHash = hash('sha512', $seriesId);
    $stmt = $db->prepare(
      'INSERT INTO
        Session(
          seriesId,
          userId
        )
      VALUES(
        :seriesId,
        :userId
      );');
    $stmt->bindValue(':seriesId', $seriesIdHash);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
    startSeriesSession($db, $seriesId);
  }

  function checkSeriesTokenPair($db, $seriesId, $token){
    $seriesIdHash = hash('sha512', $seriesId);
    $tokenHash = hash('sha512', $token);
    $stmt = $db->prepare(
      'SELECT
        *
      FROM
        Session
      WHERE
        seriesId=:seriesId AND
        loggedOut=0
    ;');
    $stmt->bindValue(':seriesId', $seriesIdHash);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    if($result){
      if($result->token == $tokenHash) {
        startSeriesSession($db, $seriesId);
      }elseif($result->sessionId){
        session_write_close();
        session_id($result->sessionId);
        session_start();
        //session_destroy();
        session_write_close();
        session_start();
        session_regenerate_id();
        $stmt = $db->prepare(
          'UPDATE
            Session
          SET
            loggedOut = 1
          WHERE
            sessionId=:sessionId
        ;');
        $stmt->bindValue(':sessionId', $result->sessionId);
        $stmt->execute();
      }
    }
  }

  function startSeriesSession($db, $seriesId){
    try {
      $seriesIdHash = hash('sha512', $seriesId);
      $token = substr(str_shuffle(md5(time())),0,128);
      setcookie('token', $token, time()+60*60*24*365);
      $tokenHash = hash('sha512', $token);
      if(session_id()!=''){
        session_destroy();
      }
      $stmt = $db->prepare(
        'SELECT
          sessionId, userId
        FROM
          Session
        WHERE
          seriesId=:seriesId AND
          loggedOut=0
      ;');
      $stmt->bindValue(':seriesId', $seriesIdHash);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_OBJ);
      if($result->sessionId){
        session_write_close();
        session_id($result->sessionId);
        session_start();
        session_destroy();
        session_write_close();
      }
      session_start();
      session_regenerate_id();
      setSessionData($db, $result->userId);
      $stmt = $db->prepare(
        'UPDATE
          Session
        SET
          sessionId = :sessionId,
          token = :token
        WHERE
          seriesId=:seriesId AND
          loggedOut=0
      ;');
      $stmt->bindValue(':sessionId', session_id());
      $stmt->bindValue(':token', $tokenHash);
      $stmt->bindValue(':seriesId', $seriesIdHash);
      $stmt->execute();
    }catch(PDOException $e){//something went wrong...
      error_log('Query failed: ' . $e->getMessage());
    }
  }
*/
  function init($db, $fb){
    $userData=array();
    $fbId = $fb->getUser();
    if ($fbId) {
      try {
        // Proceed knowing you have a logged in user who's authenticated.
        $userProfile = $fb->api('/me');
      } catch (FacebookApiException $e) {
        error_log($e);
        $fbId = null;
      }
    }

    // Login or logout url will be needed depending on current user state.
    if ($fbId) {//logged into facebook
      $fb->setExtendedAccessToken();
      $userData['fbId']=$fbId;
      $stmt = $db->prepare(
        'SELECT
          *
        FROM
          User
        WHERE
          fbId=:fbId
      ;');
      $stmt->bindValue(':fbId', $fbId);
      $stmt->execute();
      if($stmt->rowCount()<1){//not already in db
        if(isset($_SESSION['userId'])){//associate facebook with menext
          $stmt = $db->prepare(
            'UPDATE
              User
            SET
              fbId=:fbId
            WHERE
              userId=:userId
            ;
            SELECT
              *
            FROM
              User
            WHERE
          ;');
          $stmt->bindValue(':fbId', $fbId);
          $stmt->bindValue(':userId', $_SESSION['userId']);
          $stmt->execute();
        }else{//add account to facebook
          $stmt = $db->prepare(
            'INSERT INTO
              User(
                username,
                fbId
              )
            VALUES(
              :username,
              :fbId
            )
          ;');
          $stmt->bindValue(':username', $userProfile['name']);
          $stmt->bindValue(':fbId', $fbId);
          $stmt->execute();
        }
        $stmt = $db->prepare(
          'SELECT
            *
          FROM
            User
          WHERE
            fbId=:fbId
        ;');
        $stmt->bindValue(':fbId', $fbId);
        $stmt->execute();
      }
    }elseif(isset($_SESSION['userId'])){//not logged into facebook but logged in with menext
      $stmt = $db->prepare(
        'SELECT
          *
        FROM
          User
        WHERE
          userId=:userId
      ;');
      $stmt->bindValue(':userId', $_SESSION['userId']);
      $stmt->execute();
    }else{
      return $userData;
    }
    if($stmt->rowCount()>0){
      $user = $stmt->fetch(PDO::FETCH_OBJ);
      $userData['username'] = $user->username;
      $userData['userId'] = $user->userId;
      $userData['logged'] = 1;
      return $userData;
    }
    return $userData;

  }

  function fbLogin($args){
    $results = array("errors"=>array());
    if(is_array($args)&&array_key_exists("accessToken", $args)){//valid array was given
      $fbToken=sanitizeString($args['accessToken']);
      $_SESSION["fb_".FB_APP_ID."_access_token"]=$fbToken;
      $results['status']='success';
    }else{
      array_push($results['errors'], "must have accessToken");
    }
    return $results;
  }

  function loginStatus($userData){
    $results = array("errors"=>array());
    if (isset($userData['logged'])&& $userData['logged']){
      $results['logged']=1;
      if (isset($userData['fbId'])){
        $results['fbId']=$userData['fbId'];
      }
      if (isset($userData['userId'])){
        $results['userId']=$userData['userId'];
      }
    }else{
      $results['logged']=0;
    }
    $results['status']="success";
    return $results;
  }

  function sendToWebsocket($message){
    $message.="\n";
    $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    socket_connect($socket, SOCK_LOC);
    socket_write($socket, $message, strlen($message));
    socket_close($socket);
  }

  function getPartyIdFromSubmission($db, $submissionId){
    $submissionId=sanitizeString($submissionId);
    $stmt = $db->prepare(
      'SELECT
        partyId
      FROM
        Submission
      WHERE
        submissionId=:submissionId
    ;');//makes new row with given info
    $stmt->bindValue(':submissionId', $submissionId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ)->partyId;
  }
?>
