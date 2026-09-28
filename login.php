<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
include 'header.php'; 

$error = "";

if (isset($_POST['login'])) {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['full_name']; 
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'agent') {
                    header("Location: agent_dashboard.php");
                } else {
                    header("Location: index.php"); 
                }
                exit();
            } else {
                $error = "Incorrect Password!";
            }
        } else {
            $error = "Email not registered!";
        }
    } else {
        $error = "Please fill all fields!";
    }
}
?>

<style>
    /* Updated body styling: removed flex centering */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0f4c5c 0%, #007185 100%);
        min-height: 100vh;
    }

    /* New wrapper to center only the login form */
    .login-content-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh; 
        padding: 20px;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.95);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
        width: 100%;
        max-width: 400px;
    }

    h2 {
        text-align: center;
        color: #0f4c5c;
        margin-bottom: 25px;
        font-weight: 700;
    }

    .form-group { margin-bottom: 20px; }

    label { display: block; margin-bottom: 8px; color: #475569; font-weight: 600; }

    input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        box-sizing: border-box;
        transition: 0.3s;
    }

    input:focus {
        border-color: #007185;
        outline: none;
        box-shadow: 0 0 8px rgba(0, 113, 133, 0.2);
    }

    button {
        width: 100%;
        padding: 14px;
        background: #007185;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }

    button:hover { background: #0f4c5c; }

    .error-msg {
        background: #fee2e2;
        color: #b91c1c;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        text-align: center;
        font-weight: 600;
        border: 1px solid #fecaca;
    }

    .register-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #64748b;
    }

    .register-link a { color: #007185; text-decoration: none; font-weight: 600; }
</style>

<!-- Login content centered by wrapper -->
<div class="login-content-wrapper">
    <div class="login-card">
        
        <h2>Log In</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-msg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777;"></i>
                </div>
            </div>

            <button type="submit" name="login">Log In Now</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

<script>
    const emailField = document.querySelector('#email');
    const passwordField = document.querySelector('#password');
    const togglePassword = document.querySelector('#togglePassword');

    emailField.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); 
            passwordField.focus();  
        }
    });

    togglePassword.addEventListener('click', function () {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>

<?php include 'footer.php'; ?>
<?php ob_end_flush(); ?>