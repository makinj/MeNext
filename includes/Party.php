<?php
  require_once("constants.php");//get system-specific variables
  require_once("functions.php");//various helpful functions used by most scripts
  require_once("User.php");//User object
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  class Party {
    private $db=null;
    private $partyId=-1;

    public function __construct(PDO $db, $partyId=-1){
      $this->db=$db;
      $this->partyId=$partyId;
    }

    public function initFromSubmissionId($submissionId){
      $submissionId=sanitizeString($submissionId);
      $stmt = $this->db->prepare(
        'SELECT
          partyId
        FROM
          Submission
        WHERE
          submissionId=:submissionId
      ;');//makes new row with given info
      $stmt->bindValue(':submissionId', $submissionId);
      $stmt->execute();
      $this->partyId= $stmt->fetch(PDO::FETCH_OBJ)->partyId;
    }

    /*
    Returns 1 or 0 based on whether the user owns the party
    -Vmutti
    */
    public function isPartyOwner(User $user){
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
      ;');
      $stmt->bindValue(':userId', $user->userId);
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns 1 or 0 based on whether the user must provide a password to join a party
    -Vmutti
    */
    public function isPasswordProtected(){
      $stmt = $this->db->prepare(
        'SELECT
          *
        FROM
          Party p
        Where
          p.partyId=:partyId AND
          p.removed=0 AND
          p.passwordProtected
      ;');
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns party object with partyName, ownerId, and ownerUsername for a party with a given id
    -Vmutti
    */
    public function getPartyObject($userId=-1){
      $stmt = $this->db->prepare(
        "SELECT
          p.name as partyName,
          u.username as ownerUsername,
          u.userid as ownerId,
          concat('#',p.color) as color,
          pu.userId=:userId as isOwner
        FROM
          Party p,
          PartyUser pu,
          User u
        WHERE
          p.partyid=:partyId  AND
          p.removed=0 AND
          pu.partyid=p.partyid AND
          pu.owner=1 AND
          pu.unjoined=0 AND
          u.userid=pu.userid
      ;");
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->bindValue(':userId', $userId);
      $stmt->execute();
      return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /*
    Returns 1 or 0 based on whether the user has permission to write to the party
    -Vmutti
    */
    public function canWriteParty(User $user){
      $stmt = $this->db->prepare(
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
      $stmt->bindValue(':userId', $user->userId);
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns 1 or 0 based on whether the video has already been submitted
    -Vmutti
    */
    public function isQueued($youtubeId){
      $stmt = $this->db->prepare(
        'SELECT
          *
        FROM
          Video v,
          Submission s
        Where
          v.youtubeId=:youtubeId AND
          s.videoId=v.videoId AND
          s.partyId=:partyId AND
          s.wasPlayed=0 AND
          s.removed=0
      ;');
      $stmt->bindValue(':youtubeId', $youtubeId);
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns 1 or 0 based on whether the user has permission to read a party
    -Vmutti
    */
    public function canReadParty(User $user){
      $stmt = $this->db->prepare(
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
      $stmt->bindValue(':userId', $user->userId);
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    public function addVideo(User $user, $youtubeId, array &$errors=array()){
      if(!$this->canWriteParty($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }

      if($this->isQueued($youtubeId)){
        array_push($errors, "Video is already queued");
        return 0;
      }

      $url= 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$youtubeId.'&key='.YT_API_SERVER_KEY;//url to verify data from youtube
      $verify = curl_init($url);//configures cURL with url
      curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);//don't echo returned info
      $verify = json_decode(curl_exec($verify));//returned data from youtube

      if($verify->pageInfo->totalResults!=1){//not verified to be a real video
        array_push($errors, "could not verify youtubeId");
        return 0;
      }

      $title = sanitizeString($verify->items[0]->snippet->title);
      $thumbnail = sanitizeString($verify->items[0]->snippet->thumbnails->default->url);
      $description = sanitizeString($verify->items[0]->snippet->description);
      // Want to try to insert, but not change the videoId, and
      //   change LAST_INSERT_ID() to be the videoId of the inserted video
      try{
        $stmt = $this->db->prepare(
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
        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':submitterId', $user->userId);
        $stmt->bindValue(':partyId', $this->partyId);
        $stmt->execute();
        if($this->vote($user, $this->db->lastInsertId(), 1, $errors)){
          return 1;
        }
      }catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
      return 0;
    }

    public function listVideos(User $user, array &$errors=array()){
      if(!$this->canReadParty($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      try{
        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':userId', $user->userId);
        $stmt->bindValue(':partyId', $this->partyId);
        $stmt->execute();
        $videos=array();
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)){//creates an array of the results to return
          array_push($videos, $row);
        }
        return $videos;
      }catch (PDOException $e) {//something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
      return 0;
    }


    function getCurrentVideo(User $user, array &$errors=array()){
      $videos = $this->listVideos($user, $errors);
      if (!$videos){
        return 0;
      }
      if (count($videos)){
        $video=$videos[0];
        try{
          $stmt = $this->db->prepare(
            'UPDATE
              Submission
            SET
              started=1
            WHERE
              submissionId=:submissionId
          ;');
          $stmt->bindValue(':submissionId', $video->submissionId);
          $stmt->execute();
          return $video;
        }catch (PDOException $e) {//something went wrong...
          error_log("Error: " . $e->getMessage());
          array_push($errors, ERROR_DB);
          return 0;
        }
      }else{
        return false;//no video, return false
      }
      return 0;
    }




    function markVideoWatched(User $user, $submissionId, array &$errors=array()){//takes an array with the submission id of what to mark as watched
      if (!$this->isPartyOwner($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
          'UPDATE
            Submission s
          SET
            s.wasPlayed = 1
          WHERE
            s.submissionId = :submissionId
        ;');
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->execute();
        return $stmt->rowCount()>0;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;

      }
    }


    function removeVideo(User $user, $submissionId, array &$errors=array()){//takes an array of or argument with the submission id of what to mark as watched
      if (!$this->canWriteParty($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':userId', $user->userId);
        $stmt->execute();
        return $stmt->rowCount()>0;
        //sendToWebsocket(json_encode(array('action' =>'updateParty', 'submissionId' => $submissionId)));
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, "database error");
        return 0;

      }
      return 0;
    }

    function vote(User $user, $submissionId, $voteValue, array &$errors=array()){
      if (!$this->canWriteParty($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      if($voteValue>0){
        $voteValue=1;
      }elseif($voteValue<0){
        $voteValue=-1;
      }
      try {
        $stmt = $this->db->prepare(
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
        $stmt->bindValue(':voterId', $user->userId);
        $stmt->bindValue(':submissionId', $submissionId);
        $stmt->bindValue(':voteValue', $voteValue);
        $stmt->execute();
        return $stmt->rowCount()>0;
      }catch(PDOException $e){//something went wrong...
        error_log('Query failed: ' . $e->getMessage());
        array_push($errors, "database error");
        return 0;
      }
      return 0;
    }
    function deleteParty(User $user, array &$errors=array()){
      if (!$this->isPartyOwner($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
          'UPDATE
            Party
          SET
            removed = 1
          WHERE
            partyId = :partyId
        ;');
        $stmt->bindValue(':partyId', $this->partyId);
        $stmt->execute();
        return $stmt->rowCount()>0;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
    }

    function updateName(User $user, $partyName, array &$errors=array()){
      if (!$this->isPartyOwner($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return 0;
      }
      try {
        $stmt = $this->db->prepare(
          'SELECT
            *
          FROM
            Party
          WHERE
            name=:partyName AND
            removed=0
          ;');
        $stmt->bindValue(':partyName', $partyName);
        $stmt->execute();
        if($stmt->rowCount()>0){
          array_push($errors, "Party name already exists");
          return 0;
        }
        $stmt = $this->db->prepare(
          'UPDATE
            Party
          SET
            name = :partyName
          WHERE
            partyId = :partyId
        ;');
        $stmt->bindValue(':partyId', $this->partyId);
        $stmt->bindValue(':partyName', $partyName);
        $stmt->execute();
        return $stmt->rowCount()>0;
      } catch (PDOException $e) {
        //something went wrong...
        error_log("Error: " . $e->getMessage());
        array_push($errors, ERROR_DB);
        return 0;
      }
    }

    function updateParty(User $user, array$changes, array &$errors=array()){
      $updated = array();
      if(!$this->isPartyOwner($user)){
        array_push($errors, ERROR_PERMISSIONS);
        return $updated;
      }
      if(!is_array($changes)){
        array_push($errors, "Server error while updating party");
        return $updated;
      }

      foreach ($changes as $property => $propertyValue) {
        if($propertyValue!=''){
          array_push($updated, $property);
          switch ($property) {
            case 'name':
              if(!$this->updateName($user, $propertyValue, $errors)){
                array_pop($updated);
              }
              break;

            default:
              array_pop($updated);
              break;
          }
        }
      }
      return $updated;
    }

  }

?>
