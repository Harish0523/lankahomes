<?php
// Start session
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
include 'db.php'; 
include 'header.php'; 

// Check if admin or agent is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch messages from database
$sql = "SELECT * FROM contact_messages ORDER BY id DESC";
$result = $conn->query($sql);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="wrapper" style="padding: 40px 0; background: #f9f9f9; min-height: 70vh;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); width: 100%; max-width: 1100px; margin: 0 auto;">
        
        <h2 style="margin-bottom: 20px; font-size: 24px; font-weight: 600; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <i class="fas fa-envelope" style="color: #007185; margin-right: 10px;"></i> Customer Messages
        </h2>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-family: sans-serif;">
                <thead>
                    <tr style="background-color: #007185; color: white;">
                        <th style="padding: 12px 15px; border: 1px solid #ddd;">Date & Time</th>
                        <th style="padding: 12px 15px; border: 1px solid #ddd;">Customer Name</th>
                        <th style="padding: 12px 15px; border: 1px solid #ddd;">Email Address</th>
                        <th style="padding: 12px 15px; border: 1px solid #ddd;">Subject</th>
                        <th style="padding: 12px 15px; border: 1px solid #ddd;">Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $date = date('d-m-Y h:i A', strtotime($row['created_at']));
                            ?>
                            <tr style="border-bottom: 1px solid #dddddd; transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
                                <td style="padding: 12px 15px; border: 1px solid #ddd; white-space: nowrap; font-size: 14px; color: #555;"><?php echo $date; ?></td>
                                <td style="padding: 12px 15px; border: 1px solid #ddd; font-weight: 600; color: #333;"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td style="padding: 12px 15px; border: 1px solid #ddd;"><a href="mailto:<?php echo $row['email']; ?>" style="color: #007185; text-decoration: none;"><?php echo htmlspecialchars($row['email']); ?></a></td>
                                <td style="padding: 12px 15px; border: 1px solid #ddd; font-weight: 500; color: #444;"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td style="padding: 12px 15px; border: 1px solid #ddd; color: #666; line-height: 1.5; font-size: 14px; min-width: 250px;"><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: #999; font-size: 16px;">
                                <i class="fas fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px;"></i>
                                No messages received yet.
                            </td>
                        </tr>
                        <?php
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<?php include 'footer.php'; ?>