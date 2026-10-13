<?php
/**
 * logout.php — Hancurkan sesi dan redirect ke login
 */
require_once __DIR__ . '/security.php';
session_unset();
session_destroy();
header('Location: login.php?logout=1');
exit;
?>
