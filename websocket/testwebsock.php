#!/usr/bin/env php
<?php

require_once('./websockets.php');
require_once('../includes/functions.php');
class echoServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

  protected function process ($user, $message) {
    $request = json_decode($message);
    $result = "must have action selected";;
    if (is_array($request) && array_key_exists("action", $request)){
      $result = "could not complete action ".$request['action'];
      if ($request['action']=="setSession"){
        $result = "invalid session";
        if(array_key_exists("sessionId", $request)){
          session_id($request['sessionId']);
          session_start();
          $user->userData=init($db, $fb);
          session_write_close();
          if(array_key_exists("logged", $user->userData)&&$user->userData['logged']){
            $result = "logged in";
          }
        }
      }
    }
    $this->send($user, $result);
  }

  protected function connected ($user) {
    // Do nothing: This is just an echo server, there's no need to track the user.
    // However, if we did care about the users, we would probably have a cookie to
    // parse at this step, would be looking them up in permanent storage, etc.
  }

  protected function closed ($user) {
    // Do nothing: This is where cleanup would go, in case the user had any sort of
    // open files or other objects associated with them.  This runs after the socket
    // has been closed, so there is no need to clean up the socket itself here.
  }
}

//for ($i=0; $i < 1000; $i++) {
  //foreach ($echoserver->users as $user) {
    //$echoserver->send($user,$i);
  //}
  //echo $i;
  //sleep(1);
//}

$echoserver = new echoServer("0.0.0.0","9000");

try {
  $echoserver->run();
}
catch (Exception $e) {
  $echoserver->stdout($e->getMessage());
}

for ($i=0; $i < 1000; $i++) {
  foreach ($echoserver->users as $user) {
    $echoserver->send($user,$i);
  }
  echo $i;
  sleep(1);
}
