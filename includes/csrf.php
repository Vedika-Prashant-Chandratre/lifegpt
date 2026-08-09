<?php
/**
 * LifeGPT - CSRF Protection Manager
 * Generates and validates CSRF tokens to secure state-changing actions.
 */

class CSRF {
    /**
     * Get or generate the current CSRF token for the session
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Get a pre-formatted HTML input field with the CSRF token
     */
    public static function getInput(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate a token against the session token
     */
    public static function validate(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }

    /**
     * Automatically validate standard POST requests or AJAX requests.
     * Throws an exception or returns a JSON error on failure.
     */
    public static function validateRequest(): void {
        $token = null;
        
        // 1. Check POST body
        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } 
        // 2. Check Request Headers (commonly X-CSRF-Token or X-XSRF-Token for AJAX)
        else {
            $headers = getallheaders();
            if (isset($headers['X-CSRF-Token'])) {
                $token = $headers['X-CSRF-Token'];
            } elseif (isset($headers['x-csrf-token'])) {
                $token = $headers['x-csrf-token'];
            }
        }
        
        if (!self::validate($token)) {
            http_response_code(403);
            
            $isApi = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || 
                     (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                     (isset($_SERVER['REQUEST_URI']) && stripos($_SERVER['REQUEST_URI'], '/api/') !== false);
            
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid CSRF token. Request blocked for security.'
                ]);
            } else {
                echo '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Access Forbidden - LifeGPT</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fff1f2; color: #9f1239; padding: 40px 20px; text-align: center; }
                        .container { max-width: 500px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-top: 4px solid #be123c; }
                        h1 { font-size: 24px; margin-bottom: 16px; }
                        p { font-size: 16px; line-height: 1.5; color: #64748b; margin-bottom: 24px; }
                        .btn { display: inline-block; background: #be123c; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 500; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Security Verification Failed</h1>
                        <p>The security validation token for this request was missing or invalid. To protect your details, this request has been cancelled.</p>
                        <a href="javascript:history.back()" class="btn">Go Back</a>
                    </div>
                </body>
                </html>';
            }
            exit;
        }
    }
}
