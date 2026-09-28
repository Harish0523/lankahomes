<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Smart Session & Table detection
$current_logged_id = 0;
$table_name = '';

if (isset($_SESSION['agent_id']) && intval($_SESSION['agent_id']) > 0) {
    $current_logged_id = intval($_SESSION['agent_id']);
    $table_name = 'agents';
} elseif (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
    $current_logged_id = intval($_SESSION['user_id']);
    $table_name = 'users';
}

// Redirect to login page if unauthorized
if ($current_logged_id == 0) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// --- DYNAMIC DATABASE COLUMN DETECTION & AUTO-FIX ---
$columns = [];
$col_res = $conn->query("SHOW COLUMNS FROM $table_name");
if ($col_res) {
    while ($col_row = $col_res->fetch_assoc()) {
        $columns[] = strtolower($col_row['Field']);
    }
}

// Find matching Name Column
$name_column = '';
if (in_array('fullname', $columns)) { $name_column = 'fullname'; }
elseif (in_array('full_name', $columns)) { $name_column = 'full_name'; }
elseif (in_array('name', $columns)) { $name_column = 'name'; }
elseif (in_array('username', $columns)) { $name_column = 'username'; }

// Find matching Image Column
$image_column = '';
$possible_image_cols = ['profile_pic', 'image_url', 'image', 'avatar', 'photo', 'pic', 'profile_image', 'agent_image'];
foreach ($possible_image_cols as $possible_col) {
    if (in_array($possible_col, $columns)) {
        $image_column = $possible_col;
        break;
    }
}

// If absolutely NO image column exists, automatically create 'profile_pic' column!
if (empty($image_column)) {
    $alter_query = "ALTER TABLE $table_name ADD profile_pic VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alter_query)) {
        $image_column = 'profile_pic';
        $columns[] = 'profile_pic'; // Update local list
    }
}
// ----------------------------------------------------

$u_query = $conn->query("SELECT * FROM $table_name WHERE id = $current_logged_id");
if ($u_query && $u_query->num_rows > 0) {
    $u_data = $u_query->fetch_assoc();
    $name = (!empty($name_column) && isset($u_data[$name_column])) ? $u_data[$name_column] : 'User';
    $email = (in_array('email', $columns) && isset($u_data['email'])) ? $u_data['email'] : '';
    $image = (!empty($image_column) && isset($u_data[$image_column])) ? $u_data[$image_column] : '';
} else {
    echo "<script>alert('Account details not found!'); window.location.href='login.php';</script>";
    exit;
}

// Fetch listings dynamically
$properties = [];
$table_check = $conn->query("SHOW TABLES LIKE 'properties'");
$prop_table = ($table_check && $table_check->num_rows > 0) ? 'properties' : '';
if (empty($prop_table)) {
    $table_check2 = $conn->query("SHOW TABLES LIKE 'listings'");
    $prop_table = ($table_check2 && $table_check2->num_rows > 0) ? 'listings' : '';
}

if (!empty($prop_table)) {
    $prop_cols = [];
    $p_col_res = $conn->query("SHOW COLUMNS FROM $prop_table");
    if ($p_col_res) {
        while ($p_row = $p_col_res->fetch_assoc()) {
            $prop_cols[] = strtolower($p_row['Field']);
        }
    }
    
    $fk_col = '';
    if ($table_name == 'agents' && in_array('agent_id', $prop_cols)) {
        $fk_col = 'agent_id';
    } elseif (in_array('user_id', $prop_cols)) {
        $fk_col = 'user_id';
    } elseif (in_array('agent_id', $prop_cols)) {
        $fk_col = 'agent_id';
    } elseif (in_array('user', $prop_cols)) {
        $fk_col = 'user';
    }

    if (!empty($fk_col)) {
        $prop_query = $conn->query("SELECT * FROM $prop_table WHERE $fk_col = $current_logged_id ORDER BY id DESC");
        if ($prop_query) {
            while ($row = $prop_query->fetch_assoc()) {
                $properties[] = $row;
            }
        }
    }
}
$listings_count = count($properties);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .dashboard-wrapper { font-family: 'Segoe UI', Tahoma, system-ui, sans-serif; background-color: #f8fafc; min-height: 100vh; padding: 40px 10%; }
    
    /* Profile Header Card */
    .profile-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; display: flex; align-items: center; gap: 30px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); margin-bottom: 30px; }
    
    /* Profile Image Circle */
    .profile-avatar-container { width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid #007185; display: flex; align-items: center; justify-content: center; background: #f1f5f9; box-shadow: 0 4px 10px rgba(0, 113, 133, 0.12); flex-shrink: 0; }
    .profile-avatar-container img { width: 100%; height: 100%; object-fit: cover; }
    .profile-avatar-container i { font-size: 64px; color: #94a3b8; }
    
    .profile-info { display: flex; flex-direction: column; gap: 6px; }
    .profile-info h1 { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0; }
    .profile-info .email { font-size: 14.5px; color: #64748b; display: flex; align-items: center; gap: 8px; margin: 4px 0 12px 0; }
    
    .btn-edit { align-self: flex-start; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px 18px; border-radius: 20px; font-weight: 600; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-edit:hover { background: #e2e8f0; color: #0f172a; border-color: #94a3b8; }

    /* Listings Counter */
    .listings-counter-badge { display: inline-flex; align-items: center; gap: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 20px; font-size: 15px; font-weight: 600; color: #334155; text-decoration: none; margin-bottom: 30px; transition: all 0.2s; }
    .listings-counter-badge:hover { border-color: #007185; background-color: #f0fdfa; }
    .listings-counter-badge span { color: #007185; text-decoration: underline; }

    /* Property Grid */
    .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
    .property-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; position: relative; justify-content: space-between; }
    
    .badge-type { position: absolute; top: 15px; left: 15px; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: white; letter-spacing: 0.5px; z-index: 5; }
    .badge-sale { background-color: #ef4444; }
    .badge-rent { background-color: #f97316; } /* Orange color */

    .property-img-container { height: 200px; background: #f1f5f9; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: #94a3b8; position: relative; width: 100%; }
    .property-img-container img { width: 100%; height: 100%; object-fit: cover; }
    .property-img-container i { font-size: 48px; }
    .property-img-container span { font-size: 13px; font-weight: 500; }

    /* Actions buttons overlay */
    .card-actions { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; z-index: 10; }
    .action-btn { width: 34px; height: 34px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.12); transition: all 0.2s ease; text-decoration: none; font-size: 14px; outline: none; }
    .action-btn:hover { transform: scale(1.1); }
    .btn-dashboard-edit { color: #007185; } .btn-dashboard-edit:hover { background: #e0f2f1; }
    .btn-dashboard-del { color: #64748b; } .btn-dashboard-del:hover { color: #dc2626; background: #fee2e2; }

    .property-details { padding: 20px; display: flex; flex-direction: column; gap: 8px; flex-grow: 1; }
    .property-title { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    /* Green color for price */
    .property-price { font-size: 16px; font-weight: 700; color: #2a9d8f; }
    
    /* Footer & View Button */
    .property-footer { border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
    .property-location { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 6px; }
    .view-details-btn { color: #007185; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
</style>

<div class="dashboard-wrapper">

    <div class="profile-card">
        
        <div class="profile-avatar-container">
            <?php if (!empty($image)): ?>
                <img id="dashboard-avatar" src="<?php echo htmlspecialchars($image) . '?v=' . time(); ?>" alt="Profile Picture" onerror="this.style.display='none'; document.getElementById('dashboard-fallback-icon').style.display='inline-block';">
                <i id="dashboard-fallback-icon" class="fas fa-user-circle" style="display:none;"></i>
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <h1>Welcome Back, <?php echo htmlspecialchars($name); ?>!</h1>
            <div class="email">
                <i class="far fa-envelope"></i> <?php echo htmlspecialchars($email); ?>
            </div>
            <a href="edit_profile.php" class="btn-edit">
                <i class="fas fa-user-gear"></i> Edit Profile
            </a>
        </div>
    </div>

    <a href="#" class="listings-counter-badge">
        <i class="fas fa-building" style="color:#007185;"></i> 
        Your Total Listings: <span><?php echo $listings_count; ?> Properties</span>
    </a>

    <div class="property-grid">
        <?php if ($listings_count > 0): ?>
            <?php foreach ($properties as $prop): 
                $p_id = intval($prop['id']);
                $p_title = isset($prop['title']) ? $prop['title'] : (isset($prop['property_title']) ? $prop['property_title'] : 'Untitled Property');
                $p_price = isset($prop['price']) ? $prop['price'] : (isset($prop['property_price']) ? $prop['property_price'] : 'N/A');
                $p_location = isset($prop['location']) ? $prop['location'] : (isset($prop['city']) ? $prop['city'] : 'Unknown Location');
                
                // Accurately determine whether it is Rent or Sale
                $p_type_raw = isset($prop['type']) ? $prop['type'] : (isset($prop['purpose']) ? $prop['purpose'] : 'sale');
                $p_type = strtolower(trim($p_type_raw));
                
                // If there are multiple images, take only the first image
                $p_image_raw = isset($prop['image']) ? $prop['image'] : (isset($prop['image_url']) ? $prop['image_url'] : '');
                $images_arr = !empty($p_image_raw) ? explode(',', $p_image_raw) : [];
                $p_image = !empty($images_arr[0]) ? trim($images_arr[0]) : '';
            ?>
                <div class="property-card">
                    <?php if ($p_type === 'rent'): ?>
                        <span class="badge-type badge-rent">For Rent</span>
                    <?php else: ?>
                        <span class="badge-type badge-sale">For Sale</span>
                    <?php endif; ?>
                    
                    <div class="card-actions">
                        <a href="edit_property.php?id=<?php echo $p_id; ?>" class="action-btn btn-dashboard-edit" title="Edit Property">
                            <i class="fas fa-pen-to-square"></i>
                        </a>
                        <a href="delete.php?id=<?php echo $p_id; ?>" class="action-btn btn-dashboard-del" title="Delete Property" onclick="return confirm('Are you sure you want to delete this property?');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                    
                    <div class="property-img-container">
                        <?php if (!empty($p_image)): ?>
                            <img src="<?php echo htmlspecialchars($p_image); ?>" alt="Property Image">
                        <?php else: ?>
                            <i class="far fa-image"></i>
                            <span>No Image</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="property-details">
                        <div>
                            <h2 class="property-title"><?php echo htmlspecialchars($p_title); ?></h2>
                            <div class="property-price">
                                Rs. <?php echo is_numeric($p_price) ? number_format($p_price) : htmlspecialchars($p_price); ?>
                                <?php if($p_type === 'rent'): ?><span style="font-size: 12px; color:#64748b; font-weight: 500;"> /month</span><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="property-footer">
                            <div class="property-location">
                                <i class="fas fa-location-dot" style="color:#ef4444;"></i> <?php echo htmlspecialchars($p_location); ?>
                            </div>
                            <a href="property_details.php?id=<?php echo $p_id; ?>" class="view-details-btn">
                                View <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: white; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b;">
                <i class="fas fa-folder-open" style="font-size: 40px; margin-bottom: 10px; color: #cbd5e1;"></i>
                <p style="margin: 0; font-weight: 500;">No properties listed yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>