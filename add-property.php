<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
include 'db.php'; 
include 'header.php'; 

if (!isset($_SESSION['user_id']) && !isset($_SESSION['agent_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $property_type = $conn->real_escape_string($_POST['property_type']);
    
    $purpose_input = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'sale';
    if (empty($purpose_input)) { 
        $purpose_input = 'sale'; 
    }
    $purpose = $conn->real_escape_string(strtolower($purpose_input));
    
    $price = floatval($_POST['price']);
    $sqft = $conn->real_escape_string($_POST['sqft']);
    $location = $conn->real_escape_string($_POST['location']);
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
    
    $bedrooms = ($property_type === 'House' && !empty($_POST['bedrooms'])) ? intval($_POST['bedrooms']) : "NULL";
    $bathrooms = ($property_type === 'House' && !empty($_POST['bathrooms'])) ? intval($_POST['bathrooms']) : "NULL";
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['agent_id'];

    // PHP Multiple Upload Handler
    $uploaded_images = []; 
    if (isset($_FILES['property_images']) && is_array($_FILES['property_images']['name'])) {
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $total_files = count($_FILES['property_images']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['property_images']['error'][$i] == 0) {
                $file_extension = strtolower(pathinfo($_FILES["property_images"]["name"][$i], PATHINFO_EXTENSION));
                $new_file_name = "img_" . time() . "_" . rand(1000, 9999) . "_" . $i . "." . $file_extension;
                $target_file = $target_dir . $new_file_name;
                
                $allowed_types = array("jpg", "jpeg", "png", "webp");
                if (in_array($file_extension, $allowed_types)) {
                    if (move_uploaded_file($_FILES["property_images"]["tmp_name"][$i], $target_file)) {
                        $uploaded_images[] = $target_file; 
                    }
                }
            }
        }
    }

    $image_url_string = !empty($uploaded_images) ? implode(",", $uploaded_images) : "";

    if (empty($message)) {
        $sql = "INSERT INTO properties (user_id, title, property_type, purpose, price, sqft, bedrooms, bathrooms, location, description, image_url, status) 
                VALUES ('$user_id', '$title', '$property_type', '$purpose', '$price', '$sqft', $bedrooms, $bathrooms, '$location', '$description', '$image_url_string', 'available')";
        
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> 🎉 Successfully posted with images!</div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Database Error: " . $conn->error . "</div>";
        }
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f1f5f9; }
    .form-wrapper { padding: 60px 0; display: flex; justify-content: center; background: #f8fafc; }
    .form-card { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(15, 76, 92, 0.08); width: 100%; max-width: 680px; border: 1px solid #e2e8f0; }
    .form-title { margin-bottom: 30px; font-size: 26px; font-weight: 700; color: #0f4c5c; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
    .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; font-size: 14px; color: #334155; background-color: #fff; transition: all 0.3s ease; outline: none; }
    .form-control:focus { border-color: #007185; box-shadow: 0 0 0 4px rgba(0, 113, 133, 0.1); }
    .house-features-box { display: none; background: #f0fdfa; padding: 20px; border-radius: 12px; border: 1px solid #ccfbf1; margin-top: 5px; }
    
    /* 5 Slots Grid Styling */
    .upload-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 10px; }
    .upload-box { border: 2px dashed #007185; border-radius: 10px; height: 100px; display: flex; align-items: center; justify-content: center; position: relative; cursor: pointer; background: #f8fafc; overflow: hidden; transition: all 0.3s ease; }
    .upload-box:hover { border-color: #0f4c5c; background: #f0fdfa; }
    .upload-placeholder { display: flex; flex-direction: column; align-items: center; color: #007185; font-size: 11px; font-weight: 600; text-align: center; }
    .upload-placeholder i { font-size: 18px; margin-bottom: 4px; }
    
    .preview-img { width: 100%; height: 100%; object-fit: cover; display: none; }
    .preview-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(15, 76, 92, 0.85); color: #fff; font-size: 10px; text-align: center; padding: 2px 0; font-weight: 600; }
    
    /* Delete Button Styling */
    .remove-btn { position: absolute; top: 4px; right: 4px; background: rgba(239, 68, 68, 0.9); color: white; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; font-weight: bold; cursor: pointer; display: none; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s; }
    .remove-btn:hover { background: rgba(220, 38, 38, 1); }

    .btn-submit { background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%); color: #fff; border: none; padding: 14px 30px; font-size: 16px; font-weight: 600; border-radius: 10px; cursor: pointer; width: 100%; margin-top: 15px; }
    .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background-color: #d1fae5; color: #065f46; }
    .alert-danger { background-color: #fee2e2; color: #991b1b; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>

<div class="form-wrapper">
    <div class="form-card">
        <h2 class="form-title"><i class="fas fa-plus-circle"></i> Add New Property</h2>
        <?php echo $message; ?>
        <form action="add-property.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label class="form-label">Property Title</label>
                <input type="text" name="title" required placeholder="e.g. Beautiful Modern House / Prime Land for Sale" class="form-control">
            </div>

            <div class="grid-2">
                <div>
                    <label class="form-label">Property Type</label>
                    <select name="property_type" id="property_type" required class="form-control" onchange="checkPropertyType(this.value)">
                        <option value="">Select Type</option>
                        <option value="House">House</option>
                        <option value="Land">Land</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Purpose</label>
                    <select name="purpose" required class="form-control">
                        <option value="sale">For Sale</option>
                        <option value="rent">For Rent</option>
                    </select>
                </div>
            </div>

            <div id="house-features" class="house-features-box">
                <div class="grid-2" style="gap: 15px;">
                    <div>
                        <label class="form-label"><i class="fas fa-bed"></i> Total Bedrooms</label>
                        <select name="bedrooms" class="form-control"><option value="">Select Bedrooms</option><option value="1">1 Bedroom</option><option value="2">2 Bedrooms</option><option value="3">3 Bedrooms</option><option value="4">4 Bedrooms</option><option value="5">5+ Bedrooms</option></select>
                    </div>
                    <div>
                        <label class="form-label"><i class="fas fa-bath"></i> Total Bathrooms</label>
                        <select name="bathrooms" class="form-control"><option value="">Select Bathrooms</option><option value="1">1 Bathroom</option><option value="2">2 Bathrooms</option><option value="3">3 Bathrooms</option><option value="4">4+ Bathrooms</option></select>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label class="form-label">Price (Rs.)</label>
                    <input type="number" name="price" required placeholder="e.g. 5000000" class="form-control">
                </div>
                <div>
                    <label class="form-label">Area Size / Sqft</label>
                    <input type="text" name="sqft" required placeholder="e.g. 1500 sqft or 10 Perches" class="form-control">
                </div>
            </div>

            <div>
                <label class="form-label">Location</label>
                <input type="text" name="location" value="Mannar Town, Mannar" required class="form-control">
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea name="description" placeholder="Write a detailed description about your property..." class="form-control" style="min-height: 120px; resize: vertical;" required></textarea>
            </div>

            <div>
                <label class="form-label">Upload Images (Max 5 Images - One by One)</label>
                <div class="upload-grid">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="upload-box" id="box-<?php echo $i; ?>" onclick="triggerInput(<?php echo $i; ?>)">
                            <div class="upload-placeholder" id="placeholder-<?php echo $i; ?>">
                                <i class="fas fa-plus"></i>
                                <span>Image <?php echo $i; ?></span>
                            </div>
                            <img id="preview-<?php echo $i; ?>" class="preview-img">
                            <span class="preview-label" id="label-<?php echo $i; ?>" style="display:none;">Image <?php echo $i; ?></span>
                            <button type="button" class="remove-btn" id="remove-<?php echo $i; ?>" onclick="clearInput(event, <?php echo $i; ?>)">&times;</button>
                            <input type="file" name="property_images[]" id="file-<?php echo $i; ?>" accept="image/*" style="display: none;" onchange="previewIndividualImage(this, <?php echo $i; ?>)">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Post Property Now</button>
        </form>
    </div>
</div>

<script>
function checkPropertyType(type) {
    const featuresBox = document.getElementById('house-features');
    featuresBox.style.display = (type === 'House') ? 'block' : 'none';
}

// Trigger input click for specific index
function triggerInput(index) {
    document.getElementById('file-' + index).click();
}

// Real-time Preview logic for individual boxes
function previewIndividualImage(input, index) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('placeholder-' + index).style.display = 'none';
            
            const img = document.getElementById('preview-' + index);
            img.src = e.target.result;
            img.style.display = 'block';
            
            document.getElementById('label-' + index).style.display = 'block';
            document.getElementById('remove-' + index).style.display = 'flex';
        }
        reader.readAsDataURL(file);
    }
}

// Clear individual slot selection
function clearInput(event, index) {
    event.stopPropagation(); // Prevents triggering file dialog again
    const input = document.getElementById('file-' + index);
    input.value = ''; // Clears file input value
    
    document.getElementById('preview-' + index).style.display = 'none';
    document.getElementById('preview-' + index).src = '';
    document.getElementById('label-' + index).style.display = 'none';
    document.getElementById('remove-' + index).style.display = 'none';
    
    document.getElementById('placeholder-' + index).style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>