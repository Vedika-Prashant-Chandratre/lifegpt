<?php
/**
 * LifeGPT - Database Setup Script
 * Connects to MySQL, creates the database, and runs migration and seed files.
 * Can be run from the command line or accessed via browser for local setup.
 */

// Define absolute paths
$baseDir = dirname(__DIR__);
require_once $baseDir . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=========================================\n";
echo "       LifeGPT Database Setup\n";
echo "=========================================\n\n";

$host = config('DB_HOST', 'localhost');
$port = config('DB_PORT', '3306');
$dbname = config('DB_NAME', 'lifegpt');
$username = config('DB_USER', 'root');
$password = config('DB_PASS', '');

try {
    // 1. Connect without DB name to create the database if needed
    echo "Connecting to MySQL server at $host:$port...\n";
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Connected successfully!\n\n";
    
    // 2. Create database
    echo "Creating database '$dbname' if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' ready.\n\n";
    
    // 3. Connect directly to the database
    $pdo->exec("USE `$dbname`");
    
    // 4. Run Migration
    $migrationFile = $baseDir . '/database/migrations/001_initial_schema.sql';
    echo "Running migration script: " . basename($migrationFile) . "...\n";
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found at: $migrationFile");
    }
    
    $migrationSql = file_get_contents($migrationFile);
    // Execute SQL queries (PDO exec doesn't execute multi-queries reliably, so we split or run direct query)
    // For general native mysql scripts, standard exec works if multi-queries is supported, or we do query.
    // Let's run it directly. MySQL supports multiple queries in exec if enabled.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); // Enable multi-query support
    $pdo->exec($migrationSql);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Restore original
    echo "Migration completed successfully!\n\n";
    
    // 5. Run Seeds
    $seedFile = $baseDir . '/database/seeds/seed_personas_and_topics.sql';
    echo "Running seed script: " . basename($seedFile) . "...\n";
    if (!file_exists($seedFile)) {
        throw new Exception("Seed file not found at: $seedFile");
    }
    
    $seedSql = file_get_contents($seedFile);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->exec($seedSql);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    echo "Seeding completed successfully!\n\n";
    
    echo "=========================================\n";
    echo "Database setup completed successfully!\n";
    echo "Your LifeGPT instance is ready to use.\n";
    echo "=========================================\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    echo "\nFailed to complete setup.\n";
}
