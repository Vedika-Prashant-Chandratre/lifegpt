<?php
/**
 * LifeGPT - Complete Password Reset
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = '';
$isValidToken = false;
$userId = null;
$resetId = null;

if (!empty($token)) {
    $tokenHash = hash('sha256', $token);
    
    // Check if token exists, is active, and hasn't been used
    $reset = DB::fetch(
        "SELECT * FROM lg_password_resets WHERE token_hash = :hash AND used_at IS NULL",
        ['hash' => $tokenHash]
    );
    
    if ($reset) {
        $now = date('Y-m-d H:i:s');
        if ($reset['expires_at'] < $now) {
            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $isValidToken = true;
            $userId = $reset['user_id'];
            $resetId = $reset['reset_id'];
        }
    } else {
        $error = 'Invalid or already used password reset link.';
    }
} else {
    $error = 'No reset token specified.';
}

if ($isValidToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    CSRF::validateRequest();
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Password cannot be empty.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            DB::beginTransaction();
            
            // 1. Update password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            DB::query(
                "UPDATE lg_users SET password_hash = :hash WHERE user_id = :id",
                ['hash' => $passwordHash, 'id' => $userId]
            );
            
            // 2. Mark token used
            $now = date('Y-m-d H:i:s');
            DB::query(
                "UPDATE lg_password_resets SET used_at = :now WHERE reset_id = :id",
                ['now' => $now, 'id' => $resetId]
            );
            
            DB::commit();
            
            $success = 'Your password has been reset successfully! Redirecting you to login...';
            
            header("Refresh: 2; url=" . APP_URL . "/account/login.php");
            
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Failed resetting password: " . $e->getMessage());
            $error = 'A system error occurred. Please try again.';
        }
    }
}

$pageTitle = "Reset Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <h1 style="font-size: 2rem; margin-bottom: 1.5rem; text-align: center;">Set New Password</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-primary">Go to Login</a>
            </p>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($isValidToken): ?>
                <form action="" method="POST">
                    <?php echo CSRF::getInput(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Reset Password</button>
                </form>
            <?php else: ?>
                <p style="text-align: center; margin-top: 1.5rem;">
                    <a href="<?php echo APP_URL; ?>/account/forgot-password.php" class="btn btn-secondary">Request New Reset Link</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
