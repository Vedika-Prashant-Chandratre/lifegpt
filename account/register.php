<?php
/**
 * LifeGPT - Member Registration
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';
$mockVerificationLink = '';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: " . APP_URL . "/dashboard/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    CSRF::validateRequest();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $displayName = $_POST['display_name'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $regResult = Auth::register($email, $password, $displayName);
        
        if ($regResult['success']) {
            $user = $regResult['user'];
            
            // Create email verification token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            try {
                DB::insert(
                    "INSERT INTO lg_email_verifications (user_id, token_hash, expires_at) 
                     VALUES (:user_id, :token_hash, :expires_at)",
                    [
                        'user_id' => $user['user_id'],
                        'token_hash' => $tokenHash,
                        'expires_at' => $expiry
                    ]
                );
                
                // Formulate mock verification link
                $mockVerificationLink = APP_URL . "/account/verify-email.php?token=" . $token;
                $success = "Registration successful! Since this is running in a local environment, we have simulated sending the verification email.";
                
            } catch (Exception $e) {
                error_log("Failed to create email verification: " . $e->getMessage());
                // Non-fatal, just log user in directly
                Auth::login($email, $password);
                header("Location: " . APP_URL . "/dashboard/");
                exit;
            }
        } else {
            $error = $regResult['error'];
        }
    }
}

$pageTitle = "Create Your Account";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 2rem auto;">
    <div class="card">
        <h1 style="font-size: 2rem; margin-bottom: 1.5rem; text-align: center;">Join LifeGPT</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="flex-direction: column; align-items: stretch;">
                <p><?php echo htmlspecialchars($success); ?></p>
                <div style="margin-top: 1rem; padding: 1rem; background: #ffffff; border-radius: 6px; border: 1px solid #bbf7d0;">
                    <p style="font-size: 0.9rem; font-weight: bold; margin-bottom: 0.5rem; color: #166534;">Local Development Activation Link:</p>
                    <a href="<?php echo $mockVerificationLink; ?>" style="word-break: break-all; font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: #15803d;">
                        <?php echo $mockVerificationLink; ?>
                    </a>
                </div>
                <p style="margin-top: 1rem; font-size: 0.95rem;">Click the link above to verify your email and access your dashboard.</p>
            </div>
        <?php else: ?>
            <form action="" method="POST" autocomplete="off">
                <?php echo CSRF::getInput(); ?>
                
                <div class="form-group">
                    <label for="display_name" class="form-label">Full Name or Nickname</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" placeholder="e.g. Grandma Helen or John Doe" required value="<?php echo isset($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''; ?>">
                    <p class="text-sm" style="margin-top: 0.25rem;">This name is used to greet you on the dashboard.</p>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
                
                <div class="form-check" style="margin: 1.5rem 0;">
                    <input type="checkbox" id="terms_agree" class="form-check-input" required>
                    <label for="terms_agree" class="form-check-label">
                        I agree to the <a href="<?php echo APP_URL; ?>/terms.php" target="_blank">Terms of Service</a> and have read the <a href="<?php echo APP_URL; ?>/privacy.php" target="_blank">Privacy Policy</a>.
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Account</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem; font-size: 1.05rem;">
                Already have an account? <a href="<?php echo APP_URL; ?>/account/login.php">Sign In</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
