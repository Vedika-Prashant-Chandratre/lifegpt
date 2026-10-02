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
    header("Location: " . APP_URL . "/account/choice.php");
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
                // Non-fatal, log user in directly
                Auth::login($email, $password);
                header("Location: " . APP_URL . "/account/profile.php");
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

<div style="max-width: 520px; margin: 2rem auto;">
    <div class="card" style="border-top: 5px solid var(--color-primary); padding: 2.25rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; background: var(--color-mint-bg); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 0.75rem;">📝</div>
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Join LifeGPT</h1>
            <p class="text-sm">Create your free account to preserve and organize your life story archive.</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="flex-direction: column; align-items: stretch;">
                <p><?php echo htmlspecialchars($success); ?></p>
                <div style="margin-top: 1rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <p style="font-size: 0.85rem; font-weight: bold; margin-bottom: 0.5rem; color: #166534;">Local Activation Link:</p>
                    <a href="<?php echo $mockVerificationLink; ?>" style="word-break: break-all; font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: #15803d;">
                        <?php echo $mockVerificationLink; ?>
                    </a>
                </div>
                <a href="<?php echo $mockVerificationLink; ?>" class="btn btn-primary" style="margin-top: 1rem; width: 100%;">Verify & Setup Profile →</a>
            </div>
        <?php else: ?>
            <form action="" method="POST" autocomplete="off">
                <?php echo CSRF::getInput(); ?>
                
                <div class="form-group">
                    <label for="display_name" class="form-label">Full Name or Nickname</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" placeholder="e.g. Grandma Helen or John Doe" required value="<?php echo isset($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div style="position: relative;">
    <input type="password" id="password" name="password" class="form-control"
           placeholder="Minimum 8 characters" required minlength="8"
           onkeyup="validatePasswordCriteria(this.value)" style="padding-right: 3rem;">
    <button type="button" onclick="togglePassword('password', this)"
            aria-label="Show password"
            style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                   border:none; background:transparent; cursor:pointer; padding:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
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
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div style="position: relative;">
    <input type="password" id="confirm_password" name="confirm_password"
           class="form-control" placeholder="Re-enter password" required
           style="padding-right: 3rem;">
    <button type="button" onclick="togglePassword('confirm_password', this)"
            aria-label="Show password"
            style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                   border:none; background:transparent; cursor:pointer; padding:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
</div>
                </div>
                
                <div class="form-check" style="margin: 1.25rem 0;">
                    <input type="checkbox" id="terms_agree" class="form-check-input" required>
                    <label for="terms_agree" class="form-check-label">
                        I agree to the <a href="<?php echo APP_URL; ?>/terms.php" target="_blank">Terms of Service</a> and have read the <a href="<?php echo APP_URL; ?>/privacy.php" target="_blank">Privacy Policy</a>.
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.75rem;">Create Account</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border); font-size: 0.95rem;">
                Already have an account? <a href="<?php echo APP_URL; ?>/account/login.php">Sign In</a>
            </div>
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
