<?php
// includes/session.php
// Start output buffering to prevent header issues
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>