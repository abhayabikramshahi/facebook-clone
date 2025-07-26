<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id']) && isset($_POST['comment'])) {
    $photo_id = $_POST['photo_id'];
    $comment = trim($_POST['comment']);
    $user_id = getCurrentUserId();
    
    if (!empty($comment)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, photo_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $photo_id, $comment]);
        } catch (PDOException $e) {
            // Handle error silently
        }
    }
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit(); 