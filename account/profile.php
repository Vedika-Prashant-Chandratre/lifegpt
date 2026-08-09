<?php
/**
 * LifeGPT - Member Profile & Settings
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    CSRF::validateRequest();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        
        if (empty($displayName) || empty($email)) {
            $error = 'Display name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is already taken by someone else
            $existing = DB::fetch(
                "SELECT user_id FROM lg_users WHERE email = :email AND user_id != :id",
                ['email' => $email, 'id' => $user['user_id']]
            );
            
            if ($existing) {
                $error = 'This email is already in use by another account.';
            } else {
                try {
                    DB::query(
                        "UPDATE lg_users SET display_name = :display_name, email = :email WHERE user_id = :id",
                        ['display_name' => $displayName, 'email' => $email, 'id' => $user['user_id']]
                    );
                    
                    // Refresh session user data
                    unset($_SESSION['user_data']);
                    $user = Auth::getCurrentUser();
                    $success = 'Profile updated successfully!';
                } catch (Exception $e) {
                    error_log("Failed to update profile: " . $e->getMessage());
                    $error = 'A system error occurred. Please try again.';
                }
            }
        }
    } 
    elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            $error = 'All password fields are required.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Get user's password hash from DB
            $dbUser = DB::fetch("SELECT password_hash FROM lg_users WHERE user_id = :id", ['id' => $user['user_id']]);
            
            if (!password_verify($currentPassword, $dbUser['password_hash'])) {
                $error = 'Your current password was incorrect.';
            } else {
                try {
                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    DB::query(
                        "UPDATE lg_users SET password_hash = :hash WHERE user_id = :id",
                        ['hash' => $newHash, 'id' => $user['user_id']]
                    );
                    $success = 'Password changed successfully!';
                } catch (Exception $e) {
                    error_log("Failed to update password: " . $e->getMessage());
                    $error = 'A system error occurred. Please try again.';
                }
            }
        }
    } 
    elseif ($action === 'delete_account') {
        $confirmText = trim($_POST['delete_confirm'] ?? '');
        
        if ($confirmText !== 'DELETE') {
            $error = 'Please type "DELETE" to confirm account removal.';
        } else {
            try {
                DB::beginTransaction();
                
                $id = $user['user_id'];
                
                // 1. Delete associated interviews and cascading data
                // In our schema, foreign keys are configured with ON DELETE CASCADE or SET NULL.
                // lg_interviews FK has user_id ON DELETE SET NULL, but the spec says:
                // "Deleting an interview removes it from future retrieval."
                // "Account and interview deletion workflow. Deleting an account deletes their interviews."
                // Let's delete all interviews belonging to the user explicitly to remove them from future retrieval:
                $userInterviews = DB::fetchAll("SELECT interview_id FROM lg_interviews WHERE user_id = :user_id", ['user_id' => $id]);
                foreach ($userInterviews as $interview) {
                    DB::query("DELETE FROM lg_interviews WHERE interview_id = :id", ['id' => $interview['interview_id']]);
                }
                
                // 2. Delete the user
                DB::query("DELETE FROM lg_users WHERE user_id = :id", ['id' => $id]);
                
                DB::commit();
                
                // Log out
                Auth::logout();
                
                // Redirect
                header("Location: " . APP_URL . "/?account_deleted=1");
                exit;
                
            } catch (Exception $e) {
                DB::rollBack();
                error_log("Failed to delete account: " . $e->getMessage());
                $error = 'A system error occurred. Account deletion failed.';
            }
        }
    }
}

$pageTitle = "My Profile & Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem;">Profile & Account Settings</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; flex-direction: column; gap: 2.5rem;">
        <!-- Edit Profile -->
        <div class="card">
            <h2>Edit Profile Details</h2>
            <form action="" method="POST">
                <?php echo CSRF::getInput(); ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="display_name" class="form-label">Display Name</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo htmlspecialchars($user['display_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Save Changes</button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <h2>Update Security Password</h2>
            <form action="" method="POST">
                <?php echo CSRF::getInput(); ?>
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 8 characters" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Change Password</button>
            </form>
        </div>
        
        <!-- Delete Account -->
        <div class="card" style="border-color: var(--color-danger); background-color: #fff1f2; color: #9f1239;">
            <h2 style="color: var(--color-danger);">Danger Zone: Delete Account</h2>
            <p style="margin-bottom: 1.5rem; color: #9f1239;">
                Deleting your account is permanent. All your information, completed interviews, themes, consent registries, and AI summaries will be permanently deleted from the database. <strong>This action cannot be undone.</strong>
            </p>
            
            <form action="" method="POST" onsubmit="return confirm('Are you absolutely sure you want to permanently delete your LifeGPT account? This will delete all your interviews.');">
                <?php echo CSRF::getInput(); ?>
                <input type="hidden" name="action" value="delete_account">
                
                <div class="form-group">
                    <label for="delete_confirm" class="form-label" style="color: var(--color-danger);">Type <strong>DELETE</strong> in the box below to authorize deletion:</label>
                    <input type="text" id="delete_confirm" name="delete_confirm" class="form-control" style="border-color: var(--color-danger);" placeholder="DELETE" required autocomplete="off">
                </div>
                
                <button type="submit" class="btn btn-danger">Permanently Delete My Account</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
