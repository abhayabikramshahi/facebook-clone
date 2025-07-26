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

if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'User ID is required']);
    exit;
}

$followerId = getCurrentUserId();
$followingId = (int)$data['user_id'];

// Check if trying to follow self
if ($followerId === $followingId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Cannot follow yourself']);
    exit;
}

try {
    // Check if follow relationship exists
    $stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$followerId, $followingId]);
    $isFollowing = $stmt->fetchColumn() !== false;

    if ($isFollowing) {
        // Unfollow
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
        $isFollowing = false;
    } else {
        // Follow
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$followerId, $followingId]);
        $isFollowing = true;
    }

    echo json_encode([
        'status' => 'success',
        'is_following' => $isFollowing
    ]);

} catch (PDOException $e) {
    error_log("Follow error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
} 