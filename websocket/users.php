<?php

class WebSocketUser {

  public $socket;
  public $id;
  public $headers = array();
  public $handshake = false;

  public $handlingPartialPacket = false;
  public $partialBuffer = "";

  public $sendingContinuous = false;
  public $partialMessage = "";

  public $hasSentClose = false;
  public $userData = array();
  public $partyId = -1;

  function __construct($id, $socket) {
    $this->id = $id;
    $this->socket = $socket;
  }

  public function setParty($partyId){
    $this->partyId = $partyId;
  }

  public function setUserData($userData){
    $this->userData = $userData;
  }
}