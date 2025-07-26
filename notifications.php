<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in to view notifications.';
    header('Location: login.php');
    exit();
}

$userId = getCurrentUserId();

// Mark notifications as read when viewing
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);

// Fetch notifications with user details
$notifications = $pdo->prepare("
    SELECT n.*, 
           u.username, 
           u.profile_picture,
           p.image_path as photo_path,
           p.caption as photo_caption
    FROM notifications n
    LEFT JOIN users u ON n.actor_id = u.id
    LEFT JOIN photos p ON n.photo_id = p.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$notifications->execute([$userId]);
$items = $notifications->fetchAll();

// Function to get notification message
function getNotificationMessage($type, $username, $caption = null) {
    switch ($type) {
        case 'like':
            return "<strong>$username</strong> liked your post";
        case 'comment':
            return "<strong>$username</strong> commented on your post";
        case 'share':
            return "<strong>$username</strong> shared your post";
        case 'follow':
            return "<strong>$username</strong> started following you";
        default:
            return "New notification";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-wrapper {
            min-height: calc(100vh - 4rem);
            padding-top: 4rem;
        }
        .notification-item {
            transition: all 0.2s ease;
        }
        .notification-item:hover {
            background-color: #f9fafb;
        }
        .notification-item.unread {
            background-color: #f0f9ff;
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

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
        <div class="flex">
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

            <!-- Main Content -->
            <div class="flex-1 pt-16 md:ml-64 px-4">
                <div class="max-w-2xl mx-auto">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">Notifications</h1>

                    <?php if (empty($items)): ?>
                        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                            <i class="fas fa-bell text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500">No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-sm divide-y divide-gray-200">
                            <?php foreach ($items as $item): ?>
                                <div class="notification-item p-4 flex items-start space-x-4 <?php echo !$item['is_read'] ? 'unread' : ''; ?>">
                                    <img src="<?php echo $item['profile_picture'] ?? 'assets/images/profiles/default-profile.jpg'; ?>" 
                                         class="w-10 h-10 rounded-full object-cover" 
                                         alt="<?php echo htmlspecialchars($item['username']); ?>">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-900">
                                            <?php echo getNotificationMessage($item['type'], $item['username'], $item['photo_caption']); ?>
                                        </div>
                                        <?php if ($item['photo_path']): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($item['photo_path']); ?>" 
                                                     class="w-16 h-16 object-cover rounded" 
                                                     alt="Post thumbnail">
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 