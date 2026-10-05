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
            
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            DB::query(
                "UPDATE lg_users SET password_hash = :hash WHERE user_id = :id",
                ['hash' => $passwordHash, 'id' => $userId]
            );
            
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

<style>
    .pw-toggle-wrap { position: relative; }
    .pw-toggle-wrap input { padding-right: 2.75rem; }
    .pw-toggle-btn {
        position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; padding: 0.25rem;
        color: var(--color-text-muted); font-size: 1.15rem; line-height: 1;
    }
    .pw-toggle-btn:hover { color: var(--color-primary); }
    .pw-criteria-item { display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.15rem; transition: color 0.2s ease; }
    .pw-criteria-item .pw-icon { font-size: 0.85rem; width: 1.1rem; text-align: center; }
</style>

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
                        <div class="pw-toggle-wrap">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 chars, uppercase, lowercase, number, special" required minlength="8" onkeyup="validatePasswordCriteria(this.value)">
                            <button type="button" class="pw-toggle-btn" onclick="togglePassword('password', this)" title="Show/Hide Password">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Password Criteria -->
                    <div id="passwordCriteriaBox" style="background: var(--color-bg-base); padding: 0.85rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; border: 1px solid var(--color-border);">
                        <strong style="color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Password Requirements:</strong>
                        <div class="pw-criteria-item" id="critLen"><span class="pw-icon">&#9675;</span> At least 8 characters long</div>
                        <div class="pw-criteria-item" id="critUpper"><span class="pw-icon">&#9675;</span> At least one uppercase letter (A-Z)</div>
                        <div class="pw-criteria-item" id="critLower"><span class="pw-icon">&#9675;</span> At least one lowercase letter (a-z)</div>
                        <div class="pw-criteria-item" id="critNum"><span class="pw-icon">&#9675;</span> At least one number (0-9)</div>
                        <div class="pw-criteria-item" id="critSpecial"><span class="pw-icon">&#9675;</span> At least one special character (@, #, $, !, etc.)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="pw-toggle-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                            <button type="button" class="pw-toggle-btn" onclick="togglePassword('confirm_password', this)" title="Show/Hide Password">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
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
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');
    if (input.type === 'password') {
        input.type = 'text';
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = 'inline';
    } else {
        input.type = 'password';
        eyeOpen.style.display = 'inline';
        eyeClosed.style.display = 'none';
    }
}

function validatePasswordCriteria(val) {
    const checks = [
        { id: 'critLen',     test: val.length >= 8 },
        { id: 'critUpper',   test: /[A-Z]/.test(val) },
        { id: 'critLower',   test: /[a-z]/.test(val) },
        { id: 'critNum',     test: /[0-9]/.test(val) },
        { id: 'critSpecial', test: /[^A-Za-z0-9]/.test(val) },
    ];
    checks.forEach(function(c) {
        const el = document.getElementById(c.id);
        const icon = el.querySelector('.pw-icon');
        if (c.test) {
            el.style.color = '#16a34a';
            icon.innerHTML = '&#10003;';
        } else {
            el.style.color = '#9CA3AF';
            icon.innerHTML = '&#9675;';
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
