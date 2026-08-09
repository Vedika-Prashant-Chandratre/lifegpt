<?php
/**
 * LifeGPT - Seed Admin Account
 * Creates a default administrator account if one does not exist.
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/db.php';
require_once $baseDir . '/includes/auth.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=========================================\n";
echo "      LifeGPT Admin User Seeder\n";
echo "=========================================\n\n";

$email = 'admin@lifegpt.local';
$password = 'adminpassword123';
$displayName = 'LifeGPT Administrator';

try {
    // Check if the admin already exists
    $existing = DB::fetch("SELECT user_id FROM lg_users WHERE email = :email", ['email' => $email]);
    
    if ($existing) {
        echo "Admin account '$email' already exists. Skipping.\n";
    } else {
        $uuid = Auth::generateUUID();
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        DB::insert(
            "INSERT INTO lg_users (uuid, email, password_hash, display_name, role, status) 
             VALUES (:uuid, :email, :password_hash, :display_name, 'admin', 'active')",
            [
                'uuid' => $uuid,
                'email' => $email,
                'password_hash' => $passwordHash,
                'display_name' => $displayName
            ]
        );
        
        echo "Admin account created successfully!\n";
        echo "Email: $email\n";
        echo "Password: $password\n\n";
        echo "Please change this password immediately after logging in.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
