<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// 1. Detect user login
$current_logged_id = 0;
$table_name = '';

if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
    $current_logged_id = intval($_SESSION['user_id']);
    $table_name = 'users';
} elseif (isset($_SESSION['agent_id']) && intval($_SESSION['agent_id']) > 0) {
    $current_logged_id = intval($_SESSION['agent_id']);
    $table_name = 'agents';
}

if ($current_logged_id == 0) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// 2. Detect table columns
$columns = [];
$col_res = $conn->query("SHOW COLUMNS FROM $table_name");
if ($col_res) {
    while ($col_row = $col_res->fetch_assoc()) {
        $columns[] = strtolower($col_row['Field']);
    }
}

$name_column = '';
if (in_array('fullname', $columns)) { $name_column = 'fullname'; }
elseif (in_array('full_name', $columns)) { $name_column = 'full_name'; }
elseif (in_array('name', $columns)) { $name_column = 'name'; }

$image_column = '';
$possible_image_cols = ['profile_pic', 'image_url', 'image', 'avatar'];
foreach ($possible_image_cols as $possible_col) {
    if (in_array($possible_col, $columns)) {
        $image_column = $possible_col;
        break;
    }
}

// 3. Fetch current details
$phone = ''; // Create variable to store phone number
$u_query = $conn->query("SELECT * FROM $table_name WHERE id = $current_logged_id");
if ($u_query && $u_query->num_rows > 0) {
    $u_data = $u_query->fetch_assoc();
    $name = (!empty($name_column) && isset($u_data[$name_column])) ? $u_data[$name_column] : '';
    $email = (in_array('email', $columns) && isset($u_data['email'])) ? $u_data['email'] : '';
    $image = (!empty($image_column) && isset($u_data[$image_column])) ? $u_data[$image_column] : '';
    $phone = (in_array('phone', $columns) && isset($u_data['phone'])) ? $u_data['phone'] : '';
    $old_email = $conn->real_escape_string(trim($email)); 

    // If logged in table is 'users' and phone number is empty, look in 'agents' table
    if ($table_name === 'users' && empty($phone)) {
        $agent_check_q = $conn->query("SELECT phone FROM agents WHERE user_id = $current_logged_id OR email = '$old_email'");
        if ($agent_check_q && $agent_check_q->num_rows > 0) {
            $agent_check_d = $agent_check_q->fetch_assoc();
            $phone = $agent_check_d['phone'];
        }
    }
} else {
    // If logged in as agent
    $u_query = $conn->query("SELECT * FROM agents WHERE id = $current_logged_id OR user_id = $current_logged_id");
    if($u_query && $u_query->num_rows > 0) {
        $u_data = $u_query->fetch_assoc();
        $name = $u_data['name'];
        $email = $u_data['email'];
        $image = $u_data['image_url'];
        $phone = isset($u_data['phone']) ? $u_data['phone'] : '';
        $old_email = $conn->real_escape_string(trim($email));
    } else {
        echo "<script>alert('Account not found!'); window.location.href='login.php';</script>";
        exit;
    }
}

// 4. When the form is submitted (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_name = $conn->real_escape_string(trim($_POST['name']));
    $updated_email = $conn->real_escape_string(trim($_POST['email']));
    $updated_phone = $conn->real_escape_string(trim($_POST['phone'])); // Get phone number via POST
    $final_image_path = $image;

    // Profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['name'] !== '') {
        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
                $new_file_name = "profile_" . $current_logged_id . "_" . time() . "." . $file_ext;
                $upload_path = 'uploads/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $final_image_path = $upload_path;
                }
            }
        }
    }

    // 🌟 A. Update USERS table (updates phone column as well if it exists in the users table)
    if (in_array('phone', $columns)) {
        $user_update = "UPDATE users SET full_name = '$updated_name', fullname = '$updated_name', email = '$updated_email', phone = '$updated_phone' WHERE id = '$current_logged_id'";
    } else {
        $user_update = "UPDATE users SET full_name = '$updated_name', fullname = '$updated_name', email = '$updated_email' WHERE id = '$current_logged_id'";
    }
    $conn->query($user_update);

    // 🌟 B. Synchronize AGENTS table accurately (updates phone number as well)
    $agent_sync = "UPDATE agents SET 
                    name = '$updated_name', 
                    image_url = '$final_image_path', 
                    email = '$updated_email',
                    phone = '$updated_phone',
                    user_id = '$current_logged_id'
                   WHERE user_id = '$current_logged_id' OR email = '$old_email'";
    $conn->query($agent_sync);
    
    // Redirecting to avoid duplicate submission after successful update
    echo "<script>alert('Profile updated successfully!'); window.location.href='edit_profile.php';</script>";
    exit;
}

// Including header here to avoid duplicate output
include 'header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .edit-profile-wrapper { font-family: 'Segoe UI', Tahoma, system-ui, sans-serif; background-color: #f8fafc; min-height: 70vh; padding: 40px 5%; }
    .edit-profile-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
    .form-header { text-align: center; margin-bottom: 30px; }
    .form-header h1 { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
    .form-header p { font-size: 14px; color: #64748b; margin: 0; }
    .image-upload-section { display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 30px; }
    .image-preview-container { width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid #007185; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
    .image-preview-container img { width: 100%; height: 100%; object-fit: cover; }
    .upload-label { font-size: 13.5px; font-weight: 600; color: #007185; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 20px; }
    .upload-label input { display: none; }
    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
    .form-label { font-size: 14px; font-weight: 600; color: #334155; text-align: left; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14.5px; box-sizing: border-box; }
    .form-actions { display: flex; gap: 12px; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 25px; }
    .btn { flex: 1; padding: 12px 20px; border-radius: 30px; font-weight: 600; font-size: 14.5px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; cursor: pointer; }
    .btn-submit { background: #007185; color: white; border: none; }
    .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid transparent; }
</style>

<div class="edit-profile-wrapper">
    <div class="edit-profile-container">
        <div class="form-header">
            <h1>Edit Profile Settings</h1>
            <p>Modify your name, email address, phone number or update your profile photo.</p>
        </div>

        <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <div class="image-upload-section">
                <div class="image-preview-container">
                    <?php if (!empty($image) && file_exists($image)): ?>
                        <img id="profile-preview-img" src="<?php echo htmlspecialchars($image) . '?v=' . time(); ?>" alt="Profile Preview">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 80px; color: #cbd5e1;"></i>
                    <?php endif; ?>
                </div>
                <label class="upload-label">
                    <i class="fas fa-camera"></i> Change Photo
                    <input type="file" name="profile_image" accept="image/*" onchange="previewProfileImage(this)">
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($name); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <!-- Newly added Phone Number Input Field -->
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" placeholder="e.g. +94 77 123 4567">
            </div>

            <div class="form-actions">
                <a href="agent_dashboard.php" class="btn btn-cancel"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-submit"><i class="fas fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('profile-preview-img');
            if(!img) {
                const container = document.querySelector('.image-preview-container');
                container.innerHTML = `<img id="profile-preview-img" src="" alt="Profile Preview">`;
                img = document.getElementById('profile-preview-img');
            }
            img.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php 
include 'footer.php'; 
exit; 
?>