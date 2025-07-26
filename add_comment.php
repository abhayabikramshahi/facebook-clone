<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/notifications.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to comment on posts';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$currentUserId = getCurrentUserId();

// Check if the request method is POST and required parameters are set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id']) && isset($_POST['comment'])) {
    $photoId = (int)$_POST['photo_id'];
    $comment = trim($_POST['comment']);

    // Validate input
    if (empty($comment)) {
        $_SESSION['error'] = 'Comment cannot be empty';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Check if the photo exists (optional but recommended)
    $stmt = $pdo->prepare("SELECT id FROM photos WHERE id = ?");
    $stmt->execute([$photoId]);
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'Photo not found.';
        header('Location: index.php');
        exit();
    }

    try {
        // Insert the comment into the database
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, photo_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$currentUserId, $photoId, $comment]);

        // Get photo owner's ID
        $ownerStmt = $pdo->prepare("SELECT user_id FROM photos WHERE id = ?");
        $ownerStmt->execute([$photoId]);
        $photoOwnerId = $ownerStmt->fetchColumn();

        // Create notification if not commenting on own photo
        if ($photoOwnerId !== $currentUserId) {
            createNotification($photoOwnerId, $currentUserId, 'comment', $photoId);
        }

        $_SESSION['success'] = 'Comment added successfully';
    } catch (PDOException $e) {
        error_log("Error in add_comment.php: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred while adding your comment';
    }
} else {
    // Handle invalid request
    $_SESSION['error'] = 'Invalid request';
}

// Redirect back to the homepage or the specific photo page
// For now, redirect to index.php. You might want to redirect to a specific photo view page later.
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?> 