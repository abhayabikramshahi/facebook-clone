<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption']);
    
    if (empty($_FILES['photo']['name'])) {
        $error = 'Please select a photo to upload';
    } else {
        $file = $_FILES['photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Only JPG, PNG and GIF files are allowed';
        } elseif ($file['size'] > $max_size) {
            $error = 'File size must be less than 5MB';
        } else {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = uniqid() . '_' . basename($file['name']);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, image_path, caption) VALUES (?, ?, ?)");
                try {
                    $stmt->execute([getCurrentUserId(), $target_path, $caption]);
                    $success = 'Photo uploaded successfully';
                } catch (PDOException $e) {
                    $error = 'Failed to save photo information';
                    unlink($target_path); // Delete the uploaded file if database insert fails
                }
            } else {
                $error = 'Failed to upload photo';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photo - Photo Sharing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Facebook</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="profile.php"><?php echo htmlspecialchars(getCurrentUsername()); ?></a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Upload Photo</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Select Photo</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                                <div class="form-text">Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="caption" class="form-label">Caption</label>
                                <textarea class="form-control" id="caption" name="caption" rows="3"></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Upload Photo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 