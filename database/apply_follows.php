<?php
require_once '../config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('follows.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Follows table created successfully!";
} catch (PDOException $e) {
    echo "Error creating follows table: " . $e->getMessage();
} 