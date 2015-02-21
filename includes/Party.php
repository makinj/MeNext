<?php
  require_once("constants.php");//get system-specific variables
  require_once("functions.php");//various helpful functions used by most scripts
  require_once(dirname(__FILE__).'/../sdks/facebook.php');//facebook sdk

  class User {
    private $db=null;
    private $partyId=-1;

    public __contruct($db, $partyId=-1){
      $this->db=$db;
      $this->partyId=$partyId;
    }

    /*
    Returns 1 or 0 based on whether the user owns the party
    -Vmutti
    */
    public function isPartyOwner($userId){
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
      $stmt->bindValue(':userId', $userId);
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns 1 or 0 based on whether the user must provide a password to join a party
    -Vmutti
    */
    function isPasswordProtected(){
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
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns party object with partyName, ownerId, and ownerUsername for a party with a given id
    -Vmutti
    */
    function getPartyObject(){
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
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /*
    Returns 1 or 0 based on whether the user has permission to write to the party
    -Vmutti
    */
    function canWriteParty($userId){
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
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
    }

    /*
    Returns 1 or 0 based on whether the user has permission to read a party
    -Vmutti
    */
    function canReadParty($userId){
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
      $stmt->bindValue(':partyId', $this->partyId);
      $stmt->execute();
      return $stmt->rowCount()>0;
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

  }

?>