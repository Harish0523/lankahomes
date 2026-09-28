<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Login validation
$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

// Search parameters from URL
$search_purpose = isset($_GET['purpose']) ? trim(strtolower($conn->real_escape_string($_GET['purpose']))) : '';
$search_location = isset($_GET['location']) ? trim(strtolower($conn->real_escape_string($_GET['location']))) : '';
$search_type = isset($_GET['type']) ? trim(strtolower($conn->real_escape_string($_GET['type']))) : '';

// SQL Query
$sql = "SELECT * FROM properties WHERE 1=1";

// 1. Purpose
if (!empty($search_purpose) && $search_purpose !== 'all' && $search_purpose !== 'buy or rent') {
    if ($search_purpose === 'buy' || $search_purpose === 'sale') {
        $sql .= " AND (LOWER(purpose) LIKE '%sale%' OR LOWER(purpose) LIKE '%buy%' OR purpose = '' OR purpose IS NULL)";
    } elseif ($search_purpose === 'rent') {
        $sql .= " AND (LOWER(purpose) LIKE '%rent%' OR purpose = '' OR purpose IS NULL)";
    }
}

// 2. Location
if (!empty($search_location) && $search_location !== 'location') {
    $sql .= " AND LOWER(location) LIKE '%$search_location%'";
}

// 3. Property Type
if (!empty($search_type) && $search_type !== 'all' && $search_type !== 'property type') {
    $sql .= " AND LOWER(property_type) LIKE '%$search_type%'";
}

$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);

// Get user's favorites
$user_favorites = [];
if ($current_logged_id > 0) {
    $fav_res = $conn->query("SELECT property_id FROM favorites WHERE user_id = $current_logged_id");
    if ($fav_res) {
        while ($f_row = $fav_res->fetch_assoc()) {
            $user_favorites[] = $f_row['property_id'];
        }
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .search-page-wrapper { font-family: 'Segoe UI', Tahoma, system-ui, sans-serif; background-color: #f8fafc; min-height: 100vh; padding: 40px 5%; }
    .search-container { max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
    .search-container * { box-sizing: border-box; }

    .page-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 30px; }
    .page-title { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0; }
    .search-query-text { font-size: 14px; color: #64748b; margin-top: 5px; }

    .properties-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-top: 20px; }

    .property-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; position: relative; }
    .property-card:hover { transform: translateY(-6px); border-color: #007185; box-shadow: 0 12px 24px rgba(0, 113, 133, 0.08); }

    .card-image-box { background: #e2e8f0; height: 210px; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .card-image-box img { width: 100%; height: 100%; object-fit: cover; }
    .no-image-placeholder { display: flex; flex-direction: column; align-items: center; color: #94a3b8; font-size: 14px; gap: 8px; }
    .no-image-placeholder i { font-size: 44px; }

    .purpose-badge { position: absolute; top: 15px; left: 15px; color: white; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; z-index: 5; }
    .purpose-badge.sale { background: #f05a5a; }
    .purpose-badge.rent { background: #f4a261; }

    .card-actions-top { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; z-index: 5; }
    .action-circle-btn { width: 34px; height: 34px; border-radius: 50%; background: white; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; outline: none; }
    .action-circle-btn:hover { transform: scale(1.1); }
    .action-circle-btn.fav-btn { color: #64748b; }
    .action-circle-btn.fav-btn.active { color: #ef4444; }

    .card-details-box { padding: 20px; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
    .card-title-text { font-size: 16.5px; font-weight: 700; color: #0f172a; margin: 0; text-transform: capitalize; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .card-price-text { font-size: 17px; font-weight: 700; color: #007185; margin: 0; }
    .card-price-text span { font-size: 13px; color: #64748b; }

    .card-footer-box { display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid #f1f5f9; padding-top: 14px; }
    .card-location-text { font-size: 12.5px; color: #64748b; display: flex; align-items: center; gap: 5px; }
    .card-location-text i { color: #ef4444; }
    
    .card-view-link { font-size: 13.5px; font-weight: 700; color: #007185; text-decoration: none; display: flex; align-items: center; gap: 4px; transition: gap 0.2s; }
    .card-view-link:hover { gap: 7px; }

    .empty-state-box { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 60px 20px; text-align: center; width: 100%; max-width: 700px; margin: 30px auto; }
    .empty-state-box i { font-size: 50px; color: #94a3b8; margin-bottom: 20px; display: block; }
    .empty-state-box h3 { font-size: 20px; color: #1e293b; margin: 0 0 10px 0; font-weight: 700; }
    .empty-state-box p { color: #64748b; font-size: 14.5px; margin: 0 0 25px 0; }
    
    .back-home-btn { display: inline-flex; align-items: center; gap: 10px; background: #007185; color: white; text-decoration: none; padding: 12px 28px; border-radius: 30px; font-weight: 600; }
</style>

<div class="search-page-wrapper">
    <div class="search-container">
        
        <div class="page-header">
            <h1 class="page-title">Search Results</h1>
            <p class="search-query-text">
                Showing results for 
                <strong><?php echo !empty($search_purpose) ? ucfirst($search_purpose) : 'Any'; ?></strong> properties 
                <?php if (!empty($search_location)): ?>
                    in <strong>"<?php echo htmlspecialchars(ucwords($search_location)); ?>"</strong>
                <?php endif; ?>
                <?php if (!empty($search_type) && $search_type !== 'all'): ?>
                    of type <strong><?php echo ucfirst($search_type); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="properties-grid">
                <?php while ($row = $result->fetch_assoc()): 
                    $p_id = $row['id'];
                    $p_title = htmlspecialchars($row['title']);
                    $p_price = number_format($row['price']);
                    $p_location = htmlspecialchars($row['location']);
                    $p_purpose = !empty($row['purpose']) ? strtolower($row['purpose']) : 'sale';
                    
                    $images = !empty($row['image_url']) ? explode(',', $row['image_url']) : [];
                    $first_image = !empty($images[0]) ? htmlspecialchars($images[0]) : '';
                    
                    $is_fav = in_array($p_id, $user_favorites);
                ?>
                    <div class="property-card">
                        <div class="card-image-box">
                            <?php if (strpos($p_purpose, 'rent') !== false): ?>
                                <span class="purpose-badge rent">FOR RENT</span>
                            <?php else: ?>
                                <span class="purpose-badge sale">FOR SALE</span>
                            <?php endif; ?>

                            <div class="card-actions-top">
                                <button class="action-circle-btn fav-btn <?php echo $is_fav ? 'active' : ''; ?>" data-id="<?php echo $p_id; ?>" onclick="toggleFavorite(this, event)">
                                    <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
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
                            <p class="card-price-text">Rs. <?php echo $p_price; ?></p>
                            <div class="card-footer-box">
                                <span class="card-location-text">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $p_location; ?>
                                </span>
                                <a href="property_details.php?id=<?php echo $p_id; ?>" class="card-view-link">
                                    View <i class="fas fa-chevron-right" style="font-size: 11px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state-box">
                <i class="fas fa-search-minus"></i>
                <h3>No Properties Found</h3>
                <p>We couldn't find any properties matching your search criteria.<br>Please try selecting a different location or property type.</p>
                <a href="index.php" class="back-home-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
const loggedUserId = <?php echo $current_logged_id; ?>;

function toggleFavorite(btn, event) {
    event.preventDefault();
    
    if (loggedUserId === 0) {
        alert("⚠️ Please login first to add properties to your favorites!");
        window.location.href = "login.php";
        return;
    }

    const propertyId = btn.getAttribute('data-id');
    fetch('toggle_favorite.php?property_id=' + propertyId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') { 
            btn.classList.add('active'); 
            btn.querySelector('i').className = 'fas fa-heart'; 
        }
        else if (data.status === 'removed') { 
            btn.classList.remove('active'); 
            btn.querySelector('i').className = 'far fa-heart'; 
        }
    }).catch(error => console.error('Error:', error));
}
</script>

<?php include 'footer.php'; ?>