<?php
/**
 * LifeGPT - Authentication Manager
 * Handles login, registration, roles, and session state.
 */

require_once __DIR__ . '/db.php';

class Auth {
    /**
     * Check if a user session is active
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get details of the currently logged-in user
     */
    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        // Return user data from session or refresh from database
        if (!isset($_SESSION['user_data'])) {
            $user = DB::fetch(
                "SELECT user_id, uuid, email, username, display_name, age, country, profession, gender, about_me, role, status FROM lg_users WHERE user_id = :id",
                ['id' => $_SESSION['user_id']]
            );
            if ($user) {
                $_SESSION['user_data'] = $user;
            } else {
                self::logout();
                return null;
            }
        }

        return $_SESSION['user_data'];
    }

    /**
     * Check if the current user has admin privileges
     */
    public static function isAdmin(): bool {
        $user = self::getCurrentUser();
        return $user !== null && $user['role'] === 'admin';
    }

    /**
     * Register a new user
     */
    public static function register(string $email, string $password, string $displayName): array {
        $email = trim(strtolower($email));
        $displayName = trim($displayName);

        // Server-side validation
        if (empty($email) || empty($password) || empty($displayName)) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please enter a valid email address.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
        }

        // Check if email already exists
        $existing = DB::fetch("SELECT user_id FROM lg_users WHERE email = :email", ['email' => $email]);
        if ($existing) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        // Generate UUID
        $uuid = self::generateUUID();
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Perform insert
        try {
            DB::insert(
                "INSERT INTO lg_users (uuid, email, password_hash, display_name, role, status) 
                 VALUES (:uuid, :email, :password_hash, :display_name, 'member', 'active')",
                [
                    'uuid' => $uuid,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'display_name' => $displayName
                ]
            );

            // Fetch created user ID
            $user = DB::fetch("SELECT user_id, uuid, email, username, display_name, age, country, profession, gender, about_me, role, status FROM lg_users WHERE email = :email", ['email' => $email]);
            
            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Registration failed due to a system error. Please try again.'];
        }
    }

    /**
     * Authenticate and log in a user
     */
    public static function login(string $email, string $password): array {
        $email = trim(strtolower($email));

        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Email and password are required.'];
        }

        $user = DB::fetch("SELECT * FROM lg_users WHERE email = :email", ['email' => $email]);

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if ($user['status'] === 'suspended') {
            return ['success' => false, 'error' => 'Your account has been suspended. Please contact support.'];
        }

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Establish session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_data'] = [
                'user_id' => $user['user_id'],
                'uuid' => $user['uuid'],
                'email' => $user['email'],
                'username' => $user['username'] ?? null,
                'display_name' => $user['display_name'],
                'age' => $user['age'] ?? null,
                'country' => $user['country'] ?? null,
                'profession' => $user['profession'] ?? null,
                'gender' => $user['gender'] ?? null,
                'about_me' => $user['about_me'] ?? null,
                'role' => $user['role'],
                'status' => $user['status']
            ];

            // Re-generate session ID to prevent session fixation
            session_regenerate_id(true);

            return ['success' => true, 'user' => $_SESSION['user_data']];
        }

        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    /**
     * Terminate the user session
     */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Require a user to be logged in
     */
    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: " . APP_URL . "/account/login.php");
            exit;
        }
    }

    /**
     * Require a user to be an Administrator
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo "Access Denied: You do not have administrator permissions.";
            exit;
        }
    }

    /**
     * Generate a secure v4 UUID
     */
    public static function generateUUID(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100 (v4)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate ownership / access to an interview session for registered users and anonymous guests.
     * Auto-rehydrates guest session when a valid token is provided.
     */
    public static function validateInterviewAccess(array $interview, ?string $guestToken = null): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Registered user verification
        if ($interview['user_id'] !== null) {
            return self::isLoggedIn() && (int)$_SESSION['user_id'] === (int)$interview['user_id'];
        }

        // Anonymous guest verification: check active session match
        $sessionActiveId = $_SESSION['active_interview_id'] ?? null;
        if ((int)$sessionActiveId === (int)$interview['interview_id']) {
            return true;
        }

        // Check guest token sources: parameter, headers, session, POST/GET
        $token = $guestToken
            ?: ($_SERVER['HTTP_X_GUEST_TOKEN'] ?? null)
            ?: ($_SESSION['guest_return_token'] ?? null)
            ?: ($_POST['guest_token'] ?? null)
            ?: ($_GET['guest_token'] ?? null);

        if (empty($token)) {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        $tokenRow = DB::fetch(
            "SELECT token_id FROM lg_guest_access_tokens 
             WHERE interview_id = :id AND token_hash = :hash AND revocation_status = 0 AND expiry > NOW()",
            ['id' => $interview['interview_id'], 'hash' => $tokenHash]
        );

        if ($tokenRow) {
            // Re-hydrate session state for resilient multi-turn continuity
            $_SESSION['active_interview_id'] = (int)$interview['interview_id'];
            $_SESSION['active_interview_uuid'] = $interview['uuid'];
            $_SESSION['guest_return_token'] = $token;
            return true;
        }

        return false;
    }
}
