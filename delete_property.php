<?php
// Start session
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
include 'db.php';

// 1. Session and role verification
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id']);
$current_id = $_SESSION['user_id']; // Agent or User ID

// 2. Security: Verify ownership and delete the property
$stmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND (user_id = ? OR agent_id = ?)");
$stmt->bind_param("iii", $id, $current_id, $current_id);

if ($stmt->execute()) {
    // Redirect to dashboard on success
    header("Location: agent_dashboard.php?msg=deleted");
} else {
    // Redirect to dashboard with error message on failure
    header("Location: agent_dashboard.php?msg=error");
}
$stmt->close();
exit();
?>