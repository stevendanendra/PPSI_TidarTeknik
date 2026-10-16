<?php
/**
 * customer-logout.php — Logout Customer Portal
 * CV. Tidar Teknik
 */


require_once 'customer-auth.php';

unset($_SESSION[CUSTOMER_SESSION_KEY]);
unset($_SESSION[CUSTOMER_ID_KEY]);
unset($_SESSION[CUSTOMER_NAME_KEY]);
unset($_SESSION[CUSTOMER_EMAIL_KEY]);
unset($_SESSION['customer_auth_time']);

header('Location: landing.php?logout=1');
exit;
