<?php
/**
 * LifeGPT - User Profile Creation Form
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $displayName = trim($_POST['display_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    
    if (empty($displayName) || empty($email)) {
        $error = 'Display name and email are required.';
    } else {
        try {
            DB::query(
                "UPDATE lg_users SET display_name = :display_name, email = :email WHERE user_id = :id",
                ['display_name' => $displayName, 'email' => $email, 'id' => $user['user_id']]
            );
            unset($_SESSION['user_data']);
            header("Location: " . APP_URL . "/account/choice.php");
            exit;
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

$pageTitle = "Create Your LifeGPT Profile";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 720px; margin: 1.5rem auto 4rem auto;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary);">PROFILE CREATION</span>
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: var(--color-primary);">Build Your LifeGPT Profile</h1>
        <p class="text-sm" style="font-size: 1.05rem;">Help personalize how your stories are saved and credited.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
        <?php echo CSRF::getInput(); ?>

        <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--color-primary);">
            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. helen_smith">
                </div>
                <div class="form-group">
                    <label for="display_name" class="form-label">Display Name *</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo htmlspecialchars($user['display_name']); ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" id="age" name="age" class="form-control" placeholder="e.g. 64" min="18" max="120">
                </div>
                <div class="form-group">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" id="country" name="country" class="form-control" placeholder="e.g. United States">
                </div>
                <div class="form-group">
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="">Prefer not to say</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="non_binary">Non-binary</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="profession" class="form-label">Profession / Background</label>
                <input type="text" id="profession" name="profession" class="form-control" placeholder="e.g. Retired Elementary School Teacher">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="about_me" class="form-label">About Me Bio</label>
                <textarea id="about_me" name="about_me" class="form-control" placeholder="Share a few words about your life journey, hobbies, or perspective..."></textarea>
            </div>
        </div>

        <div style="text-align: center;">
            <button type="submit" class="btn btn-primary text-lg" style="padding: 1rem 3.5rem; font-size: 1.15rem;">
                ✨ Create My LifeGPT Profile
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
