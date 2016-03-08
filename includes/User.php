<?php
  require_once("constants.php");//get system-specific variables
  require_once("functions.php");//various helpful functions used by most scripts
  require_once("Party.php");//Party Object
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  class User {
    public $fbId=0;
    public $username=-1;
    public $userId=-1;
    public $logged=0;
    private $db=null;
    private $fb=null;

    function __construct(PDO $db, $userId=-1){
      $this->db=$db;
      $this->userId = $userId;
    }

    /*
    This checks facebook login state and session state to determine the username, userId, and other data for the session's user.
    Also acts as logging in/creating account with facebook.

    Essentially this logs in the user accessing the site and sets this User object to be that user.
    */
    public function initAuth(Facebook $fb){
      $this->fb=$fb;

      $fbId = $this->fb->getUser();
      if ($fbId) {
        try {
          // Proceed knowing you have a logged in user who's authenticated.
          $userProfile = $this->fb->api('/me');
        } catch (FacebookApiException $e) {
          error_log($e);
          $fbId = null;
        }
      }
      // Login or logout url will be needed depending on current user state.
      if ($fbId) {//logged into facebook
        $this->initFb($fbId,1);
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
            $stmt->bindValue(':fbId', $fbId);
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
            $stmt->bindValue(':fbId', $fbId);
            $stmt->execute();
          }
          $this->initFb($fbId, 1);
        }
      }elseif(isset($_SESSION['userId'])){//not logged into facebook but logged in with menext
        $this->initMn($_SESSION['userId'], 1);
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
        $this->fbId = $fbId;
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
    Adds party by the username stored in session and title given
    */
    public function createParty($name, $password, $privacyId, array &$errors=array()){
      $passwordProtected=0;
      if ($password!=''){
        $passwordProtected = 1;
      }
      $passwordHash = hash('sha512',PRE_SALT.$password.POST_SALT);

      $privacyId=intval($privacyId);

      if ($privacyId>FULLY_PUBLIC){
        $privacyId = FULLY_PUBLIC;
      }elseif ($privacyId<FULLY_PRIVATE) {
        $privacyId=FULLY_PRIVATE;
      }

      try{
        $stmt = $this->db->prepare(
          'SELECT
            *
          FROM
            Party
          WHERE
            name=:name AND
            removed=0
          ;');
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        if($stmt->rowCount()>0){
          array_push($errors, "Party name already exists");
          return 0;
        }
        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':creatorId', $this->userId);
        $stmt->bindValue(':passwordProtected', $passwordProtected);
        $stmt->bindValue(':password', $passwordHash);
        $stmt->bindValue(':privacyId', $privacyId);
        $stmt->execute();
        $partyId = $this->db->lastInsertId();
        error_log($partyId);
        $this->joinParty($partyId, $password, 1, $errors);
        return $partyId;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
    }

    /*
    Add the current user to the party specified
    */
    public function joinParty($partyId, $password, $owner, array &$errors=array()){
      $password = hash('sha512',PRE_SALT.$password.POST_SALT);
      try{
        $stmt = $this->db->prepare(
          'SELECT
            passwordProtected,
            password
          FROM
            Party
          WHERE
            partyId=:partyId AND
            removed=0
        ;');//checks for matching row
        $stmt->bindValue(':partyId', $partyId);
        $stmt->execute();
        if($stmt->rowCount()!=1){//if successfully logged in
          array_push($errors, "bad partyId");
          return 0;
        }
        $party = $stmt->fetch(PDO::FETCH_OBJ);
        if($party->passwordProtected==1 and $party->password!=$password){
          array_push($errors, "bad party password");
          return 0;
        }

        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':userId', $this->userId);
        $stmt->bindValue(':partyId', $partyId);
        $stmt->bindValue(':owner', $owner);
        $stmt->execute();
        return $stmt->rowCount()>0;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
      return 0;
    }

    function unjoinParty($partyId, array &$errors=array()){
      $party = new Party($this->db, $partyId);
      if ($party->isPartyOwner($this)){
        array_push($errors, "party owner cannot unjoin.  Please delete the party instead.");
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
          'UPDATE
            PartyUser
          SET
            unjoined = 1
          WHERE
            partyId = :partyId AND
            userId = :userId
        ;');
        $stmt->bindValue(':partyId', $partyId);
        $stmt->bindValue(':userId', $this->userId);
        $stmt->execute();
        return $stmt->rowCount()>0;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;

      }
    }

    /*
    List the parties a user is in
    */
    public function listJoinedParties(array &$errors=array()){
      try {
        $stmt = $this->db->prepare(
          "SELECT
            p.partyId,
            p.name,
            u.username,
            concat('#',p.color) as color,
            pu.owner as isOwner
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
        ;");
        $stmt->bindValue(':userId', $this->userId);
        $stmt->execute();
        $parties = array();
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {//creates an array of the results to return
          array_push($parties, $row);
        }
        return $parties;
      } catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
      return 0;
    }

    /*
    List parties a user hasn't joined
    */
    public function listUnjoinedParties(array &$errors=array()){
      try {
        $stmt = $this->db->prepare(
          "SELECT
            p.partyId,
            p.name,
            u.username,
            p.passwordProtected,
            concat('#',p.color) as color

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
        ;");
        $stmt->bindValue(':userId', $this->userId);
        $stmt->execute();
        $parties = array();
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {//creates an array of the results to return
          array_push($parties, $row);
        }
        return $parties;
      } catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
      return 0;
    }
  }

?>
