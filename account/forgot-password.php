<?php
/**
 * LifeGPT - Forgot Password Request
 * Sends a real password reset email using PHP mail().
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $user = DB::fetch("SELECT user_id, display_name FROM lg_users WHERE email = :email", ['email' => $email]);
        
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
                
                $resetLink = APP_URL . "/account/reset-password.php?token=" . $token;
                $displayName = $user['display_name'] ?? 'Member';
                
                // Send the actual email
                $to = $email;
                $subject = "LifeGPT — Password Reset Request";
                $htmlBody = "
                <html>
                <head><title>Password Reset</title></head>
                <body style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background: #f1f5f9; padding: 40px 20px;'>
                    <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);'>
                        <h2 style='color: #064e3b; margin-bottom: 8px;'>Password Reset Request</h2>
                        <p style='color: #475569; font-size: 15px; line-height: 1.6;'>
                            Hi <strong>{$displayName}</strong>,<br><br>
                            We received a request to reset your LifeGPT password. Click the button below to set a new password. This link will expire in 1 hour.
                        </p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$resetLink}' style='display: inline-block; background: #064e3b; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px;'>Reset My Password</a>
                        </div>
                        <p style='color: #94a3b8; font-size: 13px; line-height: 1.5;'>
                            If the button doesn't work, copy and paste this link into your browser:<br>
                            <a href='{$resetLink}' style='color: #064e3b; word-break: break-all;'>{$resetLink}</a>
                        </p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;'>
                        <p style='color: #94a3b8; font-size: 12px;'>
                            If you did not request this reset, you can safely ignore this email. Your password will remain unchanged.<br><br>
                            &mdash; The LifeGPT Team (A FiftyIsNifty research initiative)
                        </p>
                    </div>
                </body>
                </html>";
                
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: " . (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'LifeGPT') . " <" . (defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@lifegpt.local') . ">\r\n";
                $headers .= "Reply-To: noreply@lifegpt.local\r\n";
                
                $mailSent = @mail($to, $subject, $htmlBody, $headers);
                
                if ($mailSent) {
                    $success = "A password reset link has been sent to your email address. Please check your inbox (and spam folder).";
                } else {
                    // Fallback: show the link directly for local/dev environments
                    $success = "EMAIL_FALLBACK";
                }
                
            } catch (Exception $e) {
                error_log("Failed to insert password reset: " . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
            }
        } else {
            // Don't disclose whether the email exists
            $success = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <h1 style="font-size: 2rem; margin-bottom: 1rem; text-align: center;">Reset Password</h1>
        <p class="text-sm" style="text-align: center; margin-bottom: 2rem;">Enter your account email below, and we will send you a link to set a new password.</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success) && $success !== 'EMAIL_FALLBACK'): ?>
            <div class="alert alert-success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-primary" style="width: 100%;">Back to Login</a>
            </p>
        <?php elseif ($success === 'EMAIL_FALLBACK'): ?>
            <div class="alert alert-success" style="flex-direction: column; align-items: stretch;">
                <p>A password reset has been generated. Email delivery is not configured on this server, so please use the link below:</p>
                <?php
                // Reconstruct the link from the token we just created
                $resetLink = APP_URL . "/account/reset-password.php?token=" . $token;
                ?>
                <div style="margin-top: 1rem; padding: 1rem; background: #ffffff; border-radius: 6px; border: 1px solid #bbf7d0;">
                    <p style="font-size: 0.9rem; font-weight: bold; margin-bottom: 0.5rem; color: #166534;">Your Password Reset Link:</p>
                    <a href="<?php echo $resetLink; ?>" style="word-break: break-all; font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: #15803d;">
                        <?php echo $resetLink; ?>
                    </a>
                </div>
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
