<?php
// Include database connection and header
include 'db.php'; 
include 'header.php'; 

// Fetch properties for sale (purpose = 'buy') in Mannar
$sql = "SELECT * FROM properties 
        WHERE purpose = 'buy' 
        AND location LIKE '%Mannar%' 
        ORDER BY id DESC";
        
$result = $conn->query($sql);
?>

<div class="wrapper">
    <section>
        <div class="section-header">
            <h2>Properties for Sale in Mannar</h2>
            <span style="font-size: 14px; color: #666;"><?php echo $result ? $result->num_rows : 0; ?> Properties Available</span>
        </div>
        
        <div class="property-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="property-card" onclick="location.href='property-details.php?id=<?php echo $row['id']; ?>'" style="cursor: pointer;">
                        <div class="property-img">
                            <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none" style="background:#f4f4f4;"><line x1="0" y1="0" x2="100" y2="100" stroke="#ddd" stroke-width="0.5"/><line x1="100" y1="0" x2="0" y2="100" stroke="#ddd" stroke-width="0.5"/></svg>
                            <i class="far fa-heart fav-icon"></i>
                        </div>
                        <div class="property-details">
                            <div class="property-title"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="property-price">Rs. <?php echo number_format($row['price']); ?></div>
                            <div class="property-loc"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></div>
                            
                            <div class="property-features">
                                <?php if(strtolower($row['property_type']) == 'house'): ?>
                                    <?php if($row['beds'] > 0): ?>
                                        <span><i class="fas fa-bed"></i> <?php echo $row['beds']; ?></span>
                                    <?php endif; ?>
                                    <?php if($row['baths'] > 0): ?>
                                        <span><i class="fas fa-bath"></i> <?php echo $row['baths']; ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <span><i class="fas fa-expand-arrows-alt"></i> <?php echo htmlspecialchars($row['sqft']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='grid-column: 1/-1; text-align: center; color: #777;'>No properties are currently available for sale in Mannar.</p>";
            }
            $conn->close();
            ?>
        </div>
    </section>
</div>

<?php 
include 'footer.php'; 
?>