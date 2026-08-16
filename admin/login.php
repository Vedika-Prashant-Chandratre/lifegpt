<?php
/**
 * LifeGPT - Admin System Administration Login
 * Designed to strictly match Image 3 prototype reference.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';

if (Auth::isLoggedIn() && Auth::isAdmin()) {
    header("Location: " . APP_URL . "/admin/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $loginResult = Auth::login($email, $password);
    
    if ($loginResult['success']) {
        if (Auth::isAdmin()) {
            header("Location: " . APP_URL . "/admin/");
            exit;
        } else {
            Auth::logout();
            $error = 'Access Denied: Your account does not have administrator privileges.';
        }
    } else {
        $error = $loginResult['error'];
    }
}
?>
<?php
$pageTitle = "System Administration Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 520px; margin: 2rem auto 4rem auto;">
    <div class="admin-card-container">
        <!-- Header Section (Matching Image 3 Deep Forest Green Header) -->
        <div class="admin-card-header">
            <div class="admin-lock-badge">🔐</div>
            <div class="admin-brand-tag">🌱 LifeGPT</div>
            <h1 style="color: #FFFFFF; font-size: 1.65rem; margin-top: 0.5rem; margin-bottom: 0.75rem;">LifeGPT System Administration</h1>
            <span class="admin-restricted-tag">🔒 RESTRICTED AREA - AUTHORIZED PERSONNEL ONLY</span>
        </div>

        <!-- Body Form Section -->
        <div class="admin-card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <?php echo CSRF::getInput(); ?>

                <div class="form-group">
                    <label for="email" class="form-label" style="font-weight: 600; color: #374151;">Admin Username / Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="admin@lifegpt.org" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group" style="position: relative;">
                    <label for="password" class="form-label" style="font-weight: 600; color: #374151;">Admin Password</label>
                    <div style="position: relative;">
                        <input type="password" id="adminPassword" name="password" class="form-control" placeholder="Enter your admin password" required style="padding-right: 45px;">
                        <button type="button" onclick="toggleAdminPasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #6B7280;">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem 1.5rem; font-size: 1.05rem; margin-top: 0.5rem;">
                    🔓 Login to Admin Dashboard
                </button>
            </form>

            <!-- Pink Restricted Warning Box (Image 3) -->
            <div class="admin-pink-alert">
                🛡️ <strong>This is a restricted area.</strong> Unauthorized access attempts are logged and monitored.
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminPasswordVisibility() {
    const input = document.getElementById('adminPassword');
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

