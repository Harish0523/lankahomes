<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Directly redirecting to the main agent dashboard page
header("Location: agent_dashboard.php");
exit();
?>