<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Make sure composer dependencies have been installed
require __DIR__ . '/vendor/autoload.php';

class PartySubscription implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "connected";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from != $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}
// Run the server application through the WebSocket protocol on port 8080
    $app = new Ratchet\App('localhost', 8080);
    $app->route('/partyUpdates', new Chat);
    $app->run();
?>
