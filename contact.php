<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php'; 
include 'header.php'; 

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $message_text = $conn->real_escape_string($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message_text)) {
        $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message_text')";
        if ($conn->query($sql) === TRUE) {
            $success_msg = "🎉 Your message has been sent successfully!";
        } else {
            $error_msg = "❌ Error: " . $conn->error;
        }
    } else {
        $error_msg = "❌ Please fill in all required fields!";
    }
}
?>

<style>
    .contact-wrapper { padding: 60px 5%; background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    .contact-container { display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; max-width: 1100px; margin: 0 auto; }
    @media (max-width: 768px) { .contact-container { grid-template-columns: 1fr; } }

    .info-box, .form-box { background: #ffffff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .info-box h2, .form-box h2 { color: #1e293b; font-size: 28px; margin-bottom: 30px; font-weight: 700; }

    .contact-item { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 30px; }
    .contact-item i { font-size: 22px; color: #007185; background: #e0f2fe; padding: 15px; border-radius: 50%; }
    .contact-item h4 { margin: 0 0 5px 0; font-size: 16px; color: #1e293b; }
    .contact-item p { margin: 0; color: #64748b; font-size: 14px; line-height: 1.6; }

    .form-group { margin-bottom: 20px; }
    .form-control { width: 100%; padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; transition: 0.3s; }
    .form-control:focus { border-color: #007185; box-shadow: 0 0 0 3px rgba(0,113,133,0.1); }

    .btn-submit { 
        width: 100%; background: linear-gradient(90deg, #007185 0%, #00a8c6 100%); 
        color: white; padding: 15px; border: none; border-radius: 10px; 
        font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 16px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,113,133,0.3); }
</style>

<div class="contact-wrapper">
    <div class="contact-container">
        <div class="info-box">
            <h2>Get In Touch</h2>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div><h4>Our Office Location</h4><p>Main Street, Mannar Town, Mannar, Sri Lanka.</p></div>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone-alt"></i>
                <div><h4>Phone Number</h4><p>+94 76 208 4145<br>+94 77 040 6324</p></div>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div><h4>Email Address</h4><p>info@lankahomes.com<br>support@lankahomes.com</p></div>
            </div>
        </div>

        <div class="form-box">
            <h2>Send Us a Message</h2>
            <?php if(!empty($success_msg)): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 10px; margin-bottom: 20px;"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 10px; margin-bottom: 20px;"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <form action="contact.php" method="POST">
                <div class="form-group"><input type="text" name="name" class="form-control" placeholder="Your Full Name" required></div>
                <div class="form-group"><input type="email" name="email" class="form-control" placeholder="Your Email Address" required></div>
                <div class="form-group"><input type="text" name="subject" class="form-control" placeholder="Subject"></div>
                <div class="form-group"><textarea name="message" class="form-control" rows="5" placeholder="Write your message here..." required></textarea></div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>