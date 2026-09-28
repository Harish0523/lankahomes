<?php
// 1. செஷன் மற்றும் டேட்டாபேஸ் இணைப்பு
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; 
include 'header.php';

// 🛡️ பாதுகாப்பு: லாகின் செய்திருந்தால் மட்டுமே தொடர முடியும்
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_SESSION['user_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $property_type = $conn->real_escape_string($_POST['property_type']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $price = floatval($_POST['price']);
    $location = $conn->real_escape_string($_POST['location']);
    $beds = isset($_POST['beds']) ? intval($_POST['beds']) : 0;
    $baths = isset($_POST['baths']) ? intval($_POST['baths']) : 0;
    $sqft = $conn->real_escape_string($_POST['sqft']);

    // 🖼️ இமேஜ் அப்லோட் லாஜிக்
    $image_url = "";
    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["property_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["property_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    }

    // 💾 திருத்தப்பட்ட SQL குவாரி (அனைத்து ஃபீல்டுகளும் சேர்க்கப்பட்டுள்ளது)
    $sql = "INSERT INTO properties (user_id, title, property_type, purpose, price, sqft, bedrooms, bathrooms, location, image_url, status) 
            VALUES ('$user_id', '$title', '$property_type', '$purpose', '$price', '$sqft', '$beds', '$baths', '$location', '$image_url', 'available')";

    if ($conn->query($sql) === TRUE) {
        $message = "Property Added Successfully!";
        $message_type = "success";
    } else {
        $message = "Error: " . $conn->error;
        $message_type = "error";
    }
}
?>

<div class="wrapper">
    <div class="form-container">
        <h2>Add New Property</h2>
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="add-property.php" method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Property Title</label><input type="text" name="title" required></div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Property Type</label>
                    <select name="property_type" id="property_type" onchange="toggleFeatures()" required>
                        <option value="House">House</option>
                        <option value="Land">Land</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Purpose</label>
                    <select name="purpose" required>
                        <option value="buy">For Sale</option>
                        <option value="rent">For Rent</option>
                    </select>
                </div>
            </div>

            <div class="form-group"><label>Price</label><input type="number" name="price" required></div>
            <div class="form-group"><label>Area Size / Sqft</label><input type="text" name="sqft" required></div>

            <div id="house-only-features" class="form-row">
                <div class="form-group"><label>Bedrooms</label><input type="number" name="beds" value="0"></div>
                <div class="form-group"><label>Bathrooms</label><input type="number" name="baths" value="0"></div>
            </div>

            <div class="form-group"><label>Location</label><input type="text" name="location" required></div>
            <div class="form-group"><label>Property Image</label><input type="file" name="property_image" accept="image/*" required></div>

            <button type="submit" class="submit-btn">Post Property Now</button>
        </form>
    </div>
</div>

<script>
function toggleFeatures() {
    var type = document.getElementById("property_type").value;
    var houseFeatures = document.getElementById("house-only-features");
    houseFeatures.style.display = (type === "House") ? "flex" : "none";
}
// பக்கம் லோட் ஆகும்போது ஆரம்ப நிலையை உறுதி செய்ய
window.onload = toggleFeatures;
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>