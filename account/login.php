<?php
/**
 * LifeGPT - Member Authentication (Log In / Sign Up)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$mode = $_GET['mode'] ?? 'login';

if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL . "/account/choice.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $loginResult = Auth::login($email, $password);
        if ($loginResult['success']) {
            header("Location: " . APP_URL . "/account/choice.php");
            exit;
        } else {
            $error = $loginResult['error'];
        }
    } elseif ($action === 'register') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $displayName = $_POST['display_name'] ?? 'Member';
        
        $regResult = Auth::register($email, $password, $displayName);
        if ($regResult['success']) {
            Auth::login($email, $password);
            header("Location: " . APP_URL . "/account/profile.php");
            exit;
        } else {
            $error = $regResult['error'];
            $mode = 'register';
        }
    }
}

$pageTitle = "Member Authentication";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .pw-toggle-wrap {
        position: relative;
    }
    .pw-toggle-wrap input {
        padding-right: 2.75rem;
    }
    .pw-toggle-btn {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        color: var(--color-text-muted);
        font-size: 1.15rem;
        line-height: 1;
    }
    .pw-toggle-btn:hover {
        color: var(--color-primary);
    }
    .pw-criteria-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.15rem;
        transition: color 0.2s ease;
    }
    .pw-criteria-item .pw-icon {
        font-size: 0.85rem;
        width: 1.1rem;
        text-align: center;
    }
</style>

<div style="max-width: 520px; margin: 2rem auto 4rem auto;">
    <div class="card" style="border-top: 6px solid var(--color-primary); padding: 2.5rem;">
        
        <!-- Toggle Tabs: Sign Up / Register | Log In -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem;">
            <button type="button" class="btn <?php echo ($mode === 'login') ? 'btn-primary' : 'btn-outline'; ?>" id="tabLoginBtn" onclick="switchAuthTab('login')" style="flex: 1; border-radius: var(--border-radius-pill);">
                Log In
            </button>
            <button type="button" class="btn <?php echo ($mode === 'register') ? 'btn-primary' : 'btn-outline'; ?>" id="tabRegisterBtn" onclick="switchAuthTab('register')" style="flex: 1; border-radius: var(--border-radius-pill);">
                Sign Up / Register
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Log In Form -->
        <form action="" method="POST" id="loginAuthForm" style="display: <?php echo ($mode === 'login') ? 'block' : 'none'; ?>;">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="login_email" class="form-label">Email Address</label>
                <input type="email" id="login_email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>

            <div class="form-group">
                <label for="login_password" class="form-label">Password</label>
                <div class="pw-toggle-wrap">
                    <input type="password" id="login_password" name="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="pw-toggle-btn" onclick="togglePassword('login_password', this)" title="Show/Hide Password">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 1rem;">
                <a href="<?php echo APP_URL; ?>/account/forgot-password.php" style="font-size: 0.9rem; color: var(--color-primary); text-decoration: none; font-weight: 500;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Log In to LifeGPT
            </button>
        </form>

        <!-- Sign Up Form -->
        <form action="" method="POST" id="registerAuthForm" style="display: <?php echo ($mode === 'register') ? 'block' : 'none'; ?>;">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="reg_display_name" class="form-label">Full Name or Display Name</label>
                <input type="text" id="reg_display_name" name="display_name" class="form-control" placeholder="e.g. Grandma Helen" required>
            </div>

            <div class="form-group">
                <label for="reg_email" class="form-label">Email Address</label>
                <input type="email" id="reg_email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>

            <div class="form-group">
                <label for="reg_password" class="form-label">Password</label>
                <div class="pw-toggle-wrap">
                    <input type="password" id="reg_password" name="password" class="form-control" placeholder="Min 8 chars, uppercase, lowercase, number, special" required minlength="8" onkeyup="validatePasswordCriteria(this.value)">
                    <button type="button" class="pw-toggle-btn" onclick="togglePassword('reg_password', this)" title="Show/Hide Password">
                        <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <!-- Password Criteria Validation Box -->
            <div id="passwordCriteriaBox" style="background: var(--color-bg-base); padding: 0.85rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; border: 1px solid var(--color-border);">
                <strong style="color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Password Requirements:</strong>
                <div class="pw-criteria-item" id="critLen"><span class="pw-icon">&#9675;</span> At least 8 characters long</div>
                <div class="pw-criteria-item" id="critUpper"><span class="pw-icon">&#9675;</span> At least one uppercase letter (A-Z)</div>
                <div class="pw-criteria-item" id="critLower"><span class="pw-icon">&#9675;</span> At least one lowercase letter (a-z)</div>
                <div class="pw-criteria-item" id="critNum"><span class="pw-icon">&#9675;</span> At least one number (0-9)</div>
                <div class="pw-criteria-item" id="critSpecial"><span class="pw-icon">&#9675;</span> At least one special character (@, #, $, !, etc.)</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;" id="registerSubmitBtn">
                Create Account & Continue
            </button>
        </form>

        <!-- Guest Shortcut Link -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border);">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-outline" style="width: 100%; justify-content: center;">
                Continue as Guest (No Account Required) &rarr;
            </a>
        </div>
    </div>
</div>

<script>
function switchAuthTab(target) {
    const loginForm = document.getElementById('loginAuthForm');
    const regForm = document.getElementById('registerAuthForm');
    const tabLogin = document.getElementById('tabLoginBtn');
    const tabReg = document.getElementById('tabRegisterBtn');

    if (target === 'login') {
        loginForm.style.display = 'block';
        regForm.style.display = 'none';
        tabLogin.classList.remove('btn-outline');
        tabLogin.classList.add('btn-primary');
        tabReg.classList.remove('btn-primary');
        tabReg.classList.add('btn-outline');
    } else {
        loginForm.style.display = 'none';
        regForm.style.display = 'block';
        tabReg.classList.remove('btn-outline');
        tabReg.classList.add('btn-primary');
        tabLogin.classList.remove('btn-primary');
        tabLogin.classList.add('btn-outline');
    }
}

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

    let allPassed = true;
    checks.forEach(function(c) {
        const el = document.getElementById(c.id);
        const icon = el.querySelector('.pw-icon');
        if (c.test) {
            el.style.color = '#16a34a';
            icon.innerHTML = '&#10003;';
        } else {
            el.style.color = '#9CA3AF';
            icon.innerHTML = '&#9675;';
            allPassed = false;
        }
    });
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
