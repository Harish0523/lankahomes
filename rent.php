<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php';

$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

// Filter variables
$type_filter = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$location_filter = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';

// Get user's favorites
$fav_properties = [];
if ($current_logged_id > 0) {
    $fav_query = "SELECT property_id FROM favorites WHERE user_id = $current_logged_id";
    $fav_result = $conn->query($fav_query);
    if ($fav_result) {
        while ($fav_row = $fav_result->fetch_assoc()) {
            $fav_properties[] = intval($fav_row['property_id']);
        }
    }
}

// SQL Query to get properties for rent
$sql = "SELECT * FROM properties WHERE purpose = 'rent'";

if (!empty($type_filter)) { $sql .= " AND property_type = '$type_filter'"; }
if (!empty($location_filter)) { $sql .= " AND location LIKE '%$location_filter%'"; }
$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties for Rent in Mannar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f8fafc; color: #334155; }
        .listing-container { max-width: 1250px; margin: 0 auto; padding: 30px 5%; }
        .page-header { background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%); padding: 35px; border-radius: 16px; color: white; margin-bottom: 35px; box-shadow: 0 10px 25px rgba(15, 76, 92, 0.1); }
        .page-header h1 { font-size: 26px; font-weight: 700; margin-bottom: 5px; }
        .page-header p { font-size: 14px; color: #cbd5e1; margin-bottom: 20px; }
        .filter-bar { background: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 10px; display: flex; flex-wrap: wrap; gap: 12px; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.15); }
        .filter-group { flex: 1; min-width: 180px; }
        .filter-group select { width: 100%; padding: 12px 14px; border-radius: 6px; border: none; font-size: 13px; background: white; color: #333; font-weight: 500; outline: none; }
        .filter-btn { background: #f4a261; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
        .filter-btn:hover { background: #e76f51; }
        .results-meta { font-size: 14px; color: #64748b; font-weight: 500; margin-bottom: 20px; }
        .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .property-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; position: relative; display: flex; flex-direction: column; }
        .property-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0, 113, 133, 0.08); border-color: #007185; }
        .property-img-box { width: 100%; height: 190px; background: #cbd5e1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .property-img-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .property-card:hover .property-img-box img { transform: scale(1.05); }
        
        .purpose-badge { position: absolute; top: 12px; left: 12px; background: #f4a261; color: white; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; z-index: 5; }
        
        .property-info { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .property-title { font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 8px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .property-price { color: #2a9d8f; font-weight: 700; font-size: 18px; margin-bottom: 12px; }
        
        .property-footer { border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        .property-location { font-size: 13px; color: #64748b; display: inline-flex; align-items: center; gap: 5px; }
        .view-details-btn { color: #007185; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }
        .card-actions { position: absolute; top: 12px; right: 12px; display: flex; gap: 8px; z-index: 10; }
        .action-btn { width: 34px; height: 34px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.12); transition: all 0.2s ease; text-decoration: none; font-size: 14px; outline: none; }
        .action-btn:hover { transform: scale(1.1); }
        .btn-fav { color: #64748b; } 
        .btn-fav.active { color: #ef4444; }
        .btn-del { color: #64748b; } .btn-del:hover { color: #dc2626; background: #fee2e2; }
        .no-results { text-align: center; padding: 60px 20px; background: white; border-radius: 14px; border: 1px solid #e2e8f0; color: #64748b; width: 100%; }
        @media (max-width: 768px) { .filter-bar { flex-direction: column; } .filter-btn { width: 100%; justify-content: center; } }
    </style>
</head>
<body>
    <div class="listing-container">
        <div class="page-header">
            <h1>Properties For Rent in Mannar</h1>
            <p>Find comfortable houses and commercial lands for rent</p>
            <form action="" method="GET" class="filter-bar">
                <div class="filter-group">
                    <select name="type">
                        <option value="">All Property Types</option>
                        <option value="House" <?php if($type_filter == 'House') echo 'selected'; ?>>House</option>
                        <option value="Land" <?php if($type_filter == 'Land') echo 'selected'; ?>>Land</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="location">
                        <option value="">All Locations</option>
                        <option value="Mannar" <?php if($location_filter == 'Mannar') echo 'selected'; ?>>Mannar</option>
                        <option value="Mannar Town" <?php if($location_filter == 'Mannar Town') echo 'selected'; ?>>Mannar Town</option>
                        <option value="Pesalai" <?php if($location_filter == 'Pesalai') echo 'selected'; ?>>Pesalai</option>
                        <option value="Adampan" <?php if($location_filter == 'Adampan') echo 'selected'; ?>>Adampan</option>
                        <option value="Murunkan" <?php if($location_filter == 'Murunkan') echo 'selected'; ?>>Murunkan</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn"><i class="fas fa-sliders-h"></i> Filter Rentings</button>
            </form>
        </div>

        <div class="results-meta"><i class="fas fa-key" style="color:#007185;"></i> Found <?php echo ($result) ? $result->num_rows : 0; ?> properties for rent</div>

        <div class="property-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $p_id = intval($row['id']);
                    $is_fav = in_array($p_id, $fav_properties);
                    
                    $images = !empty($row['image_url']) ? explode(',', $row['image_url']) : [];
                    $first_image = !empty($images[0]) ? htmlspecialchars($images[0]) : '';
                    ?>
                    <div class="property-card">
                        <span class="purpose-badge">For Rent</span>
                        <div class="card-actions">
                            <button class="action-btn btn-fav <?php echo $is_fav ? 'active' : ''; ?>" onclick="toggleFavourite(this, <?php echo $p_id; ?>, <?php echo $current_logged_id; ?>)">
                                <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                            <?php if ($current_logged_id > 0 && $current_logged_id === intval($row['user_id'])): ?>
                                <a href="delete.php?id=<?php echo $p_id; ?>" class="action-btn btn-del" onclick="return confirm('Are you sure you want to delete this property?');"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </div>
                        <div class="property-img-box">
                            <?php if(!empty($first_image)): ?>
                                <img src="<?php echo $first_image; ?>" alt="Property Image">
                            <?php else: ?>
                                <div style="color:#94a3b8; font-size:14px;"><i class="far fa-image" style="font-size:24px; margin-right:5px;"></i> No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="property-info">
                            <div>
                                <div class="property-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                <div class="property-price">Rs. <?php echo number_format($row['price']); ?><span style="font-size: 13px; color:#64748b; font-weight: 500;"> /month</span></div>
                            </div>
                            <div class="property-footer">
                                <div class="property-location"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?php echo htmlspecialchars($row['location'] ?? 'Mannar'); ?></div>
                                <a onclick="checkViewDetails(<?php echo $p_id; ?>, <?php echo $current_logged_id; ?>)" class="view-details-btn">View <i class="fas fa-chevron-right" style="font-size: 11px;"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='no-results'><h3>No Properties Found for Rent!</h3></div>";
            }
            ?>
        </div>
    </div>
    
    <script>
    function toggleFavourite(btn, id, isLoggedIn) {
        if (isLoggedIn === 0) {
            alert("⚠️ Please login first to add favorites!");
            window.location.href = "login.php";
            return;
        }
        const icon = btn.querySelector('i');
        fetch('toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `property_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added') {
                icon.className = 'fas fa-heart';
                btn.classList.add('active');
            } else if (data.status === 'removed') {
                icon.className = 'far fa-heart';
                btn.classList.remove('active');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function checkViewDetails(propertyId, isLoggedIn) {
        if (isLoggedIn === 0) {
            alert("⚠️ Please login to view full details!");
            window.location.href = "login.php";
            return;
        }
        window.location.href = "property_details.php?id=" + propertyId;
    }
    </script>
</body>
</html>
<?php include 'footer.php'; ob_end_flush(); ?>