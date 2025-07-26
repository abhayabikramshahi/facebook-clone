<?php
require_once 'config/database.php';
require_once 'session.php';

function createNotification($userId, $actorId, $type, $photoId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, photo_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$userId, $actorId, $type, $photoId]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting unread notification count: " . $e->getMessage());
        return 0;
    }
}

function markNotificationsAsRead($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Error marking notifications as read: " . $e->getMessage());
        return false;
    }
}

// AJAX endpoint for getting unread notification count
if (isset($_GET['action']) && $_GET['action'] === 'get_unread_count') {
    if (!isLoggedIn()) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    $count = getUnreadNotificationCount(getCurrentUserId());
    echo json_encode(['count' => $count]);
    exit;
}
?> 