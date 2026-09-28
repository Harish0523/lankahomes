<?php 
// 1. Output buffering and session start (to prevent header errors)
ob_start();
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LankaHomes - Properties in Mannar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
	    
		
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #fafafa; color: #333; }
        
        /* Navbar Design */
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 5%; background: #fff; border-bottom: 1px solid #eee; }
        .logo { font-size: 24px; font-weight: bold; color: #111; text-decoration: none; img:"OIP.jpg";}
        nav a { margin: 0 15px; text-decoration: none; color: #555; font-size: 14px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .add-property-btn { background: #111; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; }
        
        /* Hero Section Design */
        .hero-container { display: flex; padding: 60px 5%; align-items: center; background: #fff; gap: 40px; border-bottom: 1px solid #f0f0f0; }
        .hero-left { flex: 1; }
        .hero-right { flex: 1; text-align: right; }
        .hero-right img { width: 100%; max-width: 500px; height: auto; background: #eee; border: 1px solid #ddd; }
        .hero-left h1 { font-size: 42px; margin-bottom: 10px; font-weight: 700; color: #111; }
        .hero-left p { color: #666; margin-bottom: 30px; font-size: 16px; }
        
        /* Search Box */
        .search-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; gap: 10px; max-width: 650px; }
        .search-box select, .search-box input { padding: 12px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 14px; outline: none; }
        .search-box input { flex: 1; }
        .search-btn { background: #111; color: #fff; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
        .popular-searches { margin-top: 15px; font-size: 13px; color: #777; }
        .popular-searches a { color: #333; margin-left: 5px; text-decoration: none; border-bottom: 1px solid #333; }

        /* Wrapper */
        .wrapper { padding: 50px 5%; }
        section h2 { font-size: 22px; margin-bottom: 25px; font-weight: 600; }

        /* Category Grid */
        .category-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 60px; }
        .category-card { background: #fff; border: 1px solid #eaeaea; padding: 25px; text-align: center; border-radius: 8px; text-decoration: none; color: #222; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 12px; transition: transform 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .category-card:hover { transform: translateY(-3px); }
        .category-card i { font-size: 28px; color: #444; }

        /* Property Grid & Card Design */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .view-all { color: #111; font-size: 14px; font-weight: 500; text-decoration: underline; }
        .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
        .property-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; overflow: hidden; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .property-img { width: 100%; height: 180px; background: #eee; position: relative; }
        .fav-icon { position: absolute; top: 15px; right: 15px; color: #555; background: #fff; padding: 8px; border-radius: 50%; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .property-details { padding: 18px; }
        .property-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; color: #111; }
        .property-price { color: #111; font-weight: 700; font-size: 16px; margin-bottom: 8px; }
        .property-loc { color: #666; font-size: 13px; margin-bottom: 15px; }
        .property-features { display: flex; gap: 15px; font-size: 13px; color: #555; border-top: 1px solid #f0f0f0; padding-top: 12px; }
        
        footer { background: #111; color: #fff; text-align: center; padding: 20px; font-size: 14px; margin-top: 60px; }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo" img src="OIP.jpg">jaffnaHomes</a>
		
        <nav>
            <a href="buy.php">Buy</a>
            <a href="rent.php">Rent</a>
            <!-- Sell link is shown only to agents -->
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'agent'): ?>
                <a href="add-property.php">Sell</a>
            <?php endif; ?>
            <a href="agents.php">Agents</a> 
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
        </nav>
        <div class="nav-right">
             <a href="favorites.php" style="text-decoration: none; color: #111; display: flex; align-items: center; gap: 5px;">
             <i class="far fa-heart"></i> Favorites</a>

            <?php if(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'agent'): ?>
                    <a href="agent_dashboard.php" style="text-decoration: none; color: #007185; font-weight: 600; font-size: 14px; margin-right: 10px;">
                        <i class="fas fa-user-tie"></i> Dashboard
                    </a>
                    
                    <!-- Message link for agents -->
                    <a href="view-messages.php" style="text-decoration: none; color: #333; font-weight: 600; font-size: 14px; margin-right: 10px;">
                        <i class="fas fa-envelope" style="color: #007185;"></i> Messages
                    </a>
                <?php endif; ?>

                <span style="font-size: 14px; font-weight: 600; margin-right: 10px;">
                    <i class="far fa-user"></i> Hi, <?php echo htmlspecialchars($_SESSION['name']); ?>
                </span>
                
                <a href="logout.php" style="text-decoration:none; color:red; font-size: 14px; font-weight:600; margin-left: 5px;">Logout</a>
            
            <?php else: ?>
                <a href="login.php" style="text-decoration:none; color:#333;"><i class="far fa-user"></i> Login / Signup</a>
            <?php endif; ?>
            
            <!-- Add Property button shown only to agents -->
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'agent'): ?>
                <a href="add-property.php" class="add-property-btn">Add Property</a>
            <?php endif; ?>
        </div>
    </header>