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
    */
    public function init($fb){
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
      $stmt=null;
      // Login or logout url will be needed depending on current user state.
      if ($this->fbId) {//logged into facebook
        $stmt = $this->db->prepare(//check if user exists in MeNext db
          'SELECT
            *
          FROM
            User
          WHERE
            fbId=:fbId
        ;');
        $stmt->bindValue(':fbId', $this->fbId);
        $stmt->execute();
        if($stmt->rowCount()<1){//not already in db
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
          $stmt = $this->db->prepare(
            'SELECT
              *
            FROM
              User
            WHERE
              fbId=:fbId
          ;');
          $stmt->bindValue(':fbId', $this->fbId);
          $stmt->execute();
        }
      }elseif(isset($_SESSION['userId'])){//not logged into facebook but logged in with menext
        $stmt = $this->db->prepare(
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
        return 0;
      }
      if($stmt->rowCount()>0){
        $this->user = $stmt->fetch(PDO::FETCH_OBJ);
        $this->username = $user->username;
        $this->userId = $user->userId;
        $this->logged = 1;
      }
      return 1;
    }

    /*
    Takes an associative array with  username and password and creates a new user in the database with that information.
    TODO: make this take username and password as arguments. make a new function called something like handleCreateAccount that takes the http POST data and calls this function and does the error checking.
    -Vmutti
    */
    public function createAccount($args){//creates account with an array of user information given
      $results = array("errors"=>array());
      if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
        $username = sanitizeString($args['username']);
        $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
        if(usernameRegistered($this->db, $username)){//user already exists
          array_push($results['errors'], "username unavailable");
        } else {
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

    public function logIn($args){//sets session data if the user information matches a user's row
      $results = array("errors"=>array());
      if(is_array($args)&&array_key_exists("username", $args)&&array_key_exists("password", $args)){//valid array was given
        $username = sanitizeString($args['username']);
        $password = hash('sha512',PRE_SALT.sanitizeString($args['password']).POST_SALT);
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

          if($stmt->rowCount()==1){//if successfully logged in
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            //startSeries($this->db, $result->userId);
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

    /*
    Returns 1 or 0 based on whether the user owns the party
    -Vmutti
    */
    public function isPartyOwner($partyId, $userId=-1){
      if ($userId==-1){
        $userId = $userData['userId'];
      }
      $stmt = $this->db->prepare(
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

  }

?>