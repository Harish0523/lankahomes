<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Login check (Gets ID for either user or agent)
$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

if ($current_logged_id === 0) {
    echo "<script>alert('⚠️ Please log in first!'); window.location.href='login.php';</script>";
    exit();
}

// Getting property ID from URL
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT p.*, u.full_name as owner_name, u.phone as owner_phone, u.created_at as member_since 
          FROM properties p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE p.id = $property_id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'><h2>⚠️ Property details not found!</h2></div>");
}

$row = $result->fetch_assoc();

// Check if the logged-in user has already favorited this property
$is_favorited = false;
$fav_query = "SELECT * FROM favorites WHERE user_id = $current_logged_id AND property_id = $property_id";
$fav_result = $conn->query($fav_query);
if ($fav_result && $fav_result->num_rows > 0) {
    $is_favorited = true;
}

$seller_name = !empty($row['owner_name']) ? htmlspecialchars($row['owner_name']) : "LankaHomes Member";
$seller_phone = !empty($row['owner_phone']) ? htmlspecialchars($row['owner_phone']) : "0762084145";
$member_date = !empty($row['member_since']) ? date('F Y', strtotime($row['member_since'])) : "June 2026";
$prop_title = htmlspecialchars($row['title'] ?? 'No Title Specified');
$prop_price = number_format($row['price'] ?? 0);
$prop_location = htmlspecialchars($row['location'] ?? 'Mannar Town, Mannar');
$prop_purpose = htmlspecialchars($row['purpose'] ?? 'sale');
$prop_type = htmlspecialchars($row['property_type'] ?? 'Property');

$prop_size = !empty($row['sqft']) ? htmlspecialchars($row['sqft']) : 'N/A';
$prop_beds = !empty($row['bedrooms']) ? htmlspecialchars($row['bedrooms']) : 'N/A';
$prop_baths = !empty($row['bathrooms']) ? htmlspecialchars($row['bathrooms']) : 'N/A';
$prop_desc = !empty($row['description']) ? htmlspecialchars($row['description']) : 'No description available.';

// Convert comma separated image URLs to Array
$images_array = !empty($row['image_url']) ? explode(',', $row['image_url']) : [];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .details-page-wrapper { font-family: 'Segoe UI', Tahoma, system-ui, sans-serif; background-color: #f8fafc; color: #334155; padding: 40px 5%; }
    .details-container { max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
    .details-container * { box-sizing: border-box; }
    
    .property-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; gap: 20px; }
    .title-area { display: flex; flex-direction: column; gap: 6px; }
    .property-main-title { font-size: 30px; font-weight: 700; color: #0f172a; text-transform: capitalize; margin: 0; }
    .location-badge-top { display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 14.5px; }
    
    .btn-favorite-toggle { background: white; border: 1px solid #e2e8f0; padding: 10px 22px; border-radius: 50px; font-weight: 600; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); outline: none; }
    .btn-favorite-toggle:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

    .main-layout { display: grid; grid-template-columns: 7fr 4fr; gap: 30px; align-items: start; }
    .left-content-wrapper { display: flex; flex-direction: column; gap: 24px; }
    
    .image-gallery-box { background: #e2e8f0; border-radius: 16px; height: 460px; position: relative; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #94a3b8; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
    .image-gallery-box img { width: 100%; height: 100%; object-fit: cover; }
    .badge-for { position: absolute; top: 20px; left: 20px; color: white; padding: 6px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; text-transform: uppercase; z-index: 10; letter-spacing: 0.5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    
    .thumbnail-container { display: flex; gap: 12px; margin-top: 14px; overflow-x: auto; padding-bottom: 6px; }
    .thumbnail-card { width: 95px; height: 68px; border-radius: 10px; overflow: hidden; border: 2px solid transparent; cursor: pointer; transition: all 0.2s ease; background: #e2e8f0; flex-shrink: 0; }
    .thumbnail-card img { width: 100%; height: 100%; object-fit: cover; }
    .thumbnail-card:hover { transform: translateY(-2px); }
    .thumbnail-card.active-thumb { border-color: #007185; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0, 113, 133, 0.3); }

    .sidebar { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 20px; }
    
    .combined-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
    
    .price-block { background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%); padding: 25px; color: white; }
    .price-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #ccfbf1; margin-bottom: 6px; }
    .price-amount { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
    .negotiable-badge { background: rgba(255,255,255,0.18); padding: 5px 12px; border-radius: 30px; font-size: 12.5px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; backdrop-filter: blur(4px); }

    .seller-block { padding: 25px; text-align: center; background: white; }
    .avatar-circle { width: 60px; height: 60px; background: #f0fdfa; color: #007185; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 24px; border: 1px solid #ccfbf1; }
    .seller-name { font-size: 19px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .seller-meta { font-size: 12.5px; color: #64748b; margin-bottom: 22px; }
    
    .contact-actions { display: flex; flex-direction: column; gap: 12px; }
    .btn-action { text-decoration: none; padding: 14px; border-radius: 10px; font-weight: 600; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; border: none; transition: all 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); cursor: pointer; }
    
    .btn-call { background: #007185; color: white; }
    .btn-call:hover { background: #0f4c5c; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,113,133,0.2); }
    
    .btn-whatsapp { background: #25d366; color: white; }
    .btn-whatsapp:hover { background: #1ebd59; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(37,211,102,0.2); }

    .content-card { background: white; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
    .section-title { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 20px 0; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
    
    .overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
    .overview-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 15px; border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; }
    .overview-item:hover { background: #f0fdfa; border-color: #ccfbf1; transform: translateY(-2px); }
    
    .overview-icon { width: 40px; height: 40px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .overview-item:nth-child(2) .overview-icon { background: #fee2e2; color: #ef4444; }
    .overview-item:nth-child(3) .overview-icon { background: #fef3c7; color: #d97706; }
    .overview-item:nth-child(4) .overview-icon { background: #d1fae5; color: #059669; }
    .overview-item:nth-child(5) .overview-icon { background: #ede9fe; color: #7c3aed; }

    .overview-info { display: flex; flex-direction: column; }
    .overview-label { font-size: 11.5px; color: #64748b; font-weight: 500; margin-bottom: 1px; }
    .overview-value { font-size: 14.5px; font-weight: 600; color: #0f172a; }

    .desc-text { line-height: 1.6; color: #475569; margin: 0; font-size: 14.5px; white-space: pre-line; }

    @media (max-width: 992px) { .main-layout { grid-template-columns: 1fr; } .sidebar { position: static; } }
</style>

<div class="details-page-wrapper">
    <div class="details-container">
        
        <div class="property-header">
            <div class="title-area">
                <h1 class="property-main-title"><?php echo $prop_title; ?></h1>
                <div class="location-badge-top">
                    <i class="fas fa-map-marker-alt" style="color:#ef4444;"></i>
                    <span><?php echo $prop_location; ?></span>
                </div>
            </div>
            <button id="favoriteBtn" class="btn-favorite-toggle" data-id="<?php echo $property_id; ?>">
                <?php if ($is_favorited): ?>
                    <i class="fas fa-heart" style="color: #ef4444;"></i> Favorited
                <?php else: ?>
                    <i class="far fa-heart"></i> Add to Favorites
                <?php endif; ?>
            </button>
        </div>

        <div class="main-layout">
            <div class="left-content-wrapper">
                
                <div>
                    <div class="image-gallery-box">
                        <span class="badge-for" style="background: <?php echo (!empty($row['purpose']) && strtolower($row['purpose']) == 'rent') ? '#f4a261' : '#e76f51'; ?>;">
                            <?php echo (empty($row['purpose']) || strtolower($row['purpose']) == 'sale') ? 'For Sale' : 'For Rent'; ?>
                        </span>
                        <?php if(!empty($images_array)): ?>
                            <img id="mainDisplayImg" src="<?php echo htmlspecialchars($images_array[0]); ?>" alt="<?php echo $prop_title; ?>">
                        <?php else: ?>
                            <i class="far fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <span>No Premium Image Available</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(count($images_array) > 1): ?>
                        <div class="thumbnail-container">
                            <?php foreach($images_array as $index => $img_url): ?>
                                <div class="thumbnail-card <?php echo $index === 0 ? 'active-thumb' : ''; ?>" onclick="viewImage('<?php echo htmlspecialchars($img_url); ?>', this)">
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Thumbnail">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="content-card">
                    <h2 class="section-title"><i class="fas fa-layer-group" style="color: #007185;"></i> Key Property Overview</h2>
                    <div class="overview-grid">
                        <div class="overview-item">
                            <div class="overview-icon"><i class="fas fa-building"></i></div>
                            <div class="overview-info">
                                <span class="overview-label">Property Type</span>
                                <span class="overview-value"><?php echo $prop_type; ?></span>
                            </div>
                        </div>
                        <div class="overview-item">
                            <div class="overview-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="overview-info">
                                <span class="overview-label">Location</span>
                                <span class="overview-value"><?php echo $prop_location; ?></span>
                            </div>
                        </div>
                        <div class="overview-item">
                            <div class="overview-icon"><i class="fas fa-ruler-combined"></i></div>
                            <div class="overview-info">
                                <span class="overview-label">Land/House Size</span>
                                <span class="overview-value"><?php echo $prop_size; ?></span>
                            </div>
                        </div>
                        <div class="overview-item">
                            <div class="overview-icon"><i class="fas fa-bed"></i></div>
                            <div class="overview-info">
                                <span class="overview-label">Bedrooms</span>
                                <span class="overview-value"><?php echo $prop_beds; ?></span>
                            </div>
                        </div>
                        <div class="overview-item">
                            <div class="overview-icon"><i class="fas fa-bath"></i></div>
                            <div class="overview-info">
                                <span class="overview-label">Bathrooms</span>
                                <span class="overview-value"><?php echo $prop_baths; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h2 class="section-title"><i class="fas fa-align-left" style="color: #007185;"></i> Description</h2>
                    <p class="desc-text"><?php echo $prop_desc; ?></p>
                </div>
            </div>

            <div class="sidebar">
                <div class="combined-card">
                    <div class="price-block">
                        <div class="price-label">Total Expected Price</div>
                        <div class="price-amount">Rs. <?php echo $prop_price; ?></div>
                        <div class="negotiable-badge"><i class="fas fa-handshake"></i> Price Negotiable</div>
                    </div>

                    <div class="seller-block">
                        <div class="avatar-circle"><i class="fas fa-user"></i></div>
                        <div class="seller-name"><?php echo $seller_name; ?></div>
                        <div class="seller-meta">LankaHomes Member Since <?php echo $member_date; ?></div>
                        
                        <div class="contact-actions">
                            <?php
                            // WhatsApp-க்கு ஏற்றவாறு நம்பரை மாற்றுதல்
                            $clean_phone = preg_replace('/[^0-9]/', '', $seller_phone);
                            $wa_phone = (substr($clean_phone, 0, 1) == '0') ? '94' . substr($clean_phone, 1) : $clean_phone;
                            ?>
                            
                            <!-- Call Seller -->
                            <a href="tel:<?php echo $seller_phone; ?>" class="btn-action btn-call">
                                <i class="fas fa-phone-alt"></i> Call Seller: <?php echo $seller_phone; ?>
                            </a>

                            <!-- WhatsApp Chat -->
                            <a href="https://wa.me/<?php echo $wa_phone; ?>?text=Hi, I am interested in your property: <?php echo urlencode($prop_title); ?>" 
                               target="_blank" class="btn-action btn-whatsapp">
                                <i class="fab fa-whatsapp" style="font-size: 18px;"></i> WhatsApp Chat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewImage(imgUrl, thumbCard) {
    document.getElementById('mainDisplayImg').src = imgUrl;
    document.querySelectorAll('.thumbnail-card').forEach(card => {
        card.classList.remove('active-thumb');
    });
    thumbCard.classList.add('active-thumb');
}

document.getElementById('favoriteBtn').addEventListener('click', function() {
    const btn = this;
    const propertyId = btn.getAttribute('data-id');

    fetch('toggle_favorite.php?property_id=' + propertyId)
    .then(response => response.json())
    .then(data => {
        if (data.status === 'added') {
            btn.innerHTML = '<i class="fas fa-heart" style="color: #ef4444;"></i> Favorited';
        } else if (data.status === 'removed') {
            btn.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
        } else if (data.status === 'error') {
            alert('⚠️ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred!');
    });
});
</script>

<?php include 'footer.php'; ?>