<?php
/**
 * LifeGPT - Logout Handler
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::logout();
header("Location: " . APP_URL . "/");
exit;
