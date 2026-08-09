<?php
/**
 * LifeGPT - Common Header Template
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php'; // We will create this file next

// Check if user is logged in
$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();

// Get active page name to mark nav link active
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | LifeGPT' : 'LifeGPT - Collective Wisdom Platform'; ?></title>
    <meta name="description" content="Share your life stories, lessons, and turning points with LifeGPT. Powered by people. Organized by AI. A FiftyIsNifty research initiative.">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/lifegpt.css">
    <?php echo isset($extraHead) ? $extraHead : ''; ?>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="<?php echo APP_URL; ?>/" class="logo-link" aria-label="LifeGPT Homepage">
                LifeGPT <span class="logo-sub">FiftyIsNifty</span>
            </a>
            
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false">
                ☰
            </button>
            
            <ul class="nav-links" id="navLinks">
                <li class="<?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>/">Home</a>
                </li>
                <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/interview/') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>/interview/choose-persona.php">Share Your Story</a>
                </li>
                <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/ask/') !== false) ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>/ask/">Ask LifeGPT</a>
                </li>
                <li class="<?php echo ($currentPage === 'how-it-works.php') ? 'active' : ''; ?>">
                    <a href="<?php echo APP_URL; ?>/how-it-works.php">How It Works</a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                    <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? 'active' : ''; ?>">
                        <a href="<?php echo APP_URL; ?>/dashboard/">My Interviews</a>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : ''; ?>">
                            <a href="<?php echo APP_URL; ?>/admin/" style="color: var(--color-warning);">Admin</a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="<?php echo APP_URL; ?>/account/profile.php" class="font-semibold"><?php echo htmlspecialchars($user['display_name']); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo APP_URL; ?>/account/logout.php" class="btn btn-secondary" style="min-height: 38px; padding: 0.25rem 1rem; font-size: 0.95rem;">Sign Out</a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-primary" style="min-height: 38px; padding: 0.25rem 1rem; font-size: 0.95rem; color: #ffffff;">Sign In</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    
    <script>
        // Responsive Menu Toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            const navLinks = document.getElementById('navLinks');
            const expanded = this.getAttribute('aria-expanded') === 'true' || false;
            
            navLinks.classList.toggle('show');
            this.setAttribute('aria-expanded', !expanded);
            this.innerHTML = expanded ? '☰' : '✕';
        });
    </script>
    <main class="main-content">
