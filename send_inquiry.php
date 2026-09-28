<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first to send an inquiry!");
}

if (isset($_POST['submit_inquiry'])) {
    $property_id = intval($_POST['property_id']);
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['buyer_message']);
    $sender_id = $_SESSION['user_id'];

    // Using Prepared Statements for security
    $stmt = $conn->prepare("INSERT INTO inquiries (property_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $property_id, $sender_id, $receiver_id, $message);

    if ($stmt->execute()) {
        echo "Inquiry message sent successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
?>