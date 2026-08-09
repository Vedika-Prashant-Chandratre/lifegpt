<?php
/**
 * LifeGPT - Common Footer Template
 */
require_once __DIR__ . '/config.php';
?>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section" style="max-width: 400px;">
                <h4>LifeGPT</h4>
                <p style="color: #94a3b8; font-size: 1.05rem; margin-bottom: 1rem;">
                    “Your life has answers someone else needs.”
                </p>
                <p style="color: #64748b; font-size: 0.95rem;">
                    Powered by people. Organized by AI.<br>
                    A FiftyIsNifty research and experimentation initiative.
                </p>
            </div>
            
            <div class="footer-section">
                <h4>Participate</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo APP_URL; ?>/interview/choose-persona.php">Share Your Story</a></li>
                    <li><a href="<?php echo APP_URL; ?>/ask/">Ask LifeGPT</a></li>
                    <li><a href="<?php echo APP_URL; ?>/how-it-works.php">How It Works</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Legal & Info</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo APP_URL; ?>/privacy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo APP_URL; ?>/terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> FiftyIsNifty. All rights reserved.</p>
            <p style="font-size: 0.85rem; color: #64748b;">LifeGPT is an experimental research project. Content is for informational purposes only.</p>
        </div>
    </footer>
</body>
</html>
