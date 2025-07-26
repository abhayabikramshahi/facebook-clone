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
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!$userId || !in_array($type, ['followers', 'following'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid parameters']);
    exit;
}

try {
    $currentUserId = getCurrentUserId();
    
    if ($type === 'followers') {
        // Get followers
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.profile_picture, u.verification_status,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$currentUserId, $userId]);
    } else {
        // Get following
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.profile_picture, u.verification_status,
                   EXISTS(SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
            FROM follows f
            JOIN users u ON f.following_id = u.id
            WHERE f.follower_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$currentUserId, $userId]);
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'users' => $users
    ]);

} catch (PDOException $e) {
    error_log("Get follows error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred'
    ]);
} 