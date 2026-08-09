<?php
/**
 * LifeGPT - Verify Email
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!empty($token)) {
    $tokenHash = hash('sha256', $token);
    
    // Fetch verification token
    $verification = DB::fetch(
        "SELECT * FROM lg_email_verifications WHERE token_hash = :hash AND verified_at IS NULL",
        ['hash' => $tokenHash]
    );
    
    if ($verification) {
        $now = date('Y-m-d H:i:s');
        if ($verification['expires_at'] < $now) {
            $error = 'This verification link has expired. Please log in and request a new one.';
        } else {
            try {
                DB::beginTransaction();
                
                // Mark verified
                DB::query(
                    "UPDATE lg_email_verifications SET verified_at = :now WHERE verification_id = :id",
                    ['now' => $now, 'id' => $verification['verification_id']]
                );
                
                // Make user active
                DB::query(
                    "UPDATE lg_users SET status = 'active' WHERE user_id = :user_id",
                    ['user_id' => $verification['user_id']]
                );
                
                // Fetch verified user details
                $user = DB::fetch(
                    "SELECT user_id, uuid, email, display_name, role, status FROM lg_users WHERE user_id = :id",
                    ['id' => $verification['user_id']]
                );
                
                DB::commit();
                
                // Log user in automatically
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_data'] = $user;
                session_regenerate_id(true);
                
                $success = "Your email has been verified successfully! Redirecting you to your dashboard...";
                
                // Redirect after 3 seconds
                header("Refresh: 3; url=" . APP_URL . "/dashboard/");
                
            } catch (Exception $e) {
                DB::rollBack();
                error_log("Verification transaction failed: " . $e->getMessage());
                $error = 'A system error occurred during verification. Please try again later.';
            }
        }
    } else {
        $error = 'Invalid or already used verification token.';
    }
} else {
    $error = 'No verification token provided.';
}

$pageTitle = "Email Verification";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 4rem auto; text-align: center;">
    <div class="card">
        <?php if (!empty($success)): ?>
            <div style="font-size: 4rem; color: var(--color-success); margin-bottom: 1.5rem;">✔</div>
            <h1>Verified!</h1>
            <p><?php echo htmlspecialchars($success); ?></p>
            <div style="margin-top: 2rem;">
                <a href="<?php echo APP_URL; ?>/dashboard/" class="btn btn-primary">Go to Dashboard Now</a>
            </div>
        <?php else: ?>
            <div style="font-size: 4rem; color: var(--color-danger); margin-bottom: 1.5rem;">✕</div>
            <h1>Verification Failed</h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <div style="margin-top: 2rem;">
                <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-primary">Return to Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
