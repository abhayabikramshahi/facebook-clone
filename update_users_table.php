<?php
require_once 'config/database.php';

try {
    // Add new columns to users table
    $pdo->exec("
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS name VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS nickname VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(32) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS verification_status ENUM('unverified', 'pending', 'verified') DEFAULT 'unverified'
    ");
    
    echo "Users table updated successfully!";
} catch (PDOException $e) {
    echo "Error updating users table: " . $e->getMessage();
}
?> 