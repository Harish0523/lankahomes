<?php 
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Login check
$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

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

// Get recent 4 properties
$property_query = "SELECT * FROM properties ORDER BY id DESC LIMIT 4";
$property_result = $conn->query($property_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jaffnahomes- Find Your Perfect Property</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f8fafc; color: #334155; }
        .main-container { max-width: 1250px; margin: 0 auto; padding: 40px 5%; }

        .hero-section {
            background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%);
            border-radius: 20px; padding: 50px; display: flex; align-items: center;
            justify-content: space-between; gap: 40px; box-shadow: 0 10px 30px rgba(15, 76, 92, 0.15);
            margin-bottom: 50px; color: white;
        }
        .hero-content { flex: 1; }
        .hero-content h1 { font-size: 36px; font-weight: 700; margin-bottom: 12px; line-height: 1.2; }
        .hero-content p { font-size: 16px; color: #e2e8f0; margin-bottom: 30px; opacity: 0.9; }
        
        .search-container {
            background: rgba(25, 25, 25, 0.2); padding: 15px; border-radius: 12px;
            display: inline-flex; gap: 10px; backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 600px;
        }
        .search-container select {
            flex: 1; padding: 12px; border-radius: 8px; border: none; 
            font-size: 14px; background: white; color: #333; font-weight: 500; outline: none;
        }
        .search-btn {
            background: #f4a261; color: white; border: none; padding: 12px 25px;
            border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex;
            align-items: center; gap: 8px; font-size: 14px; transition: background 0.2s;
        }
        .search-btn:hover { background: #e76f51; }

        .hero-image-box { flex: 1; max-width: 450px; display: flex; justify-content: center; }
        .hero-image-box img { width: 100%; max-height: 280px; object-fit: cover; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); border: 4px solid rgba(255,255,255,0.1); }

        .section-title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 25px; }
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .category-card {
            background: white; border-radius: 14px; padding: 25px; text-align: center;
            text-decoration: none; color: #334155; font-weight: 600; font-size: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #f1f5f9;
            display: flex; flex-direction: column; align-items: center; gap: 15px; transition: all 0.3s ease;
        }
        .category-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,77,90,0.08); border-color: #007185; }
        .cat-icon-wrap { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .cat-houses { background: #e0f2fe; color: #0284c7; }
        .cat-lands { background: #fef3c7; color: #d97706; }

        .featured-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .view-all-link { color: #007185; font-weight: 600; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; }
        .view-all-link:hover { color: #0f4c5c; text-decoration: underline; }
        
        .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .property-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: all 0.3s ease; position: relative; }
        .property-card:hover { transform: translateY(-4px); border-color: #007185; }
        .property-img-box { width: 100%; height: 180px; background: #cbd5e1; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        
        .purpose-badge { position: absolute; top: 12px; left: 12px; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; z-index: 5; }
        .property-info { padding: 20px; }
        .property-title { font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .property-price { color: #007185; font-weight: 700; font-size: 17px; margin-bottom: 10px; }
        .property-footer { border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        .property-location { font-size: 13px; color: #64748b; display: inline-flex; align-items: center; gap: 5px; }
        .view-details-btn { color: #007185; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; }

        .card-actions { position: absolute; top: 12px; right: 12px; display: flex; gap: 8px; z-index: 10; }
        .action-btn { width: 34px; height: 34px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.12); transition: all 0.2s ease; text-decoration: none; font-size: 14px; outline: none; }
        .action-btn:hover { transform: scale(1.1); }
        .btn-fav { color: #64748b; }
        .btn-fav.active { color: #ef4444; }
        .btn-del { color: #2563eb; }
        .btn-del:hover { color: #dc2626; background: #fee2e2; }

        @media (max-width: 768px) { .hero-section { flex-direction: column; padding: 30px 20px; text-align: center; } .search-container { flex-direction: column; } .hero-image-box { display: none; } }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="hero-section">
            <div class="hero-content">
                <h1>Find Your Perfect Property in Mannar</h1>
                <p>Buy, Rent & Sell properties in Mannar District</p>
                
                <form action="search.php" method="GET" class="search-container">
                    <select name="purpose"><option value="buy">Buy</option><option value="rent">Rent</option></select>
                    <select name="location"><option value="Mannar">Mannar</option><option value="Mannar Town">Mannar Town</option><option value="Pesalai">Pesalai</option><option value="Adampan">Adampan</option><option value="Murunkan">Murunkan</option></select>
                    <select name="type"><option value="">Property Type</option><option value="house">House</option><option value="land">Land</option></select>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
            <div class="hero-image-box">
                <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80" alt="LankaHomes">
            </div>
        </div>

        <h2 class="section-title">Browse by Category in Mannar</h2>
        <div class="category-grid">
            <a href="all-properties.php?type=house" class="category-card"><div class="cat-icon-wrap cat-houses"><i class="fas fa-home"></i></div><span>Houses</span></a>
            <a href="all-properties.php?type=land" class="category-card"><div class="cat-icon-wrap cat-lands"><i class="fas fa-map-marker-alt"></i></div><span>Lands</span></a>
        </div>

        <div class="featured-header">
            <h2 class="section-title" style="margin-bottom: 0;">Featured Properties in Mannar</h2>
            <a href="all-properties.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="property-grid">
            <?php
            if ($property_result && $property_result->num_rows > 0) {
                while($row = $property_result->fetch_assoc()) {
                    $p_id = intval($row['id']);
                    $is_fav = in_array($p_id, $fav_properties);
                    
                    $images = !empty($row['image_url']) ? explode(',', $row['image_url']) : [];
                    $first_image = !empty($images[0]) ? htmlspecialchars($images[0]) : '';
                    ?>
                    <div class="property-card">
                        
                        <span class="purpose-badge" style="background: <?php echo (strtolower($row['purpose']) == 'rent') ? '#f4a261' : '#e76f51'; ?>;">
                            <?php echo (empty($row['purpose']) || strtolower($row['purpose']) == 'sale') ? 'For Sale' : 'For Rent'; ?>
                        </span>
                        
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
                                <img src="<?php echo $first_image; ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="color:#94a3b8; font-size:14px;"><i class="far fa-image" style="font-size:24px; margin-right:5px;"></i> No Image</div>
                            <?php endif; ?>
                        </div>

                        <div class="property-info">
                            <div class="property-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="property-price">Rs. <?php echo number_format($row['price']); ?><?php if($row['purpose'] == 'rent') echo '<span style="font-size:13px; color:#64748b; font-weight:500;"> /month</span>'; ?></div>
                            <div class="property-footer">
                                <div class="property-location"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?php echo htmlspecialchars($row['location'] ?? 'Mannar'); ?></div>
                                <a onclick="checkViewDetails(<?php echo $p_id; ?>, <?php echo $current_logged_id; ?>)" class="view-details-btn">View <i class="fas fa-chevron-right" style="font-size: 11px;"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color:#64748b;'>No Properties Available at the moment.</p>";
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