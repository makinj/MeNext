<?php

class WebSocketParty {

  public $partyId;
  public $subscribers = array();

  function __construct($partyId) {
    $this->partyId = $partyId;
  }

  public function subscribe($user){
    $this->subscribers[$user->id] = $user;
  }

  public function unsubscribe($userId){
    unset($this->subscribers[$userId]);
  }
}