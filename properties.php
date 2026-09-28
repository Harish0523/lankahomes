<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Login validation
$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

// Get search and filter parameters (GET Method)
$filter_type = isset($_GET['type']) ? trim(strtolower($conn->real_escape_string($_GET['type']))) : 'all';
$filter_purpose = isset($_GET['purpose']) ? trim(strtolower($conn->real_escape_string($_GET['purpose']))) : 'all';
$filter_location = isset($_GET['location']) ? trim(strtolower($conn->real_escape_string($_GET['location']))) : '';

// Prepare SQL Query
$sql = "SELECT p.*, u.full_name as owner_name 
        FROM properties p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE 1=1";

// 1. Property Type filter (House / Land)
if ($filter_type !== 'all' && !empty($filter_type) && $filter_type !== 'property type') {
    $sql .= " AND LOWER(p.property_type) = '$filter_type'";
}

// 2. Purpose filter (Buy -> sale / Rent -> rent)
if ($filter_purpose !== 'all' && !empty($filter_purpose) && $filter_purpose !== 'buy or rent') {
    // If 'buy' comes from the homepage, search for 'sale' in the database
    if ($filter_purpose === 'buy') {
        $filter_purpose = 'sale';
    }
    $sql .= " AND LOWER(p.purpose) = '$filter_purpose'";
}

// 3. Location filter (Mannar, etc.)
if (!empty($filter_location) && $filter_location !== 'location' && $filter_location !== 'all') {
    $sql .= " AND LOWER(p.location) LIKE '%$filter_location%'";
}

$sql .= " ORDER BY p.id DESC";
$result = $conn->query($sql);

// Get user's existing favorites list
$user_favorites = [];
if ($current_logged_id > 0) {
    $fav_res = $conn->query("SELECT property_id FROM favorites WHERE user_id = $current_logged_id");
    while ($f_row = $fav_res->fetch_assoc()) {
        $user_favorites[] = $f_row['property_id'];
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .properties-page-wrapper { font-family: 'Segoe UI', Tahoma, system-ui, sans-serif; background-color: #f8fafc; min-height: 100vh; padding: 40px 5%; }
    .properties-container { max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
    .properties-container * { box-sizing: border-box; }

    /* Page Header */
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
    .page-title { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0; }
    
    /* Filter Tabs */
    .filter-tabs { display: flex; gap: 10px; }
    .filter-btn { background: white; border: 1px solid #e2e8f0; padding: 8px 18px; border-radius: 30px; font-weight: 600; color: #64748b; text-decoration: none; font-size: 14px; transition: all 0.3s ease; }
    .filter-btn:hover, .filter-btn.active { background: #007185; color: white; border-color: #007185; box-shadow: 0 4px 10px rgba(0, 113, 133, 0.2); }

    /* Properties Grid */
    .properties-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-top: 20px; }

    /* Card Design */
    .property-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; position: relative; }
    .property-card:hover { transform: translateY(-6px); border-color: #007185; box-shadow: 0 12px 24px rgba(0, 113, 133, 0.08); }

    /* Card Image Section */
    .card-image-box { background: #e2e8f0; height: 210px; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .card-image-box img { width: 100%; height: 100%; object-fit: cover; }
    
    .no-image-placeholder { display: flex; flex-direction: column; align-items: center; color: #94a3b8; font-size: 14px; gap: 8px; }
    .no-image-placeholder i { font-size: 44px; }

    /* FOR SALE / FOR RENT Badge */
    .purpose-badge { position: absolute; top: 15px; left: 15px; color: white; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; z-index: 5; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .purpose-badge.sale { background: #f05a5a; }
    .purpose-badge.rent { background: #f4a261; }

    /* Card Action Buttons */
    .card-actions-top { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; z-index: 5; }
    .action-circle-btn { width: 34px; height: 34px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.06); transition: all 0.2s ease; outline: none; }
    .action-circle-btn:hover { transform: scale(1.1); }
    .action-circle-btn.fav-btn { color: #64748b; }
    .action-circle-btn.fav-btn.active { color: #ef4444; }
    .action-circle-btn.delete-btn { color: #2563eb; }
    .action-circle-btn.delete-btn:hover { color: #dc2626; }

    /* Card Content Section */
    .card-details-box { padding: 20px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
    .card-title-text { font-size: 16.5px; font-weight: 700; color: #0f172a; margin: 0; text-transform: capitalize; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    
    /* Price Design */
    .card-price-text { font-size: 17px; font-weight: 700; color: #007185; margin: 0; }
    .card-price-text span { font-size: 13px; font-weight: 500; color: #64748b; }

    /* Card Footer Section */
    .card-footer-box { display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid #f1f5f9; padding-top: 14px; }
    .card-location-text { font-size: 12.5px; color: #64748b; display: flex; align-items: center; gap: 5px; }
    .card-location-text i { color: #ef4444; }
    
    .card-view-link { font-size: 13.5px; font-weight: 700; color: #007185; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: gap 0.2s; }
    .card-view-link:hover { gap: 7px; }

    /* Empty State Message */
    .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; grid-column: 1 / -1; }
    .empty-state i { font-size: 48px; color: #94a3b8; margin-bottom: 15px; }
    .empty-state h3 { font-size: 18px; color: #1e293b; margin: 0 0 8px 0; }
    .empty-state p { color: #64748b; margin: 0; font-size: 14px; }

    @media (max-width: 768px) { .page-header { flex-direction: column; align-items: flex-start; gap: 15px; } .filter-tabs { width: 100%; overflow-x: auto; padding-bottom: 5px; } }
</style>

<div class="properties-page-wrapper">
    <div class="properties-container">
        
        <!-- Title and Filter Section -->
        <div class="page-header">
            <h1 class="page-title">
                <?php 
                if (!empty($filter_location) && $filter_location !== 'location') {
                    echo "Properties in " . ucfirst($filter_location);
                } else {
                    echo "Featured Properties in Mannar";
                }
                ?>
            </h1>
            
            <div class="filter-tabs">
                <a href="properties.php?type=all" class="filter-btn <?php echo $filter_type == 'all' ? 'active' : ''; ?>">All</a>
                <a href="properties.php?type=house" class="filter-btn <?php echo $filter_type == 'house' ? 'active' : ''; ?>">Houses</a>
                <a href="properties.php?type=land" class="filter-btn <?php echo $filter_type == 'land' ? 'active' : ''; ?>">Lands</a>
            </div>
        </div>

        <!-- Properties Grid -->
        <div class="properties-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $p_id = $row['id'];
                    $p_title = htmlspecialchars($row['title']);
                    $p_price = number_format($row['price']);
                    $p_location = htmlspecialchars($row['location']);
                    $p_purpose = strtolower($row['purpose']);
                    $p_type = strtolower($row['property_type']);
                    
                    $images = !empty($row['image_url']) ? explode(',', $row['image_url']) : [];
                    $first_image = !empty($images[0]) ? htmlspecialchars($images[0]) : '';
                    
                    $is_owner = ($current_logged_id > 0 && intval($row['user_id']) === $current_logged_id);
                    $is_fav = in_array($p_id, $user_favorites);
                ?>
                    <div class="property-card">
                        <div class="card-image-box">
                            <?php if ($p_purpose === 'rent'): ?>
                                <span class="purpose-badge rent">FOR RENT</span>
                            <?php else: ?>
                                <span class="purpose-badge sale">FOR SALE</span>
                            <?php endif; ?>

                            <div class="card-actions-top">
                                <button class="action-circle-btn fav-btn <?php echo $is_fav ? 'active' : ''; ?>" data-id="<?php echo $p_id; ?>" onclick="toggleFavorite(this, event)">
                                    <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                                
                                <?php if ($is_owner): ?>
                                    <button class="action-circle-btn delete-btn" onclick="deleteProperty(<?php echo $p_id; ?>, event)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($first_image)): ?>
                                <img src="<?php echo $first_image; ?>" alt="<?php echo $p_title; ?>">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="far fa-image"></i>
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-details-box">
                            <h3 class="card-title-text"><?php echo $p_title; ?></h3>
                            <p class="card-price-text">
                                Rs. <?php echo $p_price; ?>
                                <?php if ($p_purpose === 'rent'): ?>
                                    <span>/month</span>
                                <?php endif; ?>
                            </p>
                            
                            <div class="card-footer-box">
                                <span class="card-location-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo $p_location; ?>
                                </span>
                                <a href="property_details.php?id=<?php echo $p_id; ?>" class="card-view-link">
                                    View <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Properties Found</h3>
                    <p>We couldn't find any properties matching your current criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleFavorite(btn, event) {
    event.preventDefault();
    const propertyId = btn.getAttribute('data-id');

    fetch('toggle_favorite.php?property_id=' + propertyId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            btn.classList.add('active');
            btn.querySelector('i').className = 'fas fa-heart';
        } else if (data.status === 'removed') {
            btn.classList.remove('active');
            btn.querySelector('i').className = 'far fa-heart';
        } else if (data.status === 'error') {
            alert('⚠️ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred!');
    });
}

function deleteProperty(propertyId, event) {
    event.preventDefault();
    if (confirm("⚠️ Are you sure you want to delete this listing?")) {
        window.location.href = "delete_property.php?id=" + propertyId;
    }
}
</script>

<?php include 'footer.php'; ?>