<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if lg_password_resets table exists
try {
    $r = DB::fetchAll("SHOW TABLES LIKE 'lg_password_resets'");
    echo count($r) > 0 ? "TABLE EXISTS\n" : "TABLE MISSING\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Create it if missing
if (count($r) === 0) {
    DB::query("CREATE TABLE IF NOT EXISTS lg_password_resets (
        reset_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token_hash (token_hash),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "TABLE CREATED\n";
}
