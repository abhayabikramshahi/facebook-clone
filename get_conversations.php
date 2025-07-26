<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'error' => 'Unauthorized']);
    exit;
}

try {
    $currentUserId = getCurrentUserId();
    
    // Get all conversations with last message
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.id as user_id,
            u.username,
            u.profile_picture,
            u.verification_status,
            (
                SELECT message 
                FROM messages 
                WHERE (sender_id = ? AND receiver_id = u.id) 
                   OR (sender_id = u.id AND receiver_id = ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message,
            (
                SELECT created_at 
                FROM messages 
                WHERE (sender_id = ? AND receiver_id = u.id) 
                   OR (sender_id = u.id AND receiver_id = ?)
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message_time
        FROM messages m
        JOIN users u ON (
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id = u.id
                ELSE m.sender_id = u.id
            END
        )
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY last_message_time DESC
    ");
    
    $stmt->execute([
        $currentUserId, $currentUserId,
        $currentUserId, $currentUserId,
        $currentUserId,
        $currentUserId, $currentUserId
    ]);
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format timestamps
    foreach ($conversations as &$conv) {
        $conv['last_message_time'] = timeAgo($conv['last_message_time']);
    }
    
    echo json_encode([
        'status' => 'success',
        'conversations' => $conversations
    ]);

} catch (PDOException $e) {
    error_log("Get conversations error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred'
    ]);
} 