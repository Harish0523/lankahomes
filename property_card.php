<?php

$is_fav = in_array(intval($row['id']), $fav_properties);
?>

<div class="property-card">
    <span class="purpose-badge" style="background: <?php echo (strtolower($row['purpose']) == 'rent') ? '#f4a261' : '#e76f51'; ?>;">
        <?php echo (empty($row['purpose']) || strtolower($row['purpose']) == 'sale') ? 'For Sale' : 'For Rent'; ?>
    </span>
	
      <div class="card-actions">
        <button class="action-btn btn-fav" onclick="toggleFavourite(this, <?php echo $row['id']; ?>, <?php echo $current_logged_id; ?>)" style="color: <?php echo $is_fav ? '#ef4444' : '#64748b'; ?>;">
            <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i>
        </button>
        
        <?php if ($current_logged_id > 0 && $current_logged_id === intval($row['user_id'])): ?>
            <a href="delete.php?id=<?php echo $row['id']; ?>" class="action-btn btn-del" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i></a>
        <?php endif; ?>
    </div>

    <div class="property-img-box">
        <?php if(!empty($row['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
        <?php else: ?>
            <div style="display:flex; justify-content:center; align-items:center; height:100%; color:#94a3b8; background:#e2e8f0;"><i class="far fa-image"></i> No Image</div>
        <?php endif; ?>
    </div>
    <div class="property-info">
        <div class="property-title"><?php echo htmlspecialchars($row['title']); ?></div>
        <div class="property-price">Rs. <?php echo number_format($row['price']); ?><?php if($row['purpose'] == 'rent') echo '<span style="font-size:13px; color:#64748b;"> /month</span>'; ?></div>
        <div class="property-footer">
            <div class="property-location"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?php echo htmlspecialchars($row['location'] ?? 'Mannar'); ?></div>
            <a onclick="checkViewDetails(<?php echo $row['id']; ?>, <?php echo $current_logged_id; ?>)" class="view-details-btn">View <i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
</div>
