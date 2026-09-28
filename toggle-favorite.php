<?php
// Start session
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Set JSON output header for AJAX
header('Content-Type: application/json');

include 'db.php';

// 1. Get logged-in user ID
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

if ($user_id === 0) {
    echo json_encode(["status" => "error", "message" => "Please login first!"]);
    exit();
}

// 2. Safely get Property ID from POST or GET
$property_id = 0;
if (isset($_POST['property_id'])) {
    $property_id = intval($_POST['property_id']);
} elseif (isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);
}

// Validate Property ID
if ($property_id === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Property ID!"]);
    exit();
}

// 3. Check if it is already in the favorites list using Prepared Statement
$stmt = $conn->prepare("SELECT * FROM favorites WHERE property_id = ? AND user_id = ?");
$stmt->bind_param("ii", $property_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ❌ If exists: Remove from favorites
    $delete_stmt = $conn->prepare("DELETE FROM favorites WHERE property_id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $property_id, $user_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(["status" => "removed"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove from favorites"]);
    }
    $delete_stmt->close();
} else {
    // ✅ If not exists: Add to favorites
    $insert_stmt = $conn->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $user_id, $property_id);
    
    if ($insert_stmt->execute()) {
        echo json_encode(["status" => "added"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add to favorites"]);
    }
    $insert_stmt->close();
}

$stmt->close();
$conn->close();
?>