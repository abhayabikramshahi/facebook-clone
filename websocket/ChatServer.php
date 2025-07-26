<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $users = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        if ($data->type === 'auth') {
            // Store user connection
            $this->users[$data->userId] = $from;
            echo "User {$data->userId} authenticated\n";
            return;
        }

        if ($data->type === 'message') {
            // Store message in database
            $this->storeMessage($data->senderId, $data->receiverId, $data->message);
            
            // Send to receiver if online
            if (isset($this->users[$data->receiverId])) {
                $this->users[$data->receiverId]->send(json_encode([
                    'type' => 'message',
                    'senderId' => $data->senderId,
                    'message' => $data->message,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
            }
            
            // Send confirmation to sender
            $from->send(json_encode([
                'type' => 'message_sent',
                'message' => $data->message,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Remove user from users array
        $userId = array_search($conn, $this->users);
        if ($userId !== false) {
            unset($this->users[$userId]);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function storeMessage($senderId, $receiverId, $message) {
        try {
            $pdo = new \PDO(
                "mysql:host=localhost;dbname=facebook;charset=utf8mb4",
                "root",
                "",
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$senderId, $receiverId, $message]);
            
            return $pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error storing message: " . $e->getMessage());
            return false;
        }
    }
} 