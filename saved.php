<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in (optional, depending on if saved content is user-specific)
if (!isLoggedIn()) {
    $_SESSION['error'] = 'You must be logged in to view saved items.';
    header('Location: login.php');
    exit();
}

// Fetch saved items for the logged-in user here
// $userId = getCurrentUserId();
// Example: $savedItems = $pdo->prepare("SELECT * FROM saved_items WHERE user_id = ?");
// $savedItems->execute([$userId]);
// $items = $savedItems->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-wrapper {
            min-height: calc(100vh - 4rem);
            padding-top: 4rem;
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
                   <h1 class="text-2xl font-bold text-gray-900 mb-6">Saved Items</h1>

                   <!-- Content for Saved Items goes here -->
                   <div class="bg-white rounded-lg shadow-sm p-6">
                       <p class="text-gray-700">This is where your saved items will be displayed.</p>
                       <!-- Example saved item: -->
                       <!-- <div class="border-b border-gray-200 py-4">Saved Item 1</div> -->
                   </div>
               </div>
            </div>
        </div>
    </div>

</body>
</html> 