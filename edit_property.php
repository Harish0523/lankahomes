<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

// Check if user is logged in
$current_logged_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : (isset($_SESSION['agent_id']) ? intval($_SESSION['agent_id']) : 0);

if ($current_logged_id == 0) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Validate Property ID
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($property_id == 0) {
    echo "<script>alert('Invalid Property Request!'); window.location.href='agent_dashboard.php';</script>";
    exit;
}

// Fetch property details from database
$p_query = $conn->query("SELECT * FROM properties WHERE id = $property_id AND (user_id = $current_logged_id OR agent_id = $current_logged_id)");
if (!$p_query || $p_query->num_rows == 0) {
    echo "<script>alert('Property not found or unauthorized access!'); window.location.href='agent_dashboard.php';</script>";
    exit;
}

$property = $p_query->fetch_assoc();
$success_msg = "";
$error_msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string(trim($_POST['title']));
    $price = floatval($_POST['price']);
    $purpose = $conn->real_escape_string(trim($_POST['purpose']));
    $location = $conn->real_escape_string(trim($_POST['location']));
    $property_type = $conn->real_escape_string(trim($_POST['property_type']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $final_image_path = $property['image_url'];

    // Image Upload
    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['property_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['property_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            $final_image_path = 'uploads/prop_' . $property_id . '_' . time() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $final_image_path);
        }
    }

    // SQL UPDATE
    $update_sql = "UPDATE properties SET 
                    title = '$title', 
                    price = $price, 
                    purpose = '$purpose', 
                    location = '$location', 
                    property_type = '$property_type', 
                    description = '$description', 
                    image_url = '$final_image_path' 
                   WHERE id = $property_id";
                   
    if ($conn->query($update_sql)) {
        $success_msg = "Property listing updated successfully!";
        $property['title'] = $title;
        $property['description'] = $description;
    } else {
        $error_msg = "Database Error: Unable to save changes.";
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
    .edit-prop-wrapper { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f8fafc; padding: 40px 5%; }
    .edit-prop-container { max-width: 650px; margin: 0 auto; background: white; border-radius: 16px; padding: 40px; border: 1px solid #e2e8f0; }
    .form-group { margin-bottom: 20px; }
    .form-label { font-weight: 600; color: #334155; margin-bottom: 8px; display: block; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; }
    .btn-submit { background: #007185; color: white; padding: 12px 20px; border-radius: 30px; border: none; cursor: pointer; width: 100%; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; }
    .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
</style>

<div class="edit-prop-wrapper">
    <div class="edit-prop-container">
        <h1>Edit Property Details</h1>
        
        <?php if (!empty($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
        
        <form action="edit_property.php?id=<?php echo $property_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Property Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Price (Rs.)</label>
                <input type="number" name="price" class="form-control" value="<?php echo htmlspecialchars($property['price']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Purpose</label>
                <select name="purpose" class="form-control">
                    <option value="sale" <?php if($property['purpose'] == 'sale') echo 'selected'; ?>>For Sale</option>
                    <option value="rent" <?php if($property['purpose'] == 'rent') echo 'selected'; ?>>For Rent</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($property['location']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Property Type</label>
                <select name="property_type" class="form-control">
                    <option value="House" <?php if($property['property_type'] == 'House') echo 'selected'; ?>>House</option>
                    <option value="Land" <?php if($property['property_type'] == 'Land') echo 'selected'; ?>>Land</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Update Listing</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>