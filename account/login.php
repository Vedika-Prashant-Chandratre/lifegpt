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

<div style="max-width: 520px; margin: 2rem auto 4rem auto;">
    <div class="card" style="border-top: 6px solid var(--color-primary); padding: 2.5rem;">
        
        <!-- Toggle Tabs: Sign Up / Register | Log In -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem;">
            <button type="button" class="btn <?php echo ($mode === 'login') ? 'btn-primary' : 'btn-outline'; ?>" id="tabLoginBtn" onclick="switchAuthTab('login')" style="flex: 1; border-radius: var(--border-radius-pill);">
                Log In
            </button>
           <a href="<?php echo APP_URL; ?>/account/register.php"
   class="btn btn-outline"
   style="flex: 1; border-radius: var(--border-radius-pill); justify-content: center;">
    Sign Up / Register
</a>
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

    <div style="position: relative;">
        <input type="password" id="login_password" name="password"
               class="form-control"
               placeholder="Enter your password"
               required
               style="padding-right: 3rem;">

        <button type="button"
                onclick="togglePassword('login_password', this)"
                aria-label="Show password"
                style="position: absolute; right: 0.75rem; top: 50%;
                       transform: translateY(-50%);
                       border: none; background: transparent;
                       cursor: pointer; padding: 0;">
            <svg width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
</div>
            <div style="text-align: right; margin-top: -0.5rem; margin-bottom: 0.75rem;">
    <a href="<?php echo APP_URL; ?>/account/forgot-password.php" style="font-size: 0.9rem; font-weight: 600;">
        Forgot Password?
    </a>
</div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
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
                <input type="password" id="reg_password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8" onkeyup="validatePasswordCriteria(this.value)">
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

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Create Account & Continue
            </button>
        </form>

        <!-- Guest Shortcut Link -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border);">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-outline" style="width: 100%; justify-content: center;">
                Continue as Guest (No Account Required) →
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
        {
            id: 'critLen',
            valid: val.length >= 8,
            text: 'At least 8 characters long'
        },
        {
            id: 'critUpper',
            valid: /[A-Z]/.test(val),
            text: 'At least one uppercase letter'
        },
        {
            id: 'critLower',
            valid: /[a-z]/.test(val),
            text: 'At least one lowercase letter'
        },
        {
            id: 'critNumber',
            valid: /[0-9]/.test(val),
            text: 'At least one number'
        },
        {
            id: 'critSpecial',
            valid: /[^A-Za-z0-9]/.test(val),
            text: 'At least one special character'
        }
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
