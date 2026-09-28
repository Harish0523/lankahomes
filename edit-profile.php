<?php
// 1. Start output buffering and session
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Include database connection
include 'db.php';

// 3. Security check: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// 4. 🎯 Retrieve the agent's current details from the database
$agent_sql = "SELECT * FROM agents WHERE user_id = '$user_id'";
$agent_result = $conn->query($agent_sql);

if ($agent_result && $agent_result->num_rows > 0) {
    $agent = $agent_result->fetch_assoc();
} else {
    // Default values if details are not found in the agents table
    $agent = [
        'name' => isset($_SESSION['name']) ? $_SESSION['name'] : '',
        'title' => '',
        'phone' => '',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        'location' => '',
        'address' => ''
    ];
}

// 5. When the form is submitted (Update Profile)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $title = $conn->real_escape_string(trim($_POST['title']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $location = $conn->real_escape_string(trim($_POST['location']));
    $address = $conn->real_escape_string(trim($_POST['address']));

    // Update agent details in the database
    $update_sql = "UPDATE agents SET 
                    name = '$name', 
                    title = '$title', 
                    phone = '$phone', 
                    location = '$location', 
                    address = '$address' 
                  WHERE user_id = '$user_id'";

    if ($conn->query($update_sql)) {
        $success_msg = "Profile updated successfully!";
        
        // Update session name as well
        $_SESSION['name'] = $name;
        
        // Refresh data to show updated details
        $agent_result = $conn->query($agent_sql);
        if ($agent_result) { $agent = $agent_result->fetch_assoc(); }
    } else {
        $error_msg = "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - LankaHomes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 5%; background: #fff; border-bottom: 1px solid #eee; }
        .logo { font-size: 24px; font-weight: bold; color: #111; text-decoration: none; }
        nav a { margin: 0 15px; text-decoration: none; color: #555; font-size: 14px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .add-property-btn { background: #111; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; }
        
        .profile-container { max-width: 600px; margin: 50px auto; padding: 30px; background: #fff; border: 1px solid #eaeaea; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .profile-container h2 { margin-bottom: 25px; color: #111; font-weight: 600; text-align: center; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; margin-bottom: 8px; color: #444; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; outline: none; }
        .form-group textarea { resize: vertical; height: 80px; }
        .form-group input[readonly] { background-color: #f5f5f5; color: #777; cursor: not-allowed; }
        
        .btn-container { display: flex; gap: 15px; margin-top: 25px; }
        .save-btn { flex: 2; background: #111; color: #fff; padding: 14px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .back-btn { flex: 1; display: block; text-align: center; background: #f4f4f4; color: #333; padding: 14px; border: 1px solid #ccc; border-radius: 6px; text-decoration: none; font-size: 16px; font-weight: 500; }
        
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        footer { background: #111; color: #fff; text-align: center; padding: 20px; font-size: 14px; margin-top: 60px; }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">LankaHomes</a>
        <nav>
            <a href="index.php">Home</a>
            <a href="my-properties.php">My Listings</a>
            <a href="agents.php">Agents</a> 
        </nav>
        <div class="nav-right">
             <span style="font-size: 14px; font-weight: 600;"><i class="far fa-user"></i> Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
             <a href="add-property.php" class="add-property-btn">Add Property</a>
        </div>
    </header>

    <div class="profile-container">
        <h2>Edit Agent Profile</h2>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="edit-profile.php" method="POST">
            <div class="form-group">
                <label>Email Address </label>
                <input type="email" value="<?php echo htmlspecialchars($agent['email']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Full Name </label>
                <input type="text" name="name" required placeholder="Enter full name" value="<?php echo htmlspecialchars($agent['name']); ?>">
            </div>

            <div class="form-group">
                <label>Title </label>
                <input type="text" name="title" placeholder="Enter your title" value="<?php echo htmlspecialchars($agent['title']); ?>">
            </div>

            <div class="form-group">
                <label>Phone Number </label>
                <input type="text" name="phone" required placeholder="Enter phone number" value="<?php echo htmlspecialchars($agent['phone']); ?>">
            </div>

            <div class="form-group">
                <label>Location </label>
                <input type="text" name="location" placeholder="Enter city/location" value="<?php echo htmlspecialchars($agent['location']); ?>">
            </div>

            <div class="form-group">
                <label>Address </label>
                <textarea name="address" placeholder="Enter your full address"><?php echo htmlspecialchars($agent['address']); ?></textarea>
            </div>

            <div class="btn-container">
                <a href="my-properties.php" class="back-btn">Cancel</a>
                <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2026 LankaHomes. All Rights Reserved.</p>
    </footer>

</body>
</html>
<?php 
ob_end_flush();
?>