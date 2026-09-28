<?php
session_start();
include 'db.php';
include 'header.php';

// Login check
$agent_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['agent_id']) ? $_SESSION['agent_id'] : null);

if (!$agent_id) {
    header("Location: login.php");
    exit();
}

// 1. Total properties added by the agent
$count_sql = "SELECT COUNT(*) AS total_listed FROM properties WHERE user_id = $agent_id";
$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_properties = $count_row['total_listed'];

// 2. Customer messages for this agent
$msg_sql = "SELECT m.*, p.title AS property_title 
            FROM messages m
            JOIN properties p ON m.property_id = p.id
            WHERE m.agent_id = $agent_id
            ORDER BY m.id DESC";
$msg_result = $conn->query($msg_sql);
?>

<div class="wrapper" style="padding: 40px 20px;">
    <h2>Agent Dashboard</h2>
    <hr style="border: 0.5px solid #ddd; margin-bottom: 30px;">

    <div class="dashboard-stats" style="display: flex; gap: 20px; margin-bottom: 40px;">
        <div style="background: #007185; color: #fff; padding: 25px; border-radius: 8px; flex: 1; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 16px; font-weight: normal;">Total Properties Added</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $total_properties; ?></p>
        </div>
        <div style="background: #25D366; color: #fff; padding: 25px; border-radius: 8px; flex: 1; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 16px; font-weight: normal;">Customer Messages</h3>
            <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold;"><?php echo $msg_result ? $msg_result->num_rows : 0; ?></p>
        </div>
    </div>

    <section>
        <h3 style="margin-bottom: 15px;"><i class="fas fa-envelope"></i> Received Customer Messages</h3>
        <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: #f4f4f4; border-bottom: 2px solid #ddd; text-align: left;">
                    <th style="padding: 15px;">Customer Name</th>
                    <th style="padding: 15px;">Email</th>
                    <th style="padding: 15px;">Property</th>
                    <th style="padding: 15px;">Message</th>
                    <th style="padding: 15px;">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($msg_result && $msg_result->num_rows > 0) {
                    while($msg = $msg_result->fetch_assoc()) {
                        ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px; font-weight: bold; color: #333;"><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td style="padding: 15px; color: #666; font-size: 14px;"><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td style="padding: 15px; color: #007185;"><a href="property-details.php?id=<?php echo $msg['property_id']; ?>" style="text-decoration: none; color: inherit; font-weight: 500;"><?php echo htmlspecialchars($msg['property_title']); ?></a></td>
                            <td style="padding: 15px; color: #555; background: #fafafa; font-style: italic;">"<?php echo htmlspecialchars($msg['message']); ?>"</td>
                            <td style="padding: 15px; color: #777; font-size: 13px;"><?php echo date('M d, Y - h:i A', strtotime($msg['created_at'])); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5' style='padding: 30px; text-align: center; color: #999;'>No new messages from customers yet.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </section>
</div>

<?php include 'footer.php'; ?>