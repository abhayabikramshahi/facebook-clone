<?php
require_once '../config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('messages.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Messages table created successfully!";
} catch (PDOException $e) {
    echo "Error creating messages table: " . $e->getMessage();
} 