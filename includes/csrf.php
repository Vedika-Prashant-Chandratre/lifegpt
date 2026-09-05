<?php
/**
 * LifeGPT - CSRF Protection Manager
 *
 * Two validation modes:
 *   1. validateRequest() - session-based token for HTML form POSTs (login, register, consent, etc.)
 *   2. validateAjax()    - origin/content-type check for JSON AJAX API calls
 *
 * Why validateAjax() for API routes?
 *   Session tokens break on serverless platforms (Vercel) because each function
 *   invocation may run in a separate process with no shared session. For JSON-only
 *   AJAX endpoints we instead check:
 *     a) Content-Type: application/json
 *     b) X-Requested-With: XMLHttpRequest
 *   Browsers cannot set custom headers on cross-origin requests without a CORS
 *   preflight, so this is sufficient CSRF protection for same-origin AJAX calls.
 */

class CSRF {

    // -------------------------------------------------------------------------
    // Session-Based Token (HTML Forms)
    // -------------------------------------------------------------------------

    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function getInput(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

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

    public static function validateRequest(): void {
        $token = null;

        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } else {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'x-csrf-token') {
                    $token = $value;
                    break;
                }
            }
        }

        if (!self::validate($token)) {
            self::blockRequest();
        }
    }

    // -------------------------------------------------------------------------
    // AJAX / JSON API Validation (Serverless-safe, no session needed)
    // -------------------------------------------------------------------------

    public static function validateAjax(): void {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $isJson = stripos($contentType, 'application/json') !== false;

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $requestedWith = '';
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'x-requested-with') {
                $requestedWith = $value;
                break;
            }
        }
        $isXhr = strtolower($requestedWith) === 'xmlhttprequest';

        if (!$isJson || !$isXhr) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Invalid request. Must be a JSON AJAX call.'
            ]);
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // Shared block helper
    // -------------------------------------------------------------------------

    private static function blockRequest(): void {
        http_response_code(403);

        $isApi = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
               || (isset($_SERVER['HTTP_ACCEPT'])  && stripos($_SERVER['HTTP_ACCEPT'],  'application/json') !== false)
               || (isset($_SERVER['REQUEST_URI'])  && stripos($_SERVER['REQUEST_URI'],  '/api/')  !== false);

        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token. Request blocked for security.']);
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Forbidden - LifeGPT</title>
<style>body{font-family:sans-serif;background:#fff1f2;color:#9f1239;padding:40px 20px;text-align:center}
.c{max-width:500px;margin:0 auto;background:#fff;padding:40px;border-radius:12px;border-top:4px solid #be123c}
h1{font-size:24px}.btn{background:#be123c;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none}
</style></head><body><div class="c"><h1>Security Verification Failed</h1>
<p>The security token was missing or invalid. Please go back and try again.</p>
<a href="javascript:history.back()" class="btn">Go Back</a></div></body></html>';
        }
        exit;
    }
}