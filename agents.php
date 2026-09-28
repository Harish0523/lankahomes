<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// 1. Fetch agent details from database
$query = "SELECT * FROM agents ORDER BY id DESC";
$result = $conn->query($query);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    /* Page background and layout */
    .agents-page-wrapper {
        font-family: 'Segoe UI', Tahoma, system-ui, sans-serif;
        background-color: #f8fafc;
        min-height: 100vh;
        padding: 50px 8%;
    }

    .page-title-section {
        margin-bottom: 40px;
        text-align: left;
    }

    .page-title-section h2 {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: inline-block;
        position: relative;
    }

    .page-title-section h2::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 60px;
        height: 4px;
        background-color: #007185;
        border-radius: 2px;
    }

    /* Agent cards grid */
    .agents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 20px;
    }

    /* Agent card main box */
    .agent-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 30px 24px;
        text-align: center;
        position: relative;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .agent-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Circular image container */
    .agent-image-container {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 10px auto 20px auto;
        overflow: hidden;
        border: 4px solid #007185;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        box-shadow: 0 4px 10px rgba(0, 113, 133, 0.15);
        position: relative;
    }

    /* Circular image style */
    .agent-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Default icon style for no image */
    .default-avatar-icon {
        font-size: 80px;
        color: #cbd5e1;
    }

    /* Verified badge */
    .verified-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: #e6f4ea;
        color: #137333;
        font-size: 12px;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #ceead6;
    }

    /* Agent details */
    .agent-details h3 {
        font-size: 20px;
        color: #0f172a;
        margin: 0 0 12px 0;
        font-weight: 700;
    }

    .agent-info-list {
        margin-bottom: 25px;
    }

    .agent-info {
        font-size: 14px;
        color: #475569;
        margin: 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .agent-info i {
        color: #007185;
        width: 16px;
        text-align: center;
    }

    /* Call agent button */
    .btn-call-agent {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #007185;
        color: white;
        text-decoration: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14.5px;
        transition: background 0.2s;
        border: none;
        width: 100%;
        box-sizing: border-box;
    }

    .btn-call-agent:hover {
        background: #005f70;
    }
</style>

<div class="agents-page-wrapper">
    <div class="page-title-section">
        <h2>Our Verified Agents in Mannar</h2>
    </div>

    <div class="agents-grid">
        <?php 
        if ($result && $result->num_rows > 0): 
            while($row = $result->fetch_assoc()): 
        ?>
            <div class="agent-card">
                <div class="verified-badge">
                    <i class="fas fa-check-circle"></i> Verified
                </div>
                
                <div class="agent-image-container">
                    <?php 
                    // Check if the image from the database actually exists in the folder
                    if (!empty($row['image_url']) && file_exists($row['image_url'])): 
                    ?>
                        <img src="<?php echo htmlspecialchars($row['image_url']) . '?v=' . time(); ?>" alt="Agent Profile" class="agent-avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle default-avatar-icon"></i>
                    <?php endif; ?>
                </div>

                <div class="agent-content">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    
                    <div class="agent-info-list">
                        <p class="agent-info">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo !empty($row['location']) ? htmlspecialchars($row['location']) : 'Mannar'; ?>
                        </p>
                        <p class="agent-info">
                            <i class="fas fa-phone"></i> 
                            <?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A'; ?>
                        </p>
                        <p class="agent-info">
                            <i class="fas fa-envelope"></i> 
                            <?php echo htmlspecialchars($row['email']); ?>
                        </p>
                    </div>
                    
                    <a href="tel:<?php echo $row['phone']; ?>" class="btn-call-agent">
                        <i class="fas fa-phone-alt"></i> Call Agent
                    </a>
                </div>
            </div>
            <?php 
            endwhile; 
        else: 
        ?>
            <p style="text-align: center; grid-column: 1/-1; color: #64748b; font-size: 16px;">No verified agents found.</p>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'footer.php'; 
?>