<?php
/**
 * Logout Handler
 * VMS - Pipe Manufacturing Company
 */

require_once 'config/config.php';

// Log out the user
logoutUser();

// Redirect to login page with message
$_SESSION['info'] = 'You have been logged out successfully.';
header('Location: ' . APP_URL . '/login.php');
exit();
