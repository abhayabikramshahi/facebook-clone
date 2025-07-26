<?php
require_once 'config/database.php';

try {
    // SQL statement to create the comments table
    $sql = "
    CREATE TABLE IF NOT EXISTS comments (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        photo_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        comment_text TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );";

    $pdo->exec($sql);
    echo "Comments table created successfully or already exists.";

} catch (PDOException $e) {
    echo "Error creating comments table: " . $e->getMessage();
}
?> 