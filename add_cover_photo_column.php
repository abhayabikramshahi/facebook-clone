<?php
require_once 'config/database.php';

try {
    // Add only the cover_photo column to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN cover_photo VARCHAR(255) DEFAULT NULL");
    echo "Successfully added cover_photo column to users table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 