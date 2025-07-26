<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['receiver_id']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Missing required fields']);
    exit;
}

$receiverId = (int)$data['receiver_id'];
$message = trim($data['message']);

if (!$receiverId || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid message data']);
    exit;
}

try {
    $currentUserId = getCurrentUserId();
    
    // Check if receiver exists
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'error' => 'User not found']);
        exit;
    }
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$currentUserId, $receiverId, $message]);
    
    $messageId = $pdo->lastInsertId();
    
    // Get the created message
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, created_at
        FROM messages
        WHERE id = ?
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format timestamp
    $message['created_at'] = timeAgo($message['created_at']);
    
    echo json_encode([
        'status' => 'success',
        'message' => $message
    ]);

} catch (PDOException $e) {
    error_log("Send message error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred'
    ]);
} 