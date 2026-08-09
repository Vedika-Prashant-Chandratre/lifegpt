<?php
/**
 * LifeGPT - Database Connection Handler
 * Manages database connection and secure query executions using PDO.
 */

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $instance = null;

    /**
     * Get the database connection instance (Singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = config('DB_HOST', 'localhost');
            $port = config('DB_PORT', '3306');
            $dbname = config('DB_NAME', 'lifegpt');
            $username = config('DB_USER', 'root');
            $password = config('DB_PASS', '');

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // True prepared statements
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Log error silently, do not show credentials or stack trace to user
                error_log("Database Connection Failure: " . $e->getMessage());
                
                // Show a clean friendly error message
                self::showFriendlyError();
            }
        }

        return self::$instance;
    }

    /**
     * Helper to run a parameterized query and return the statement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Failure: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params));
            self::showFriendlyError();
        }
    }

    /**
     * Helper to run a parameterized query and return a single row
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Helper to run a parameterized query and return all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Helper to insert a row and return the last inserted ID
     */
    public static function insert(string $sql, array $params = []): string {
        self::query($sql, $params);
        return self::getConnection()->lastInsertId();
    }

    /**
     * Transaction Helpers
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool {
        return self::getConnection()->rollBack();
    }

    /**
     * Render a clean, friendly error message to the user
     */
    private static function showFriendlyError(): void {
        http_response_code(500);
        // If this is an API call, return JSON. Otherwise return a nice HTML page.
        $isApi = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || 
                 (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                 (isset($_SERVER['REQUEST_URI']) && stripos($_SERVER['REQUEST_URI'], '/api/') !== false);

        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'A database error occurred. Please try again later.'
            ]);
        } else {
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>System Error - LifeGPT</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px 20px; text-align: center; }
                    .container { max-width: 500px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
                    h1 { color: #1e3a8a; font-size: 24px; margin-bottom: 16px; }
                    p { font-size: 16px; line-height: 1.5; color: #64748b; margin-bottom: 24px; }
                    .btn { display: inline-block; background: #1e3a8a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 16px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Something went wrong</h1>
                    <p>We are experiencing a temporary technical issue. Please rest assured your data is safe, and we are working to resolve the issue as quickly as possible.</p>
                    <a href="/lifegpt/" class="btn">Return to Homepage</a>
                </div>
            </body>
            </html>';
        }
        exit;
    }
}
