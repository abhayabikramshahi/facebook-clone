<?php
require_once 'config/database.php';

try {
    // Check photos table
    $result = $pdo->query("SHOW TABLES LIKE 'photos'");
    if ($result->rowCount() == 0) {
        echo "Error: photos table does not exist<br>";
    } else {
        echo "photos table exists<br>";
        
        // Check photos columns
        $columns = $pdo->query("SHOW COLUMNS FROM photos")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'caption', 'image_path', 'created_at'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                echo "Error: photos table missing column: $column<br>";
            }
        }
    }

    // Check shares table
    $result = $pdo->query("SHOW TABLES LIKE 'shares'");
    if ($result->rowCount() == 0) {
        echo "Error: shares table does not exist<br>";
    } else {
        echo "shares table exists<br>";
        
        // Check shares columns
        $columns = $pdo->query("SHOW COLUMNS FROM shares")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'photo_id', 'created_at'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                echo "Error: shares table missing column: $column<br>";
            }
        }
    }

    // Check foreign keys
    $foreignKeys = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME IN ('photos', 'shares')
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<br>Foreign Keys:<br>";
    foreach ($foreignKeys as $fk) {
        echo "Table: {$fk['TABLE_NAME']}, Column: {$fk['COLUMN_NAME']}, References: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}<br>";
    }

    // Check for any existing shares
    $shares = $pdo->query("SELECT COUNT(*) as count FROM shares")->fetch();
    echo "<br>Total shares in database: " . $shares['count'] . "<br>";

    // Check for any existing photos
    $photos = $pdo->query("SELECT COUNT(*) as count FROM photos")->fetch();
    echo "Total photos in database: " . $photos['count'] . "<br>";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?> 