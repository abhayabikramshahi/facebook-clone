<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = getCurrentUserId();
$username = getCurrentUsername();

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $newUsername = trim($_POST['username']);
        $newName = trim($_POST['name']);
        $newNickname = trim($_POST['nickname']);
        
        // Check if username is already taken
        if ($newUsername !== $username) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$newUsername, $userId]);
            if ($checkStmt->rowCount() > 0) {
                $_SESSION['error'] = 'Username is already taken.';
            } else {
                // Update username
                $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->execute([$newUsername, $userId]);
                $_SESSION['success'] = 'Username updated successfully.';
            }
        }
        
        // Update name and nickname
        $stmt = $pdo->prepare("UPDATE users SET name = ?, nickname = ? WHERE id = ?");
        $stmt->execute([$newName, $newNickname, $userId]);
        $_SESSION['success'] = 'Profile updated successfully.';
        
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['enable_2fa'])) {
        // Generate 2FA secret
        require_once 'vendor/autoload.php';
        $ga = new PHPGangsta_GoogleAuthenticator\GoogleAuthenticator();
        $secret = $ga->createSecret();
        
        // Store secret in database
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $userId]);
        
        $_SESSION['2fa_secret'] = $secret;
        $_SESSION['success'] = '2FA has been enabled. Please scan the QR code to complete setup.';
        
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['disable_2fa'])) {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = '2FA has been disabled.';
        
        header('Location: settings.php');
        exit();
    }
    
    if (isset($_POST['verify_account'])) {
        // Handle verification request
        $stmt = $pdo->prepare("UPDATE users SET verification_status = 'pending' WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = 'Verification request submitted. We will review your account.';
        
        header('Location: settings.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">PhotoShare</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home"></i>
                        <span class="ml-2">Home</span>
                    </a>
                    <a href="profile.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-user"></i>
                        <span class="ml-2">Profile</span>
                    </a>
                    <a href="logout.php" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Sidebar -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Settings</h2>
                    <nav class="space-y-2">
                        <a href="#profile" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                            <i class="fas fa-user-circle mr-2"></i> Profile Settings
                        </a>
                        <a href="#security" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                            <i class="fas fa-shield-alt mr-2"></i> Security
                        </a>
                        <a href="#verification" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                            <i class="fas fa-check-circle mr-2"></i> Verification
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Settings Area -->
            <div class="md:col-span-2 space-y-6">
                <!-- Profile Settings -->
                <div id="profile" class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Profile Settings</h3>
                    <form action="settings.php" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nickname</label>
                            <input type="text" name="nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit" name="update_profile" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Security Settings -->
                <div id="security" class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Security Settings</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">Two-Factor Authentication</h4>
                                <p class="text-sm text-gray-500">Add an extra layer of security to your account</p>
                            </div>
                            <?php if (empty($user['two_factor_secret'])): ?>
                                <form action="settings.php" method="POST">
                                    <button type="submit" name="enable_2fa" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Enable 2FA
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="settings.php" method="POST">
                                    <button type="submit" name="disable_2fa" 
                                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        Disable 2FA
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($_SESSION['2fa_secret'])): ?>
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Scan QR Code</h4>
                                <p class="text-sm text-gray-500 mb-4">Scan this QR code with your authenticator app</p>
                                <?php
                                $ga = new PHPGangsta_GoogleAuthenticator\GoogleAuthenticator();
                                $qrCodeUrl = $ga->getQRCodeGoogleUrl($username, $_SESSION['2fa_secret'], 'PhotoShare');
                                ?>
                                <img src="<?php echo $qrCodeUrl; ?>" alt="2FA QR Code" class="mx-auto">
                                <p class="text-sm text-gray-500 mt-4">Or enter this code manually: <?php echo $_SESSION['2fa_secret']; ?></p>
                            </div>
                            <?php unset($_SESSION['2fa_secret']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Verification Settings -->
                <div id="verification" class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Verification</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">Verified Badge</h4>
                                <p class="text-sm text-gray-500">Get a verified badge on your profile</p>
                            </div>
                            <?php if ($user['verification_status'] === 'verified'): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-check-circle mr-1"></i> Verified
                                </span>
                            <?php elseif ($user['verification_status'] === 'pending'): ?>
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-clock mr-1"></i> Pending Review
                                </span>
                            <?php else: ?>
                                <form action="settings.php" method="POST">
                                    <button type="submit" name="verify_account" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Request Verification
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
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
</body>
</html> 