#!/usr/bin/env php
<?php
ini_set('session.use_cookies', 0);
session_cache_limiter(false);
require_once('websocket/websockets.php');
require_once('includes/functions.php');

class echoServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

  protected function process ($user, $message) {

    $results = array();//array to be returned to client
    if(is_array($message)){
      $request=$message;
    }else{
      $request = json_decode($message, $assoc=true);
    }
    if (is_array($request) && array_key_exists("action", $request)){
      if($request['action']=="setSession"){
        $results = array_merge_recursive($results, $this->setUserData($user, $request));
      }else if($request['action']=="subscribeParty"){
        $results = array_merge_recursive($results, $this->subscribeParty($user, $request));
        $results = array_merge_recursive($results, listVideos($this->db, $user->userData, $request));
      }else{
        array_push($results['errors'], "invalid action");
      }
    }else{
      array_push($results['errors'], "must have action selected");
    }

    $finalStatus = 'failed';
    if(array_key_exists("status", $results)){
      if(is_array($results['status'])){
        $finalStatus = 'success';
        foreach ($results['status'] as $status){
          if($status != "success"){
            $finalStatus = 'failed';
            break;
          }
        }
      }elseif($results['status']=='success'){
        $finalStatus = 'success';
      }
    }

    if(array_key_exists("errors", $results) && count($results['errors'])==0){
      unset($results['errors']);
    }else{
      $finalStatus = 'failed';
    }
    $results['status'] = $finalStatus;

    $this->send($user, json_encode($results));
  }

  protected function setUserData($user, $request) {
    $results = array("errors"=>array());
    $results['responseType'] = "loggedIn";
    if(array_key_exists("sessionId", $request)){
      if(session_id() != '') {
        session_write_close();
      }
      session_id($request['sessionId']);
      session_start();
      $user->setUserData(init($this->db, $this->fb));
      session_abort();
      if(array_key_exists("logged", $user->userData)&&$user->userData['logged']){
        $results['status'] = "success";
      }else{
        array_push($results['errors'], "invalid session");
      }
    }else{
      array_push($results['errors'], "must have sessionId");
    }
    return $results;
  }

  protected function subscribeParty($user, $request){
    $results = array("errors"=>array());
    $results['responseType'] = "subscribed";
    if(array_key_exists("partyId", $request)){
      $partyId=sanitizeString($request['partyId']);
      if(canReadParty($this->db, $user->userData, $partyId)){

        if(!isset($this->parties[$partyId])){

          $this->parties[$partyId]= new $this->partyClass($partyId);
        }
        if ($user->partyId>0){
          $this->parties[$user->partyId]->unsubscribe($user->id);
          if (count($this->parties[$user->partyId]->subscribers)>0){
            unset($this->parties[$user->partyId]);
          }
        }
        $user->setParty($partyId);
        $this->parties[$partyId]->subscribe($user);
      }else{
        array_push($results['errors'], "must join party");
      }
    }else{
      array_push($results['errors'], "must have partyId");
    }

    return $results;
  }

  protected function handleApache($message){
    if(is_array($message)){
      $request=$message;
    }else{
      $request = json_decode($message, $assoc=true);
    }
    if (is_array($request) && array_key_exists("action", $request)){
      if($request['action']=="updateParty"){
        $partyId=-1;
        if(array_key_exists('submissionId', $request)){
          $partyId=getPartyIdFromSubmission($this->db, $request['submissionId']);
        }
        if(isset($this->parties[$partyId])){
          $this->broadcastParty($this->parties[$partyId]);
        }
      }
    }
  }

  protected function broadcastParty($party) {
    foreach ($party->subscribers as $user) {
      $partyData=listVideos($this->db, $user->userData, array('partyId' => $party->partyId));
      $partyData['responseType'] = "videoList";
      $this->send($user, json_encode($partyData));
    }
  }

  protected function connected ($user) {
    // Do nothing: This is just an echo server, there's no need to track the user.
    // However, if we did care about the users, we would probably have a cookie to
    // parse at this step, would be looking them up in permanent storage, etc.
  }

  protected function closed ($user) {
    $partyId=$user->partyId;
    if ($partyId>0){
      $this->parties[$partyId]->unsubscribe($user->id);
      if (count($this->parties[$partyId]->subscribers)>0){
        unset($this->parties[$partyId]);
      }
    }
  }
}

//for ($i=0; $i < 1000; $i++) {
  //foreach ($echoserver->users as $user) {
    //$echoserver->send($user,$i);
  //}
  //echo $i;
  //sleep(1);
//}
$echoserver = new echoServer("0.0.0.0","9000", $db, $fb, SOCK_LOC);

try {
  $echoserver->run();
}
catch (Exception $e) {
  $echoserver->stdout($e->getMessage());
}
