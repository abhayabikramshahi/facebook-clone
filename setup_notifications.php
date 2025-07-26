<?php
require_once 'config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('sql/create_notifications_table.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Notifications table created successfully!";
} catch (PDOException $e) {
    echo "Error creating notifications table: " . $e->getMessage();
}
?> 