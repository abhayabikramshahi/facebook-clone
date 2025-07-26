<?php
require_once 'config/database.php';

try {
    // Create users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        )
    ");

    // Create photos table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS photos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            caption TEXT,
            image_path VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Create likes table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            photo_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (photo_id) REFERENCES photos(id),
            UNIQUE KEY unique_like (user_id, photo_id)
        )
    ");

    // Create comments table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            photo_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (photo_id) REFERENCES photos(id)
        )
    ");

    // Create shares table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shares (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            photo_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (photo_id) REFERENCES photos(id),
            UNIQUE KEY unique_share (user_id, photo_id)
        )
    ");

    echo "All tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 