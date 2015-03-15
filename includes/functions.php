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

  function sanitizeInputs($inputs){
    $result = array();
    foreach ($inputs as $key => $value) {
      $result[sanitizeString($key)]=sanitizeString($value);
    }
    return $result;
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
      "CREATE TABLE Party(
        partyId int NOT NULL AUTO_INCREMENT,
        name VARCHAR(255),
        passwordProtected BIT(1) DEFAULT 0,
        password VARCHAR(255),
        privacyId int DEFAULT 0,
        creatorId int REFERENCES User(userId),
        removed BIT(1) DEFAULT 0,
        date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        color CHAR(6) DEFAULT 'EB2735',

        PRIMARY KEY(partyId, name)
      )
    ;");//each party has row

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
        date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

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
        date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

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

  /*
  Takes an associative array with  username and password and creates a new user in the database with that information.
  TODO: make this take username and password as arguments. make a new function called something like handleCreateAccount that takes the http POST data and calls this function and does the error checking.
  -Vmutti
  */
  function createAccount($db, $username, $password, &$errors=array()){//creates account with an array of user information given
    $password = hash('sha512',PRE_SALT.$password.POST_SALT);
    if($username==""||usernameRegistered($db, $username)){//user already exists
      array_push($errors, "username unavailable");
      return 0;
    }
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
      return 1;
    } catch (PDOException $e) {//something went wrong...
      error_log("Error: " . $e->getMessage());
      array_push($errors, "database error");
      return 0;
    }
  }

  function logIn($db, $username, $password, &$errors=array()){//sets session data if the user information matches a user's row
    $password = hash('sha512',PRE_SALT.$password.POST_SALT);
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

      if($stmt->rowCount()!=1){//if successfully logged in
        array_push($errors, "bad username/password combination");
        return 0;
      }

      $result = $stmt->fetch(PDO::FETCH_OBJ);
      session_regenerate_id();
      $_SESSION['userId'] = $result->userId;
      $_SESSION['logged'] = 1;
    } catch (PDOException $e) {//something went wrong...
      error_log("Error: " . $e->getMessage());
      array_push($errors, "database error");
      return 0;
    }
    return 1;
  }

  /*
  checks for row in user table corresponding to username provided
  aka. checks to see if username is already taken
  -Vmutti
  */
  function usernameRegistered($db, $username){
    $result = $db->prepare(
      'SELECT
        *
      FROM
        User
      WHERE
        username=:username
    ;');//performs check
    $result->bindValue(':username', $username);
    $result->execute();
    return ($result->rowCount()>0);//1 if exists
  }

  function logOut(){
    session_write_close();
    session_start();
    session_destroy();//leave no trace
    return 1;
  }
  function fbLogin($accessToken){
    $_SESSION["fb_".FB_APP_ID."_access_token"]=$accessToken;
    return 1;
  }

  function loginStatus($user){
    $results = array();
    if ($user->logged){
      $results['logged']=1;
      if ($user->fbId){
        $results['fbId']=$user->fbId;
      }
      if ($user->userId>-1){
        $results['userId']=$user->userId;
      }
    }else{
      $results['logged']=0;
    }
    return $results;
  }

  function checkRequiredParameters($parameters, $required, &$errors=array()){
    foreach ($required as $parameter){
      if(!isset($parameters[$parameter])){
        array_push($errors, "this action requires parameter(s): ".implode(",", $required));
        return 0;
      }
    }
    return 1;
  }

?>
