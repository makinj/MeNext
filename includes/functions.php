<?php
  /*
  Joshua Makinen(Vmutti)
  */

  /*
  This block includes files needed to run the functions and sets up db and fb variables needed by basically everything
  Sincerely,
  Vmutti
  */
  require_once("constants.php");//get system-specific variables
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  $db = connectDb();//connect to mysql


  $fb = new Facebook(array(//fb setup
    'appId'  => FB_APP_ID,
    'secret' => FB_APP_SECRET,
  ));

  /*
  This removes html tags, html entities, slashes, and leading and trailing whitespace
  The main purpose of this function is to thoroughly clean up user input.
  There is no excuse at this point to allow users to put html of any sort into our database.
  This stops stored XSS attacks for the most parts and adds more difficulty to sql injection.
  If you have any questions about this check out https://www.owasp.org/index.php/Cross-site_Scripting_%28XSS%29
  NOTE: USE THIS FUNCTION ON ANY USER INPUT
  ONE MORE TIME: WHEN IN DOUBT USE IT
  SERIOUSLY: I WILL NOT BE COOL IF YOU INTRODUCE A SECURITY VULNERABILITY TO THIS SYSTEM BY NOT USING THIS
  Yours truly,
  Vmutti
  */
  function sanitizeString($string){
    $string = strip_tags($string);
    $string = htmlentities($string);
    $string = trim($string);
    return stripslashes($string);
  }

  /*
  This is a kind of clever function for making setup super easy.
  It attempts to connect to a db and if it can't it creates the db using the setupDb function.
  this means that if you are installing this on a new server then all you need to do is call this
  -Vmutti
  */
  function connectDb(){
    require_once("constants.php");//get system-specific variables
    $db=0;
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME;//string to connect to database
    try{
      $db = new PDO($dsn, DB_USER, DB_PASS);
    }catch(PDOException $e){//connection failed, set up a new database
      $db = setupDb();
    }
    return $db;
  }

  /*
  Creates the database in mysql for MeNext
  Any changes made here should actually be implemented by hand on any server to prevent errors or loss of data.
  If you really want to just wipe the server and lose all of the user data(you probably shouldn't in production) you can do that and just run this function again
  TODO: Make a function that checks whether the database has all of the right tables setup the right way and fixes them if it can.  This would prevent errors and loss of data when this function is changed.  It would be kindof like the schema_sync utility at vmutti's previous job
  -Vmutti
  */
  function setupDb(){//creates the database needed to run the application
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
    ;');//each party has row

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

        PRIMARY KEY(partyId, submissionId)
      )
    ;');//individual actual submission

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
  }

  /*
  Executes a mysql query
  NOTE: Mostly deprecated, only used in setup(due to being a very small function that is only really helpful there)
  -Vmutti
  */
  function executeSQL($db, $query){//runs a query with PDO's specific syntax
    try{
      $db->exec($query);
    }catch(PDOException $e){//something went wrong...
      error_log('Query failed: ' . $e->getMessage());
      exit;
    }
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

  function getPartyIdFromSubmission($submissionId){
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
