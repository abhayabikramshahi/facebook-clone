<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to share posts.';
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo_id']) && isset($_POST['caption'])) {
    $photoId = (int)$_POST['photo_id'];
    $caption = trim($_POST['caption']);
    $userId = getCurrentUserId();

    // Log the incoming data
    error_log("Share attempt - User ID: $userId, Photo ID: $photoId, Caption: $caption");

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Check if user has already shared this photo
        $checkStmt = $pdo->prepare("
            SELECT id FROM shares 
            WHERE user_id = ? AND photo_id = ?
        ");
        $checkStmt->execute([$userId, $photoId]);
        $existingShare = $checkStmt->fetch();

        if ($existingShare) {
            $_SESSION['error'] = 'You have already shared this post.';
            header('Location: index.php');
            exit();
        }

        // Get the original photo details
        $stmt = $pdo->prepare("
            SELECT p.*, u.username 
            FROM photos p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$photoId]);
        $originalPhoto = $stmt->fetch();

        if (!$originalPhoto) {
            error_log("Original photo not found - Photo ID: $photoId");
            throw new Exception('The original post could not be found.');
        }

        error_log("Original photo found - Image path: " . $originalPhoto['image_path']);

        // Create a new post with the shared content
        $stmt = $pdo->prepare("
            INSERT INTO photos (user_id, caption, image_path, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $userId,
            $caption,
            $originalPhoto['image_path']
        ]);

        if (!$result) {
            error_log("Failed to insert new photo - Error: " . implode(", ", $stmt->errorInfo()));
            throw new Exception('Failed to create shared post.');
        }

        $newPhotoId = $pdo->lastInsertId();
        error_log("New photo created - ID: $newPhotoId");

        // Record the share
        $stmt = $pdo->prepare("
            INSERT INTO shares (user_id, photo_id, created_at)
            VALUES (?, ?, NOW())
        ");
        
        $result = $stmt->execute([$userId, $photoId]);
        
        if (!$result) {
            error_log("Failed to record share - Error: " . implode(", ", $stmt->errorInfo()));
            throw new Exception('Failed to record share.');
        }

        // Commit transaction
        $pdo->commit();
        error_log("Share completed successfully - New Photo ID: $newPhotoId");

        $_SESSION['success'] = 'Post shared successfully!';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the detailed error
        error_log("Share Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $_SESSION['error'] = 'An error occurred while sharing the post. Please try again.';
        header('Location: index.php');
        exit();
    }
} else {
    error_log("Invalid share request - Method: " . $_SERVER['REQUEST_METHOD'] . 
              ", Photo ID: " . (isset($_POST['photo_id']) ? $_POST['photo_id'] : 'not set') . 
              ", Caption: " . (isset($_POST['caption']) ? $_POST['caption'] : 'not set'));
    
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: index.php');
    exit();
} 