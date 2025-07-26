<?php
require_once 'config/database.php';

try {
    // Read the SQL file
    $sql = file_get_contents('sql/setup_database.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage();
}
?> 