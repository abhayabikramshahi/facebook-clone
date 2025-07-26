<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            try {
                $stmt->execute([$username, $email, $hashed_password]);
                $success = 'Registration successful! You can now login.';
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - PhotoShare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="text-3xl font-bold text-blue-600">PhotoShare</a>
            <p class="text-gray-600 mt-2">Join our community</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Create your account</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
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
                           placeholder="Choose a username">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter your email">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Create a password">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Confirm your password">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-700 font-medium">
                        Login here
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 