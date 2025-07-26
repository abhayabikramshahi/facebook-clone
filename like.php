<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check if photo_id is provided
if (!isset($_POST['photo_id'])) {
    echo json_encode(['success' => false, 'error' => 'Photo ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$photo_id = $_POST['photo_id'];

try {
    // Check if the photo exists
    $stmt = $pdo->prepare("SELECT id FROM photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Photo not found']);
        exit;
    }

    // Check if user already liked the photo
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND photo_id = ?");
    $stmt->execute([$user_id, $photo_id]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Unlike the photo
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND photo_id = ?");
        $stmt->execute([$user_id, $photo_id]);
        $liked = false;
    } else {
        // Like the photo
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, photo_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $photo_id]);
        $liked = true;

        // Create notification for the photo owner
        $stmt = $pdo->prepare("SELECT user_id FROM photos WHERE id = ?");
        $stmt->execute([$photo_id]);
        $photo_owner = $stmt->fetch();

        if ($photo_owner && $photo_owner['user_id'] != $user_id) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, photo_id) VALUES (?, ?, 'like', ?)");
            $stmt->execute([$photo_owner['user_id'], $user_id, $photo_id]);
        }
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE photo_id = ?");
    $stmt->execute([$photo_id]);
    $like_count = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'like_count' => $like_count
    ]);

} catch (PDOException $e) {
    error_log("Like error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?> 