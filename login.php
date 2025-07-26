<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="text-3xl font-bold text-blue-600">PhotoShare</a>
            <p class="text-gray-600 mt-2">Share your moments with the world</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Login to your account</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter your username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter your password">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-blue-600 hover:text-blue-700 font-medium">
                        Register here
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 