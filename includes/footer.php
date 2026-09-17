<?php
/**
 * LifeGPT - Common Footer Template
 */
require_once __DIR__ . '/config.php';
?>
    </main>

    <?php if (empty($hideFooter)): ?>
    <footer>
        <div class="footer-container">
            <div class="footer-section" style="max-width: 400px;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <span class="logo-icon-badge notranslate" translate="no" style="width: 28px; height: 28px; font-size: 0.9rem;">&#127793;</span>
                    <strong class="notranslate" translate="no" style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--color-primary);">LifeGPT</strong>
                </div>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 0.75rem;">
                    &ldquo;Your life has answers someone else needs.&rdquo;
                </p>
                <p style="color: var(--color-text-muted); font-size: 0.85rem;">
                    Powered by people. Organized by AI.<br>
                    <span class="notranslate" translate="no">A FiftyIsNifty research and experimentation initiative.</span>
                </p>
            </div>
            
            <div class="footer-section">
                <h4 style="font-family: var(--font-heading); color: var(--color-primary); font-size: 1.1rem; margin-bottom: 0.75rem;">Navigation</h4>
                <ul class="footer-links" style="list-style: none;">
                    <li style="margin-bottom: 0.4rem;"><a href="<?php echo APP_URL; ?>/interview/start.php">Share Your Story</a></li>
                    <li style="margin-bottom: 0.4rem;"><a href="<?php echo APP_URL; ?>/ask/">Ask LifeGPT</a></li>
                    <li style="margin-bottom: 0.4rem;"><a href="javascript:void(0)" onclick="openHowItWorksModal()">How LifeGPT Works</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4 style="font-family: var(--font-heading); color: var(--color-primary); font-size: 1.1rem; margin-bottom: 0.75rem;">Legal & Portals</h4>
                <ul class="footer-links" style="list-style: none;">
                    <li style="margin-bottom: 0.4rem;"><a href="<?php echo APP_URL; ?>/privacy.php">Privacy Policy</a></li>
                    <li style="margin-bottom: 0.4rem;"><a href="<?php echo APP_URL; ?>/terms.php">Terms of Service</a></li>
                    <li style="margin-bottom: 0.4rem;"><a href="<?php echo APP_URL; ?>/admin/login.php" style="color: var(--color-text-muted);">Admin System Login</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <span class="notranslate" translate="no">LifeGPT</span> &mdash; Collective Wisdom Archive. All rights reserved.</p>
            <p style="font-size: 0.85rem; color: var(--color-text-muted);"><span class="notranslate" translate="no">LifeGPT</span> is an experimental research platform. Respecting comfort & privacy.</p>
        </div>
    </footer>
    <?php endif; ?>
</body>
</html>
