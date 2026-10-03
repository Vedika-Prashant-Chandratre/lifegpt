<?php
/**
 * LifeGPT - Interview Success Page
 * Shown after a user successfully submits their story summary.
 */
$pageTitle = "Thank You - LifeGPT";
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container" style="max-width: 700px; padding: 4rem 1rem; text-align: center;">
    <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 3rem 2rem; box-shadow: var(--shadow-md); border-top: 6px solid var(--color-primary);">
        <div style="font-size: 4rem; margin-bottom: 1rem;">&#127881;</div>
        
        <h1 style="font-size: 2.25rem; color: var(--color-primary); margin-bottom: 1rem;">
            Story Successfully Archived
        </h1>
        
        <p style="font-size: 1.1rem; color: var(--color-text-main); margin-bottom: 2rem; line-height: 1.6;">
            Thank you for sharing your experience! Your story summary has been safely stored. 
            By contributing to the LifeGPT archive, you are helping guide future generations seeking wisdom and advice.
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo APP_URL; ?>/dashboard/" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    Go to Dashboard
                </a>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/account/register.php" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    Create an Account to Save Stories
                </a>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                Return to Homepage
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
