<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

if (isset($_SESSION['user_name'])) {
    log_audit('User Logged Out', 'User session ended for: ' . $_SESSION['user_name']);
}

session_destroy();
header("Location: login.php");
exit;
?>
