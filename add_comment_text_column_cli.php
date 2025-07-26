<?php
require_once 'config/database.php';

try {
    // SQL statement to add the comment_text column to the comments table
    $sql = "
    ALTER TABLE comments
    ADD COLUMN IF NOT EXISTS comment_text TEXT NOT NULL;";

    $pdo->exec($sql);
    echo "comment_text column added to comments table successfully or already exists.\n";

} catch (PDOException $e) {
    echo "Error adding comment_text column: " . $e->getMessage() . "\n";
}
?> 