<?php
// Define the directories to create
$directories = [
    'uploads',
    'uploads/profiles',
    'uploads/covers',
    'uploads/posts',
    'assets',
    'assets/images',
    'assets/images/profiles',
    'assets/images/covers'
];

// Create each directory if it doesn't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        echo "Directory already exists: $dir\n";
    }
    
    // Set permissions (if on Unix-like system)
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        chmod($dir, 0777);
        echo "Set permissions for: $dir\n";
    }
}

echo "\nDirectory setup complete!";
?> 