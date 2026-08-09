<?php
/**
 * LifeGPT - Configuration Loader
 * Loads .env variables, establishes session security guidelines, and sets error reporting.
 */

// Secure session configuration
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Set cookie secure if HTTPS is enabled
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isSecure) {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Custom function to load environment variables from .env file
function loadEnv($dir) {
    $envPath = rtrim($dir, '/\\') . '/.env';
    if (!file_exists($envPath)) {
        return false;
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Find the first equals sign
        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            continue;
        }
        
        $key = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));
        
        // Strip surrounding quotes if present
        if (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match('/^\'([^\']*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        // Put in environment and global arrays
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
    return true;
}

// Load .env from the root directory of the application
$rootDir = dirname(__DIR__);
loadEnv($rootDir);

// Helper function to get environment variables with fallbacks
function config($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    return $default;
}

// Environment settings
$appEnv = config('APP_ENV', 'production');
define('APP_ENV', $appEnv);
define('APP_URL', rtrim(config('APP_URL', 'http://localhost/lifegpt'), '/'));
define('APP_SECRET', config('APP_SECRET', 'default_secret_please_change'));

// Set error reporting based on APP_ENV
if (APP_ENV === 'local' || APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
