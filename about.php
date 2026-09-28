<?php
// மேல் பகுதியை இணைத்தல்
include 'header.php'; 
?>

<style>
    /* பொதுவான ஸ்டைல்கள் */
    .about-wrapper { padding: 60px 5%; background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    
    /* Hero Section */
    .about-hero { 
        text-align: center; 
        background: linear-gradient(135deg, #007185 0%, #00a8c6 100%); 
        padding: 80px 20px; 
        border-radius: 25px; 
        margin-bottom: 50px; 
        color: white;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .about-hero h1 { font-size: 42px; margin-bottom: 15px; font-weight: 700; }
    .about-hero p { font-size: 18px; max-width: 700px; margin: 0 auto; opacity: 0.9; }
    
    /* Main Content */
    .about-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; margin-bottom: 60px; }
    @media (max-width: 768px) { .about-content { grid-template-columns: 1fr; } }
    
    .about-text h2 { font-size: 32px; color: #1e293b; margin-bottom: 20px; font-weight: 700; }
    .about-text p { font-size: 16px; color: #475569; line-height: 1.8; margin-bottom: 20px; }
    
    .about-img-box { height: 400px; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.15); transition: transform 0.3s; }
    .about-img-box:hover { transform: scale(1.03); }
    .about-img-box img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Features Section */
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px; }
    .feature-item { background: #fff; padding: 40px 30px; border-radius: 20px; text-align: center; border: 1px solid #edf2f7; transition: all 0.3s; }
    .feature-item:hover { transform: translateY(-10px); box-shadow: 0 20px 25px rgba(0,0,0,0.1); border-color: #007185; }
    .feature-item i { font-size: 40px; color: #007185; margin-bottom: 20px; background: #e0f2fe; padding: 20px; border-radius: 50%; }
    .feature-item h3 { font-size: 20px; margin-bottom: 15px; color: #1e293b; }
    .feature-item p { font-size: 15px; color: #64748b; line-height: 1.6; }
</style>

<div class="about-wrapper">
    <div class="about-hero">
        <h1>About LankaHomes</h1>
        <p>Welcome to LankaHomes, your trusted partner in finding the perfect property in Mannar. We connect buyers, sellers, and renters with ease and transparency.</p>
    </div>

    <div class="about-content">
        <div class="about-text">
            <h2>Our Story & Vision</h2>
            <p>Finding a new house or purchasing land in the Mannar district has often been a challenging process for many. LankaHomes was created specifically to simplify this journey and bridge the gap seamlessly.</p>
            <p>Through our platform, you can directly access verified and authentic property listings without the hassle of middlemen. Trust, reliability, and complete transparency are our core values.</p>
        </div>
        
        <div class="about-img-box">
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=800&q=80" alt="LankaHomes About Image">
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-size: 32px; color: #1e293b;">Why Choose Us?</h2>
    </div>
    
    <div class="features-grid">
        <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <h3>Verified Properties</h3>
            <p>We only list houses and lands that have been thoroughly verified by our team to ensure your peace of mind.</p>
        </div>
        <div class="feature-item">
            <i class="fas fa-users"></i>
            <h3>Expert Agents</h3>
            <p>The best real estate agents in the Mannar region are ready to assist you anytime with professional advice.</p>
        </div>
        <div class="feature-item">
            <i class="fas fa-search-location"></i>
            <h3>Local Expertise</h3>
            <p>We possess deep knowledge regarding land values and accurate location insights across all of Mannar city.</p>
        </div>
    </div>
</div>

<?php 
// கீழ் பகுதியை இணைத்தல்
include 'footer.php'; 
?>