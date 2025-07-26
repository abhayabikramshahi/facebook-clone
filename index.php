<?php
require_once 'config/database.php';
require_once 'includes/session.php';

$currentUserId = isLoggedIn() ? getCurrentUserId() : null;

// First, check if shares table exists
$checkTable = $pdo->query("SHOW TABLES LIKE 'shares'");
$sharesTableExists = $checkTable->rowCount() > 0;

// Modify the query based on whether shares table exists
$query = "
    SELECT p.*, u.username, u.profile_picture, u.verification_status,
    (SELECT COUNT(*) FROM likes WHERE photo_id = p.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE photo_id = p.id) as comment_count" .
    ($sharesTableExists ? ", (SELECT COUNT(*) FROM shares WHERE photo_id = p.id) as share_count" : ", 0 as share_count") .
    ($currentUserId ? ", (SELECT COUNT(*) FROM likes WHERE photo_id = p.id AND user_id = :currentUserId) as liked_by_user" : ", 0 as liked_by_user") . "
    FROM photos p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($query);
if ($currentUserId) {
    $stmt->bindParam(':currentUserId', $currentUserId, PDO::PARAM_INT);
}
$stmt->execute();
$photos = $stmt->fetchAll();

// Get trending users (users with most likes)
$trendingUsersQuery = "
    SELECT u.id, u.username, u.profile_picture, u.verification_status,
    (SELECT COUNT(*) FROM photos WHERE user_id = u.id) as post_count,
    (SELECT COUNT(*) FROM likes WHERE photo_id IN (SELECT id FROM photos WHERE user_id = u.id)) as total_likes
    FROM users u
    ORDER BY total_likes DESC
    LIMIT 5
";
$trendingUsersStmt = $pdo->query($trendingUsersQuery);
$trendingUsers = $trendingUsersStmt->fetchAll();

// Fetch current user's data for the 'Share a photo...' section
$currentUser = null;
if ($currentUserId) {
    $currentUserStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $currentUserStmt->execute([$currentUserId]);
    $currentUser = $currentUserStmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoShare - Social Media Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                        primary: {
                            DEFAULT: "hsl(var(--primary))",
                            foreground: "hsl(var(--primary-foreground))",
                        },
                        secondary: {
                            DEFAULT: "hsl(var(--secondary))",
                            foreground: "hsl(var(--secondary-foreground))",
                        },
                        destructive: {
                            DEFAULT: "hsl(var(--destructive))",
                            foreground: "hsl(var(--destructive-foreground))",
                        },
                        muted: {
                            DEFAULT: "hsl(var(--muted))",
                            foreground: "hsl(var(--muted-foreground))",
                        },
                        accent: {
                            DEFAULT: "hsl(var(--accent))",
                            foreground: "hsl(var(--accent-foreground))",
                        },
                        popover: {
                            DEFAULT: "hsl(var(--popover))",
                            foreground: "hsl(var(--popover-foreground))",
                        },
                        card: {
                            DEFAULT: "hsl(var(--card))",
                            foreground: "hsl(var(--card-foreground))",
                        },
                    },
                },
            },
        }
    </script>
    <style>
        :root {
            --background: 0 0% 100%;
            --foreground: 222.2 84% 4.9%;
            --card: 0 0% 100%;
            --card-foreground: 222.2 84% 4.9%;
            --popover: 0 0% 100%;
            --popover-foreground: 222.2 84% 4.9%;
            --primary: 221.2 83.2% 53.3%;
            --primary-foreground: 210 40% 98%;
            --secondary: 210 40% 96.1%;
            --secondary-foreground: 222.2 47.4% 11.2%;
            --muted: 210 40% 96.1%;
            --muted-foreground: 215.4 16.3% 46.9%;
            --accent: 210 40% 96.1%;
            --accent-foreground: 222.2 47.4% 11.2%;
            --destructive: 0 84.2% 60.2%;
            --destructive-foreground: 210 40% 98%;
            --border: 214.3 31.8% 91.4%;
            --input: 214.3 31.8% 91.4%;
            --ring: 221.2 83.2% 53.3%;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
        }
        .like-button {
            transition: all 0.2s ease-in-out;
        }
        .like-button:hover {
            transform: scale(1.05);
        }
        .like-button.scale-110 {
            transform: scale(1.1);
        }
        @keyframes heartBeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .heart-beat {
            animation: heartBeat 0.3s ease-in-out;
        }
        .content-wrapper {
            min-height: calc(100vh - 4rem);
            padding-top: 4rem;
        }
        .sidebar {
            height: calc(100vh - 4rem);
            top: 4rem;
        }
        .share-button {
            transition: all 0.2s ease-in-out;
        }
        .share-button:hover {
            transform: scale(1.05);
        }
        .share-button.scale-110 {
            transform: scale(1.1);
        }
        @keyframes sharePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .share-pulse {
            animation: sharePulse 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Fixed Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">PhotoShare</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="notifications.php" class="relative text-gray-700 hover:text-blue-600">
                            <i class="fas fa-bell text-xl"></i>
                            <?php
                            // Get unread notifications count
                            $notifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                            $notifStmt->execute([$currentUserId]);
                            $unreadCount = $notifStmt->fetchColumn();
                            if ($unreadCount > 0):
                            ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unreadCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                        </a>
                        <a href="logout.php" class="text-gray-700 hover:text-blue-600">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="ml-2">Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-blue-600">Login</a>
                        <a href="register.php" class="text-gray-700 hover:text-blue-600">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto my-8 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900">Create New Post</h3>
                <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getCurrentUsername()); ?>" 
                         class="w-10 h-10 rounded-full" alt="Profile">
                    <input type="text" 
                           name="caption" 
                           class="flex-1 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                           placeholder="What's on your mind?" 
                           required>
                </div>
                <div class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center">
                    <input type="file" 
                           name="photo" 
                           accept="image/*" 
                           required 
                           class="hidden" 
                           id="photoInput"
                           onchange="previewImage(this)">
                    <label for="photoInput" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600">Click to upload photo</p>
                    </label>
                    <img id="imagePreview" class="hidden max-h-64 mx-auto mt-4 rounded-lg">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="max-w-2xl mx-auto px-4 py-2">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="max-w-2xl mx-auto px-4 py-2">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 hover:text-red-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="flex">
            <!-- Left Sidebar -->
            <div class="fixed left-0 top-16 h-screen w-64 bg-white border-r border-gray-200 p-4 hidden md:block">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-home w-6"></i>
                        <span>Home</span>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="messages.php" class="flex items-center space-x-3 text-gray-700 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-50 <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'bg-gray-50 text-blue-600' : ''; ?>">
                            <i class="fas fa-envelope w-6"></i>
                            <span>Messages</span>
                        </a>
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

            <!-- Main Content -->
            <div class="flex-1 pt-16 md:ml-64 md:mr-64 px-4">
                <!-- Create Post Button -->
                <div class="max-w-2xl mx-auto p-4 bg-white border border-gray-200 rounded-lg mb-6">
                    <button onclick="openUploadModal()" class="w-full flex items-center space-x-3 hover:bg-gray-50 p-2 rounded-lg">
                        <img src="<?php echo $currentUser['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                             class="w-10 h-10 rounded-full object-cover" 
                             alt="Your profile">
                        <span class="text-gray-500">Share a photo...</span>
                    </button>
                </div>

                <!-- Photos Feed -->
                <div class="max-w-2xl mx-auto space-y-6">
                    <?php foreach ($photos as $photo): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                            <!-- Photo Header -->
                            <div class="p-4 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <a href="profile.php?id=<?php echo $photo['user_id']; ?>" class="flex items-center space-x-2">
                                        <img src="<?php echo $photo['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                             class="w-10 h-10 rounded-full object-cover" 
                                             alt="<?php echo htmlspecialchars($photo['username']); ?>">
                                        <div>
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($photo['username']); ?></span>
                                            <?php if (isset($photo['verification_status']) && $photo['verification_status'] === 'verified'): ?>
                                                <i class="fas fa-check-circle text-blue-500 ml-1" title="Verified"></i>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                                <button class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </div>
                            
                            <!-- Photo -->
                            <img src="<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                 class="w-full h-96 object-cover" 
                                 alt="<?php echo htmlspecialchars($photo['caption']); ?>">
                            
                            <!-- Photo Actions -->
                            <div class="p-4">
                                <div class="flex items-center space-x-4 mb-2">
                                    <button onclick="handleLike(<?php echo $photo['id']; ?>)" 
                                            class="like-button text-gray-500 hover:text-red-500 focus:outline-none"
                                            data-photo-id="<?php echo $photo['id']; ?>">
                                        <i class="<?php echo isset($photo['liked_by_user']) && $photo['liked_by_user'] > 0 ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                    </button>
                                    <button onclick="toggleComments(<?php echo $photo['id']; ?>)" 
                                            class="text-gray-500 hover:text-blue-500 focus:outline-none">
                                        <i class="far fa-comment"></i>
                                    </button>
                                    <button onclick="sharePhoto(<?php echo $photo['id']; ?>, '<?php echo htmlspecialchars(addslashes($photo['username'])); ?>', '<?php echo htmlspecialchars(addslashes($photo['caption'])); ?>')" 
                                            class="share-button text-gray-500 hover:text-green-500 focus:outline-none">
                                        <i class="far fa-share-square"></i>
                                    </button>
                                </div>
                                
                                <div class="text-sm text-gray-900 mb-1">
                                    <span class="font-medium like-count" data-photo-id="<?php echo $photo['id']; ?>"><?php echo $photo['like_count']; ?> likes</span>
                                </div>
                                
                                <p class="text-gray-900">
                                    <span class="font-medium"><?php echo htmlspecialchars($photo['username']); ?></span>
                                    <?php echo htmlspecialchars($photo['caption']); ?>
                                </p>
                                
                                <div class="text-sm text-gray-500 mt-1">
                                    <?php echo date('M j, Y', strtotime($photo['created_at'])); ?>
                                </div>
                            </div>
                            <!-- Comments Section (hidden by default) -->
                            <div id="comments-<?php echo $photo['id']; ?>" class="hidden p-4 bg-gray-50 border-t border-gray-200">
                                <h4 class="font-semibold text-gray-700 mb-2">Comments</h4>
                                <!-- Display existing comments here -->
                                <div class="space-y-3">
                                    <?php
                                    // Fetch comments for the current photo
                                    $commentsStmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.photo_id = ? ORDER BY c.created_at DESC LIMIT 3");
                                    $commentsStmt->execute([$photo['id']]);
                                    $comments = $commentsStmt->fetchAll();
                                    ?>
                                    <?php if ($comments): ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="text-sm text-gray-800">
                                                <span class="font-medium"><?php echo htmlspecialchars($comment['username']); ?></span>
                                                <?php echo htmlspecialchars($comment['comment_text']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">No comments yet.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Add new comment form -->
                                <form action="add_comment.php" method="POST" class="mt-4 flex space-x-2">
                                    <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                    <input type="text" name="comment" placeholder="Add a comment..." 
                                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                                        Post
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="fixed right-0 top-16 h-screen w-64 bg-white border-l border-gray-200 p-4 hidden md:block">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Trending Users</h3>
                    <div class="space-y-4">
                        <?php foreach ($trendingUsers as $trendingUser): ?>
                            <a href="profile.php?id=<?php echo $trendingUser['id']; ?>" class="flex items-center space-x-3 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                                <img src="<?php echo $trendingUser['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                     class="w-10 h-10 rounded-full object-cover" 
                                     alt="<?php echo htmlspecialchars($trendingUser['username']); ?>">
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($trendingUser['username']); ?>
                                        <?php if (isset($trendingUser['verification_status']) && $trendingUser['verification_status'] === 'verified'): ?>
                                            <i class="fas fa-check-circle text-blue-500 ml-1" title="Verified"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo $trendingUser['post_count']; ?> posts</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">About PhotoShare</h3>
                    <p class="text-gray-600 text-sm">
                        Share your moments with the world. Connect with photographers, share your work, and discover amazing photos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="shareModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-auto my-8 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900">Share Post</h3>
                <button onclick="closeShareModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3 mb-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getCurrentUsername()); ?>" 
                         class="w-8 h-8 rounded-full" alt="Profile">
                    <span class="font-medium text-gray-900" id="shareOriginalUser"></span>
                </div>
                <p class="text-gray-600 text-sm" id="shareOriginalCaption"></p>
            </div>
            <form action="share.php" method="POST" class="space-y-4">
                <input type="hidden" name="photo_id" id="sharePhotoId">
                <div class="flex items-center space-x-4">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getCurrentUsername()); ?>" 
                         class="w-10 h-10 rounded-full" alt="Profile">
                    <input type="text" 
                           name="caption" 
                           class="flex-1 rounded-lg border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" 
                           placeholder="Write something about this post..." 
                           required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeShareModal()" 
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Share
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleComments(photoId) {
            const commentsDiv = document.getElementById('comments-' + photoId);
            commentsDiv.classList.toggle('hidden');
        }

        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('photoInput').value = '';
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const uploadModal = document.getElementById('uploadModal');
            const shareModal = document.getElementById('shareModal');
            
            if (event.target === uploadModal) {
                closeUploadModal();
            }
            if (event.target === shareModal) {
                closeShareModal();
            }
        }

        // Like functionality
        async function handleLike(photoId) {
            try {
                const response = await fetch('like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `photo_id=${photoId}`
                });

                const data = await response.json();
                
                if (data.success) {
                    // Update like count
                    const likeCountElement = document.querySelector(`.like-count[data-photo-id="${photoId}"]`);
                    if (likeCountElement) {
                        likeCountElement.textContent = `${data.like_count} likes`;
                    }
                    
                    // Update like button
                    const likeButton = document.querySelector(`.like-button[data-photo-id="${photoId}"]`);
                    if (likeButton) {
                        const likeIcon = likeButton.querySelector('i');
                        if (data.liked) {
                            likeIcon.classList.remove('far');
                            likeIcon.classList.add('fas', 'text-red-500');
                        } else {
                            likeIcon.classList.remove('fas', 'text-red-500');
                            likeIcon.classList.add('far');
                        }
                        
                        // Add animation
                        likeButton.classList.add('scale-110');
                        setTimeout(() => {
                            likeButton.classList.remove('scale-110');
                        }, 200);
                    }
                } else {
                    console.error('Error:', data.error);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function openShareModal(photoId, username, caption) {
            const modal = document.getElementById('shareModal');
            document.getElementById('sharePhotoId').value = photoId;
            document.getElementById('shareOriginalUser').textContent = username;
            document.getElementById('shareOriginalCaption').textContent = caption;
            modal.classList.add('active');
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('active');
        }

        // Remove old event listeners and onclick attributes
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.like-button').forEach(button => {
                // Remove any existing onclick attributes
                button.removeAttribute('onclick');
                
                // Add click event listener
                button.addEventListener('click', () => {
                    const photoId = button.getAttribute('data-photo-id');
                    handleLike(photoId);
                });
            });
        });

        // Update sharePhoto function to use event listener
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.share-button').forEach(button => {
                button.addEventListener('click', () => {
                    const onclickAttr = button.getAttribute('onclick');
                    const match = onclickAttr.match(/sharePhoto\((\d+),\s*'(.*?)',\s*'(.*?)'\)/);
                    if (match && match.length === 4) {
                        const photoId = match[1];
                        const username = match[2].replace(/\\'/g, "'"); // Unescape single quotes
                        const caption = match[3].replace(/\\'/g, "'"); // Unescape single quotes
                        openShareModal(photoId, username, caption);
                    }
                });
                // Remove the inline onclick after attaching the event listener
                button.removeAttribute('onclick');
            });
        });

        // Real-time notification updates
        function updateNotificationCount() {
            fetch('includes/notifications.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.classList.remove('hidden');
                        } else {
                            const bell = document.querySelector('.fa-bell').parentElement;
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center';
                            newBadge.textContent = data.count;
                            bell.appendChild(newBadge);
                        }
                    } else {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));
        }

        // Update notification count every 30 seconds
        setInterval(updateNotificationCount, 30000);
        // Initial update
        updateNotificationCount();

        // Add click handler for notification bell
        document.querySelector('.fa-bell').parentElement.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'notifications.php';
        });
    </script>
</body>
</html> 