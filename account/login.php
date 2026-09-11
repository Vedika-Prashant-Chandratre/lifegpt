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
                <input type="password" id="login_password" name="password" class="form-control" placeholder="Enter your password" required>
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

function validatePasswordCriteria(val) {
    const critLen = document.getElementById('critLen');
    if (val.length >= 8) {
        critLen.style.color = 'var(--color-success)';
        critLen.innerHTML = '✔ At least 8 characters long';
    } else {
        critLen.style.color = '#9CA3AF';
        critLen.innerHTML = '• At least 8 characters long';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
