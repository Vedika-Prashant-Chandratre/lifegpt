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
} elseif (!preg_match('/[A-Z]/', $password)) {
    $error = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $error = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $error = 'Password must contain at least one number.';
} elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $error = 'Password must contain at least one special character.';
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
                        <div style="position: relative;">
    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8" onkeyup="validatePasswordCriteria(this.value)" style="padding-right: 3rem;">
    <button type="button" onclick="togglePassword('password', this)" aria-label="Show password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; font-size: 1.1rem;">👁</button>
</div>
                    </div>

                    <!-- Password Criteria Validation Box -->
<div id="passwordCriteriaBox" style="background: var(--color-bg-base); padding: 0.85rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; border: 1px solid var(--color-border);">
    <strong style="color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Password Requirements:</strong>
    <div id="critLen" style="color: #9CA3AF;">• At least 8 characters long</div>
    <div id="critUpper" style="color: #9CA3AF;">• At least one uppercase letter</div>
    <div id="critLower" style="color: #9CA3AF;">• At least one lowercase letter</div>
    <div id="critNumber" style="color: #9CA3AF;">• At least one number</div>
    <div id="critSpecial" style="color: #9CA3AF;">• At least one special character</div>
</div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div style="position: relative;">
    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required style="padding-right: 3rem;">
    <button type="button" onclick="togglePassword('confirm_password', this)" aria-label="Show password" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; font-size: 1.1rem;">👁</button>
</div>
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

<script>

    function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    const eyeOpen = `
        <svg width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>`;

    const eyeOff = `
        <svg width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3l18 18"/>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
            <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a16.4 16.4 0 0 1-2.1 3.2"/>
            <path d="M6.6 6.6C3.6 8.6 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/>
        </svg>`;

    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = eyeOff;
        button.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        button.innerHTML = eyeOpen;
        button.setAttribute('aria-label', 'Show password');
    }
}

function validatePasswordCriteria(val) {
    const criteria = [
        { id: 'critLen', valid: val.length >= 8, text: 'At least 8 characters long' },
        { id: 'critUpper', valid: /[A-Z]/.test(val), text: 'At least one uppercase letter' },
        { id: 'critLower', valid: /[a-z]/.test(val), text: 'At least one lowercase letter' },
        { id: 'critNumber', valid: /[0-9]/.test(val), text: 'At least one number' },
        { id: 'critSpecial', valid: /[^A-Za-z0-9]/.test(val), text: 'At least one special character' }
    ];

    criteria.forEach(function (criterion) {
        const element = document.getElementById(criterion.id);

        if (criterion.valid) {
            element.style.color = 'var(--color-success)';
            element.innerHTML = '✔ ' + criterion.text;
        } else {
            element.style.color = '#9CA3AF';
            element.innerHTML = '• ' + criterion.text;
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
