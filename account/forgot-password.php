<?php
/**
 * LifeGPT - Forgot Password Request
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';
$mockResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    CSRF::validateRequest();
    
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $user = DB::fetch("SELECT user_id FROM lg_users WHERE email = :email", ['email' => $email]);
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            try {
                DB::insert(
                    "INSERT INTO lg_password_resets (user_id, token_hash, expires_at) 
                     VALUES (:user_id, :token_hash, :expires_at)",
                    [
                        'user_id' => $user['user_id'],
                        'token_hash' => $tokenHash,
                        'expires_at' => $expiry
                    ]
                );
                
                $mockResetLink = APP_URL . "/account/reset-password.php?token=" . $token;
                $success = "A password reset request has been processed. Since this is a local environment, we have simulated sending the email.";
                
            } catch (Exception $e) {
                error_log("Failed to insert password reset: " . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
            }
        } else {
            // Standard safety practice: don't disclose whether email exists, but we show success
            $success = "A password reset request has been processed. If the email exists, a reset link will be sent.";
        }
    }
}

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <h1 style="font-size: 2rem; margin-bottom: 1rem; text-align: center;">Reset Password</h1>
        <p class="text-sm" style="text-align: center; margin-bottom: 2rem;">Enter your account email below, and we will guide you to set a new password.</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="flex-direction: column; align-items: stretch;">
                <p><?php echo htmlspecialchars($success); ?></p>
                <?php if (!empty($mockResetLink)): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #ffffff; border-radius: 6px; border: 1px solid #bbf7d0;">
                        <p style="font-size: 0.9rem; font-weight: bold; margin-bottom: 0.5rem; color: #166534;">Local Development Reset Link:</p>
                        <a href="<?php echo $mockResetLink; ?>" style="word-break: break-all; font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: #15803d;">
                            <?php echo $mockResetLink; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form action="" method="POST">
                <?php echo CSRF::getInput(); ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Send Reset Link</button>
            </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 2rem; font-size: 1.05rem;">
            Remembered your password? <a href="<?php echo APP_URL; ?>/account/login.php">Sign In</a>
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
