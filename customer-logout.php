<?php
/**
 * customer-logout.php — Logout Customer Portal
 * CV. Tidar Teknik
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'customer-auth.php';

unset($_SESSION[CUSTOMER_SESSION_KEY]);
unset($_SESSION[CUSTOMER_ID_KEY]);
unset($_SESSION[CUSTOMER_NAME_KEY]);
unset($_SESSION[CUSTOMER_EMAIL_KEY]);
unset($_SESSION['customer_auth_time']);

header('Location: customer-login.php?logout=1');
exit;
