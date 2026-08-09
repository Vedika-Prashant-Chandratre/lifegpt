<?php
/**
 * LifeGPT - Member Login
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL . "/dashboard/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    CSRF::validateRequest();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $loginResult = Auth::login($email, $password);
    
    if ($loginResult['success']) {
        // Redirect to intended page or default dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/dashboard/');
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $redirect);
        exit;
    } else {
        $error = $loginResult['error'];
    }
}

$pageTitle = "Sign In";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <h1 style="font-size: 2rem; margin-bottom: 1.5rem; text-align: center;">Sign In to LifeGPT</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST" autocomplete="off">
            <?php echo CSRF::getInput(); ?>
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
                    <a href="<?php echo APP_URL; ?>/account/forgot-password.php" style="font-size: 0.95rem;">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Sign In</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem; font-size: 1.05rem;">
            Don't have an account? <a href="<?php echo APP_URL; ?>/account/register.php">Create Account</a>
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
