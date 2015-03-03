<?php
  require_once("constants.php");//get system-specific variables
  require_once("functions.php");//various helpful functions used by most scripts
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  class User {
    public $fbid=null;
    public $username=-1;
    public $userId=-1;
    private $logged=0;
    private $db=null;
    private $fb=null;

    public __contruct($db, $userId=-1){
      $this->db=$db;
    }

    /*
    This checks facebook login state and session state to determine the username, userId, and other data for the session's user.
    Also acts as logging in/creating account with facebook.

    Essentially this logs in the user accessing the site and sets this User object to be that user.
    */
    public function initAuth($fb){
      $this->fb=$fb;

      $this->fbId = $this->fb->getUser();
      if ($this->fbId) {
        try {
          // Proceed knowing you have a logged in user who's authenticated.
          $userProfile = $this->fb->api('/me');
        } catch (FacebookApiException $e) {
          error_log($e);
          $this->fbId = null;
        }
      }
      // Login or logout url will be needed depending on current user state.
      if ($this->fbId) {//logged into facebook
        $this->initFb($this->fbId,1);
        if(!$this->logged){//not already in db
          if(isset($_SESSION['userId'])){//associate facebook with menext
            $stmt = $this->db->prepare(
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
            $stmt->bindValue(':fbId', $this->fbId);
            $stmt->bindValue(':userId', $_SESSION['userId']);
            $stmt->execute();
          }else{//add account to facebook
            $stmt = $this->db->prepare(
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
            $stmt->bindValue(':fbId', $this->fbId);
            $stmt->execute();
          }
          $this->initFb($this->fbId, 1)
        }
      }elseif(isset($_SESSION['userId'])){//not logged into facebook but logged in with menext
        $this->initMn($_SESSION['userId'], 1)
      }
      return $this->logged;
    }

    /*
      this sets the User object to contain the user data associated with a given facebook id
      set's logged to 1 if set this means that if logged is 1, then it will mark that this user is the one making the call and they are logged in.
    */
    public function initFb($fbId, $logged=0){
        $stmt = $this->db->prepare(
          'SELECT
            *
          FROM
            User
          WHERE
            fbId=:fbId
        ;');
        $stmt->bindValue(':fbId', $fbId);
        $stmt->execute();
        if($stmt->rowCount()>0){
          $user = $stmt->fetch(PDO::FETCH_OBJ);
          $this->username = $user->username;
          $this->userId = $user->userId;
          if($logged){
            $this->logged = 1;
          }
        }else{
          $this->username="";
          $this->userId=-1;
          $this->fbId=0;
          $this->logged=0;
        }
    }

    /*
      this sets the User object to contain the user data associated with a given MeNext user id
      set's logged to 1 if set this means that if logged is 1, then it will mark that this user is the one making the call and they are logged in.
    */
    public function initMn($userId, $logged=0){
        $stmt = $this->db->prepare(
          'SELECT
            *
          FROM
            User
          WHERE
            userId=:userId
        ;');
        $stmt->bindValue(':userId', $userId);
        $stmt->execute();
        if($stmt->rowCount()>0){
          $user = $stmt->fetch(PDO::FETCH_OBJ);
          $this->username = $user->username;
          $this->userId = $user->userId;
          $this->fbId = $user->fbId;
          if($logged){
            $this->logged = 1;
          }
        }else{
          $this->username="";
          $this->userId=-1;
          $this->fbId=0;
          $this->logged=0;
        }
    }

    /*
    Takes an associative array with  username and password and creates a new user in the database with that information.
    TODO: make this take username and password as arguments. make a new function called something like handleCreateAccount that takes the http POST data and calls this function and does the error checking.
    -Vmutti
    */
    public function createAccount($username, $password, &$errors){//creates account with an array of user information given
      $password = hash('sha512',PRE_SALT.$password.POST_SALT);
      if($username==""||usernameRegistered($this->db, $username)){//user already exists
        array_push($errors, "username unavailable");
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
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

    public function logIn($username, $password, &$errors){//sets session data if the user information matches a user's row
      $password = hash('sha512',PRE_SALT.$password.POST_SALT);
      try{
        $stmt = $this->db->prepare(
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
    private function usernameRegistered($db, $username){
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



  }

?>