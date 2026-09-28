<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';

// Specify the correct column name for the receiver in your 'messages' table
$database_column_name = 'agent_id'; 

$property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

// 1. Prevent agents from sending inquiries
if (isset($_SESSION['agent_id'])) {
    ?>
    <script>
        alert("⚠️ You are logged in as an Agent! Agents cannot send inquiries.");
        window.location.href = "property_details.php?id=<?php echo $property_id; ?>";
    </script>
    <?php
    exit();
}

// 2. Check if customer is logged in
$sender_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($sender_id === 0) {
    ?>
    <script>
        alert("⚠️ Please log in to your customer account to send a message!");
        window.location.href = "login.php";
    </script>
    <?php
    exit();
}

// 3. Save data to database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($receiver_id === 0 && $property_id > 0) {
        $stmt = $conn->prepare("SELECT user_id FROM properties WHERE id = ?");
        $stmt->bind_param("i", $property_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $p_row = $res->fetch_assoc();
            $receiver_id = intval($p_row['user_id']);
        }
        $stmt->close();
    }

    if ($property_id > 0 && $receiver_id > 0 && !empty($message)) {
        
        // Using Prepared Statements for security
        $sql = "INSERT INTO messages (property_id, sender_id, $database_column_name, message, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $property_id, $sender_id, $receiver_id, $message);

        if ($stmt->execute()) {
            ?>
            <script>
                alert("✅ Message sent successfully to the agent!");
                window.location.href = "property_details.php?id=<?php echo $property_id; ?>";
            </script>
            <?php
        } else {
            ?>
            <script>
                alert("❌ Error sending message: <?php echo $stmt->error; ?>");
                window.location.href = "property_details.php?id=<?php echo $property_id; ?>";
            </script>
            <?php
        }
        $stmt->close();
        
    } else {
        ?>
        <script>
            alert("⚠️ Please enter a message!");
            window.location.href = "property_details.php?id=<?php echo $property_id; ?>";
        </script>
        <?php
    }
} else {
    header("Location: index.php");
    exit();
}
?>