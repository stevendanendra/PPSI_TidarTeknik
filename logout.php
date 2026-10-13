<?php
/**
 * logout.php — Hancurkan sesi dan redirect ke login
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
header('Location: login.php?logout=1');
exit;
?>
