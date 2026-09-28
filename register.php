<?php
session_start();
include 'db.php';
include 'header.php'; 

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = trim($_POST['password']);
    $role = $conn->real_escape_string($_POST['role']); 
    
    $phone = isset($_POST['phone']) ? $conn->real_escape_string(trim($_POST['phone'])) : "";
    $address = isset($_POST['address']) ? $conn->real_escape_string(trim($_POST['address'])) : "";

    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) NOT NULL AFTER id");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'customer'");
    
    $conn->query("ALTER TABLE agents ADD COLUMN IF NOT EXISTS user_id INT NOT NULL AFTER id");
    $conn->query("ALTER TABLE agents ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL");
    $conn->query("ALTER TABLE agents ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL");

    $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($check_email && $check_email->num_rows > 0) {
        $error = "Email is already registered!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";
        
        if ($conn->query($sql) === TRUE) {
            $new_user_id = $conn->insert_id; 
            
            if ($role === 'agent') {
                $agent_sql = "INSERT INTO agents (user_id, name, email, phone, address) VALUES ($new_user_id, '$username', '$email', '$phone', '$address')";
                
                if ($conn->query($agent_sql) !== TRUE) {
                    $error = "User registered, but database error in Agent Table: " . $conn->error;
                }
            }
            
            if (empty($error)) {
                $success = "Registration successful! You can now log in.";
            }
        } else {
            $error = "Database error in Users Table: " . $conn->error;
        }
    }
}
?>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%);
        min-height: 100vh;
    }

    .register-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .register-card {
        background: rgba(255, 255, 255, 0.95);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
        width: 100%;
        max-width: 450px;
    }

    h2 { text-align: center; color: #0f4c5c; margin-bottom: 25px; font-weight: 700; }

    .form-group { margin-bottom: 20px; }

    label { display: block; margin-bottom: 8px; color: #475569; font-weight: 600; }

    input, select, textarea {
        width: 100%; padding: 12px; border: 2px solid #e2e8f0;
        border-radius: 10px; box-sizing: border-box; transition: 0.3s;
    }

    input:focus, select:focus, textarea:focus {
        border-color: #007185; outline: none; box-shadow: 0 0 8px rgba(0, 113, 133, 0.2);
    }

    button {
        width: 100%; padding: 14px; background: #007185; color: white;
        border: none; border-radius: 10px; font-size: 16px; font-weight: 600;
        cursor: pointer; transition: background 0.3s;
    }

    button:hover { background: #0f4c5c; }

    .error-msg { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 600; }
    .success-msg { background: #dcfce7; color: #166534; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: 600; }

    #agentFields {
        background: #f8fafc; padding: 15px; border-radius: 10px;
        border: 1px dashed #cbd5e1; margin-bottom: 20px; display: none;
    }
</style>

<div class="register-wrapper">
    <div class="register-card">
        
        <h2>Create an Account</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="regForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Register As</label>
                <select name="role" id="roleSelect" onchange="toggleAgentFields()">
                    <option value="customer">Customer (Property Seeker)</option>
                    <option value="agent">Agent / Owner</option>
                </select>
            </div>

            <div id="agentFields">
                <label style="color: #007185; font-size: 13px; margin-bottom: 10px;">Required for Agents / Owners</label>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. +94 77 123 4567">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2"></textarea>
                </div>
            </div>

            <button type="submit" name="register">Register Now</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: #64748b;">
            Already have an account? <a href="login.php" style="color: #007185; text-decoration: none; font-weight: 600;">Login here</a>
        </div>
    </div>
</div>

<script>
    function toggleAgentFields() {
        const role = document.getElementById('roleSelect').value;
        const agentFields = document.getElementById('agentFields');
        agentFields.style.display = (role === 'agent') ? 'block' : 'none';
    }
</script>

<?php include 'footer.php'; ?>