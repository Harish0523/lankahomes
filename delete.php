<?php
// Start session
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

include 'db.php';

// 💡 1. Accurately retrieving user ID from session
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

// Check if user is logged in
if ($user_id === 0) {
    die("<div style='background:#ff4757; color:white; padding:20px; font-family:sans-serif; margin:50px auto; max-width:600px; border-radius:8px;'>
        ❌ <b>Error:</b> You are not logged in or the session has expired! Please log in again.
        </div>");
}

// 2. Retrieve property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id > 0) {
    
    // 🔍 3. Live verification to check property ownership
    $check_sql = "SELECT user_id FROM properties WHERE id = $property_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $db_user_id = intval($row['user_id']);
        
        // ⚠️ Security check: Verify if the logged-in user is the owner of the property
        if ($db_user_id !== $user_id) {
            die("<div style='background:#ffa502; color:black; padding:20px; font-family:sans-serif; margin:50px auto; max-width:600px; border-radius:8px;'>
                ⚠️ <b>Security Alert:</b> This property does not belong to you! <br><br>
                • Property owner ID: <b>$db_user_id</b> <br>
                • Your login ID: <b>$user_id</b> <br><br>
                (The ID of the property and your login ID must match).
                </div>");
        }
    } else {
        die("❌ <b>Error:</b> Property ID ($property_id) not found in the database!");
    }

    // ⭐ [Update]: Delete from favorites table first to prevent database crashes
    $conn->query("DELETE FROM favorites WHERE property_id = $property_id");

    // 🚀 4. If all checks pass, delete from the main table
    $sql = "DELETE FROM properties WHERE id = $property_id AND user_id = $user_id";
    
    if ($conn->query($sql) === TRUE) {
        // Redirect to main page after successful deletion
        header("Location: index.php?status=deleted"); 
        exit();
    } else {
        echo "❌ Error occurred while deleting: " . $conn->error;
    }
} else {
    die("❌ <b>Error:</b> Valid Property ID not provided!");
}

$conn->close();
?>