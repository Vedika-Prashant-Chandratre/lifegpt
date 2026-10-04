<?php
/**
 * LifeGPT - Forgot Password Request
 * Sends password reset link to user's email address via MailService.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';
$sentEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
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
                $displayName = htmlspecialchars($user['display_name'] ?? 'Member');
                
                // Construct branded HTML email
                $subject = "LifeGPT — Password Reset Link";
                $htmlBody = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Password Reset</title>
                </head>
                <body style='margin:0; padding:40px 20px; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background-color: #f1f5f9; color: #1e293b;'>
                    <div style='max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 14px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-top: 5px solid #064e3b;'>
                        <div style='margin-bottom: 24px;'>
                            <h2 style='color: #064e3b; margin: 0 0 8px 0; font-size: 24px;'>Reset Your Password</h2>
                            <p style='color: #64748b; font-size: 14px; margin: 0;'>LifeGPT Wisdom Archive Account</p>
                        </div>
                        <p style='font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 20px;'>
                            Hello <strong>{$displayName}</strong>,<br><br>
                            We received a request to reset the password associated with this email address. Click the button below to choose a new password. This link is valid for <strong>1 hour</strong>.
                        </p>
                        <div style='text-align: center; margin: 32px 0;'>
                            <a href='{$resetLink}' style='display: inline-block; background-color: #064e3b; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 2px 4px rgba(6,78,59,0.2);'>Reset Password</a>
                        </div>
                        <p style='color: #64748b; font-size: 13px; line-height: 1.5; margin-bottom: 24px;'>
                            If the button above does not work, copy and paste this URL into your web browser:<br>
                            <a href='{$resetLink}' style='color: #064e3b; word-break: break-all;'>{$resetLink}</a>
                        </p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 28px 0;'>
                        <p style='color: #94a3b8; font-size: 12px; line-height: 1.5; margin: 0;'>
                            If you did not request a password reset, you can safely disregard this email. Your password will remain unchanged.<br><br>
                            &mdash; <strong>LifeGPT</strong> &bull; A FiftyIsNifty research initiative
                        </p>
                    </div>
                </body>
                </html>";

                // Dispatch email via MailService
                MailService::send($email, $subject, $htmlBody);
                $sentEmail = $email;
                $success = "A password reset link has been sent to your email address.";
                
            } catch (Exception $e) {
                error_log("Failed to insert password reset: " . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
            }
        } else {
            // Consistent response for privacy
            $sentEmail = $email;
            $success = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card" style="border-top: 5px solid var(--color-primary); padding: 2.25rem;">
        <h1 style="font-size: 2rem; margin-bottom: 0.75rem; text-align: center; color: var(--color-primary);">Reset Password</h1>
        <p class="text-sm" style="text-align: center; margin-bottom: 2rem; color: var(--color-text-muted);">Enter your account email below and we will send a password reset link directly to your inbox.</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="padding: 1.25rem; border-radius: var(--radius-md); background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <span style="font-size: 1.5rem; line-height: 1;">&#9993;</span>
                    <div>
                        <strong style="display: block; font-size: 1.05rem; margin-bottom: 0.35rem; color: #064e3b;">Email Sent Successfully</strong>
                        <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">
                            A password reset link has been dispatched to <strong><?php echo htmlspecialchars($sentEmail); ?></strong>. Please check your inbox (and spam/junk folder) and click the link to reset your password.
                        </p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-primary" style="width: 100%;">Return to Sign In</a>
            </div>
        <?php else: ?>
            <form action="" method="POST">
                <?php echo CSRF::getInput(); ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">Account Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Send Reset Link to Email</button>
            </form>
            
            <p style="text-align: center; margin-top: 2rem; font-size: 0.95rem;">
                Remembered your password? <a href="<?php echo APP_URL; ?>/account/login.php" style="color: var(--color-primary); font-weight: 600;">Sign In</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
