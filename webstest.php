#! /usr/local/bin/php
<?php

require_once('websockets.php');

class echo_server extends WebSocketServer
{
    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    protected function process ($user, $message)
    {
        if($message == 'help')
        {
            $reply = 'Following commands are available - date, hi';
        }
        else if($message == 'date')
        {
            $reply = "Current date is " . date('Y-m-d H:i:s');
        }
        else if($message == 'hi')
        {
            $reply = "Hello user. This is a websocket server.";
        }
        else
        {
            $reply = "Thank you for the message : $message";
        }

        $this->send($user, $reply);

        //The uri component say /a/b/c
        echo "Requested resource : " . $user->requestedResource . "n";
    }

    /**
        This is run when socket connection is established. Send a greeting message
    */
    protected function connected ($user)
    {
        //Send welcome message to user
        $welcome_message = 'Hello. Welcome to the Websocket server. Type help to see what commands are available.';
        $this->send($user, $welcome_message);
    }

    /**
        This is where cleanup would go, in case the user had any sort of
        open files or other objects associated with them.  This runs after the socket
        has been closed, so there is no need to clean up the socket itself here.
    */
    protected function closed ($user)
    {
        echo "User closed connectionn";
    }
}

$host = '0.0.0.0';
$port = 9000;

$server = new echo_server($host , $port );