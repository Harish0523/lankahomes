<?php
// 1. Start output buffering to prevent header errors
ob_start();

// 2. Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Clear all session variables
session_unset();
$_SESSION = array(); 

// 4. Destroy the session
session_destroy();

// 5. Clear browser cache (to prevent access after logging out)
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 6. Redirect to the login page
header("Location: login.php");
exit();
?>