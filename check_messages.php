<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

// Get parameters
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$lastMessageId = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid user ID']);
    exit;
}

try {
    $currentUserId = getCurrentUserId();
    
    // Get new messages
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message, created_at
        FROM messages
        WHERE sender_id = ? AND receiver_id = ? AND id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $currentUserId, $lastMessageId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("
            UPDATE messages
            SET is_read = 1
            WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId, $currentUserId]);
    }
    
    // Format timestamps
    foreach ($messages as &$message) {
        $message['created_at'] = timeAgo($message['created_at']);
    }
    
    echo json_encode([
        'status' => 'success',
        'messages' => $messages
    ]);

} catch (PDOException $e) {
    error_log("Check messages error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred'
    ]);
} 