<?php
require_once 'config/database.php';

try {
    // Check if shares table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'shares'");
    if ($checkTable->rowCount() > 0) {
        // Check if the required columns exist
        $checkColumns = $pdo->query("SHOW COLUMNS FROM shares");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        // Add missing columns if they don't exist
        if (!in_array('user_id', $columns)) {
            $pdo->exec("ALTER TABLE shares ADD COLUMN user_id INT NOT NULL AFTER id");
        }
        if (!in_array('photo_id', $columns)) {
            $pdo->exec("ALTER TABLE shares ADD COLUMN photo_id INT NOT NULL AFTER user_id");
        }
        if (!in_array('created_at', $columns)) {
            $pdo->exec("ALTER TABLE shares ADD COLUMN created_at DATETIME NOT NULL AFTER photo_id");
        }

        // Add foreign keys if they don't exist
        $checkForeignKeys = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'shares' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $foreignKeys = $checkForeignKeys->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('shares_user_id_fk', $foreignKeys)) {
            $pdo->exec("ALTER TABLE shares ADD CONSTRAINT shares_user_id_fk FOREIGN KEY (user_id) REFERENCES users(id)");
        }
        if (!in_array('shares_photo_id_fk', $foreignKeys)) {
            $pdo->exec("ALTER TABLE shares ADD CONSTRAINT shares_photo_id_fk FOREIGN KEY (photo_id) REFERENCES photos(id)");
        }

        echo "Shares table updated successfully!";
    } else {
        // Create the shares table if it doesn't exist
        $pdo->exec("
            CREATE TABLE shares (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                photo_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (photo_id) REFERENCES photos(id)
            )
        ");
        echo "Shares table created successfully!";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 