<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUserId = getCurrentUserId();
$currentUsername = getCurrentUsername();

// Get the requested user ID from URL
$requestedUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

// Get user information
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM photos WHERE user_id = u.id) as post_count,
           (SELECT COUNT(*) FROM likes WHERE photo_id IN (SELECT id FROM photos WHERE user_id = u.id)) as total_likes,
           (SELECT COUNT(*) FROM comments WHERE photo_id IN (SELECT id FROM photos WHERE user_id = u.id)) as total_comments
    FROM users u 
    WHERE u.id = ?
");
$stmt->execute([$requestedUserId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit();
}

$isOwnProfile = $user['id'] === $currentUserId;

// Get user's photos
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM likes WHERE photo_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE photo_id = p.id) as comment_count
    FROM photos p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$photos = $stmt->fetchAll();

// Get follow status
$isFollowing = false;
if ($user['id'] != $currentUserId) {
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM follows 
        WHERE follower_id = ? 
        AND following_id = ?
    ");
    $stmt->execute([$currentUserId, $user['id']]);
    $isFollowing = $stmt->fetchColumn() !== false;
}

// Handle profile picture upload (only for own profile)
if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 5MB.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.'
        };
        $_SESSION['error'] = $error;
    }
    // Check file type
    elseif (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    }
    // Check file size
    elseif ($file['size'] > $maxFileSize) {
        $_SESSION['error'] = 'File is too large. Maximum size is 5MB.';
    }
    else {
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploads/profiles';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileName = 'profile_' . $user['id'] . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', basename($file['name']));
        $targetPath = $uploadDir . '/' . $fileName;
        
        // Try to move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old profile picture if it exists
            if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            if ($stmt->execute([$targetPath, $user['id']])) {
                $_SESSION['success'] = 'Profile picture updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update profile picture in database.';
                // Delete the uploaded file if database update fails
                unlink($targetPath);
            }
        } else {
            $_SESSION['error'] = 'Failed to save the uploaded file. Please try again.';
        }
    }
    
    header('Location: profile.php' . ($isOwnProfile ? '' : '?id=' . $requestedUserId));
    exit();
}

// Handle cover photo upload (only for own profile)
if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cover_photo'])) {
    $file = $_FILES['cover_photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large. Maximum size is 10MB.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.'
        };
        $_SESSION['error'] = $error;
    }
    // Check file type
    elseif (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    }
    // Check file size
    elseif ($file['size'] > $maxFileSize) {
        $_SESSION['error'] = 'File is too large. Maximum size is 10MB.';
    }
    else {
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploads/covers';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileName = 'cover_' . $user['id'] . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', basename($file['name']));
        $targetPath = $uploadDir . '/' . $fileName;
        
        // Try to move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old cover photo if it exists
            if (!empty($user['cover_photo']) && file_exists($user['cover_photo'])) {
                unlink($user['cover_photo']);
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
            if ($stmt->execute([$targetPath, $user['id']])) {
                $_SESSION['success'] = 'Cover photo updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update cover photo in database.';
                // Delete the uploaded file if database update fails
                unlink($targetPath);
            }
        } else {
            $_SESSION['error'] = 'Failed to save the uploaded file. Please try again.';
        }
    }
    
    header('Location: profile.php' . ($isOwnProfile ? '' : '?id=' . $requestedUserId));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            position: relative;
            height: 300px;
            background-color: #f3f4f6;
        }
        .cover-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-picture-container {
            position: absolute;
            bottom: -50px;
            left: 50px;
            width: 150px;
            height: 150px;
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            border: 4px solid white;
            border-radius: 50%;
            object-fit: cover;
        }
        .camera-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
        }
        .camera-icon:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        .cover-camera-icon {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 10;
        }
        .cover-camera-icon:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Error/Success Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                <span class="sr-only">Dismiss</span>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50">
            <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                <span class="sr-only">Dismiss</span>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Left Sidebar -->
    <div class="fixed left-0 top-16 h-screen w-64 bg-white border-r border-gray-200 p-4 hidden md:block">
        <nav class="space-y-2">
            <a href="index.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                <i class="fas fa-home w-6"></i>
                <span>Home</span>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-user w-6"></i>
                    <span>Profile</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-cog w-6"></i>
                    <span>Settings</span>
                </a>
                <a href="saved.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'saved.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-bookmark w-6"></i>
                    <span>Saved</span>
                </a>
                <a href="notifications.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-bell w-6"></i>
                    <span>Notifications</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-sign-in-alt w-6"></i>
                    <span>Login</span>
                </a>
                <a href="register.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'register.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                    <i class="fas fa-user-plus w-6"></i>
                    <span>Register</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Profile Header -->
    <div class="profile-header mt-16">
        <img src="<?php echo $user['cover_photo'] ?? 'assets/images/covers/default-cover.jpg'; ?>" 
             class="cover-photo" alt="Cover Photo">
        <?php if ($isOwnProfile): ?>
            <label for="cover_photo" class="cover-camera-icon">
                <i class="fas fa-camera"></i>
            </label>
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="file" name="cover_photo" id="cover_photo" onchange="this.form.submit()">
            </form>
        <?php endif; ?>
        
        <div class="profile-picture-container">
            <img src="<?php echo $user['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                 class="profile-picture" alt="Profile Picture">
            <?php if ($isOwnProfile): ?>
                <label for="profile_picture" class="camera-icon">
                    <i class="fas fa-camera"></i>
                </label>
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="hidden">
                    <input type="file" name="profile_picture" id="profile_picture" onchange="this.form.submit()">
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mt-16">
            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h1>
            <div class="flex items-center space-x-4 mt-2">
                <?php if ($user['id'] != $currentUserId): ?>
                    <button onclick="startConversation(<?php echo $user['id']; ?>)" 
                            class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                        <i class="fas fa-envelope"></i>
                        <span>Message</span>
                    </button>
                    <button onclick="toggleFollow(<?php echo $user['id']; ?>)" 
                            class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors"
                            id="followBtn_<?php echo $user['id']; ?>">
                        <i class="fas <?php echo $isFollowing ? 'fa-user-check' : 'fa-user-plus'; ?>"></i>
                        <span><?php echo $isFollowing ? 'Following' : 'Follow'; ?></span>
                    </button>
                <?php endif; ?>
            </div>
            <p class="text-gray-500">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            
            <!-- Stats -->
            <div class="grid grid-cols-5 gap-4 mt-6">
                <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $user['post_count']; ?></div>
                    <div class="text-gray-500">Posts</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $user['total_likes']; ?></div>
                    <div class="text-gray-500">Likes</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $user['total_comments']; ?></div>
                    <div class="text-gray-500">Comments</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900">
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
                        $stmt->execute([$user['id']]);
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="text-gray-500">Followers</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900">
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
                        $stmt->execute([$user['id']]);
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="text-gray-500">Following</div>
                </div>
            </div>

            <!-- Add this after the Stats section -->
            <div class="mt-6 flex space-x-4">
                <button onclick="showFollowers(<?php echo $user['id']; ?>)" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    View Followers
                </button>
                <button onclick="showFollowing(<?php echo $user['id']; ?>)" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    View Following
                </button>
            </div>
        </div>

        <!-- Photos Grid -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Photos</h2>
            <?php if (empty($photos)): ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <i class="fas fa-camera text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">
                        <?php echo $isOwnProfile ? "You haven't posted any photos yet." : "This user hasn't posted any photos yet."; ?>
                    </p>
                    <?php if ($isOwnProfile): ?>
                        <a href="index.php" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create Your First Post
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($photos as $photo): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <img src="<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 class="w-full h-48 object-cover" 
                                 alt="<?php echo htmlspecialchars($photo['caption']); ?>">
                            <div class="p-4">
                                <p class="text-gray-800 mb-2"><?php echo htmlspecialchars($photo['caption']); ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center space-x-4">
                                        <span><i class="far fa-heart"></i> <?php echo $photo['like_count']; ?></span>
                                        <span><i class="far fa-comment"></i> <?php echo $photo['comment_count']; ?></span>
                                    </div>
                                    <span><?php echo date('M j, Y', strtotime($photo['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Followers/Following Modal -->
    <div id="followModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Followers</h3>
                    <button onclick="closeFollowModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="followList" class="p-4 max-h-96 overflow-y-auto">
                    <!-- List will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    // Add follow/unfollow functionality
    async function toggleFollow(userId) {
        try {
            const response = await fetch('follow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                const followBtn = document.getElementById(`followBtn_${userId}`);
                if (followBtn) {
                    const icon = followBtn.querySelector('i');
                    const text = followBtn.querySelector('span');
                    
                    if (data.is_following) {
                        icon.className = 'fas fa-user-check';
                        text.textContent = 'Following';
                        followBtn.classList.add('text-blue-600');
                    } else {
                        icon.className = 'fas fa-user-plus';
                        text.textContent = 'Follow';
                        followBtn.classList.remove('text-blue-600');
                    }
                }
            } else {
                console.error('Follow error:', data.error);
            }
        } catch (error) {
            console.error('Error toggling follow:', error);
        }
    }

    // Add start conversation functionality
    function startConversation(userId) {
        window.location.href = `messages.php?user=${userId}`;
    }

    async function showFollowers(userId) {
        const modal = document.getElementById('followModal');
        const title = document.getElementById('modalTitle');
        const list = document.getElementById('followList');
        
        title.textContent = 'Followers';
        list.innerHTML = '<p class="text-center text-gray-500">Loading...</p>';
        modal.classList.remove('hidden');
        
        try {
            const response = await fetch(`get_follows.php?user_id=${userId}&type=followers`);
            const data = await response.json();
            
            if (data.status === 'success') {
                list.innerHTML = data.users.map(user => `
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <img src="${user.profile_picture || 'assets/images/profiles/default-profile.jpg'}" 
                                 class="w-10 h-10 rounded-full object-cover" 
                                 alt="${user.username}">
                            <div>
                                <p class="font-medium text-gray-900">
                                    ${user.username}
                                    ${user.verification_status === 'verified' ? 
                                        '<i class="fas fa-check-circle text-blue-500 ml-1"></i>' : ''}
                                </p>
                            </div>
                        </div>
                        ${user.id != <?php echo $currentUserId; ?> ? `
                            <button onclick="toggleFollow(${user.id})" 
                                    class="px-4 py-2 ${user.is_following ? 'bg-gray-200 text-gray-700' : 'bg-blue-600 text-white'} rounded-lg hover:bg-opacity-90"
                                    id="followBtn_${user.id}">
                                ${user.is_following ? 'Following' : 'Follow'}
                            </button>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<p class="text-center text-red-500">Error loading followers</p>';
            }
        } catch (error) {
            console.error('Error:', error);
            list.innerHTML = '<p class="text-center text-red-500">Error loading followers</p>';
        }
    }

    async function showFollowing(userId) {
        const modal = document.getElementById('followModal');
        const title = document.getElementById('modalTitle');
        const list = document.getElementById('followList');
        
        title.textContent = 'Following';
        list.innerHTML = '<p class="text-center text-gray-500">Loading...</p>';
        modal.classList.remove('hidden');
        
        try {
            const response = await fetch(`get_follows.php?user_id=${userId}&type=following`);
            const data = await response.json();
            
            if (data.status === 'success') {
                list.innerHTML = data.users.map(user => `
                    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <img src="${user.profile_picture || 'assets/images/profiles/default-profile.jpg'}" 
                                 class="w-10 h-10 rounded-full object-cover" 
                                 alt="${user.username}">
                            <div>
                                <p class="font-medium text-gray-900">
                                    ${user.username}
                                    ${user.verification_status === 'verified' ? 
                                        '<i class="fas fa-check-circle text-blue-500 ml-1"></i>' : ''}
                                </p>
                            </div>
                        </div>
                        ${user.id != <?php echo $currentUserId; ?> ? `
                            <button onclick="toggleFollow(${user.id})" 
                                    class="px-4 py-2 ${user.is_following ? 'bg-gray-200 text-gray-700' : 'bg-blue-600 text-white'} rounded-lg hover:bg-opacity-90"
                                    id="followBtn_${user.id}">
                                ${user.is_following ? 'Following' : 'Follow'}
                            </button>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<p class="text-center text-red-500">Error loading following</p>';
            }
        } catch (error) {
            console.error('Error:', error);
            list.innerHTML = '<p class="text-center text-red-500">Error loading following</p>';
        }
    }

    function closeFollowModal() {
        document.getElementById('followModal').classList.add('hidden');
    }
    </script>
</body>
</html> 