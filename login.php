<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

// Display success message if redirected from registration
if(isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_message = "Registration successful! Please login.";
}

// Handle login form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Check if user exists
    $query = "SELECT * FROM users WHERE email = '$email' AND is_active = 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password - handle both plain text (for demo) and hashed passwords
        if(password_verify($password, $user['password']) || $password == $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Update last login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = {$user['id']}";
            mysqli_query($conn, $update_query);
            
            // Redirect based on user type
            if($user['user_type'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                // Check for redirect URL
                $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
                unset($_SESSION['redirect_url']);
                header("Location: $redirect_url");
            }
            exit();
        } else {
            $error_message = "Invalid password!";
        }
    } else {
        $error_message = "No account found with this email!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #388E3C 0%, #689F38 100%);
            color: white;
        }
        .login-links {
            margin-top: 20px;
            text-align: center;
        }
        .login-links a {
            color: #4CAF50;
            text-decoration: none;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        .yoga-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .demo-btn {
            padding: 2px 8px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-flower1 yoga-icon"></i>
                <h2>Welcome to Yogify</h2>
                <p class="mb-0">Sign in to your account</p>
            </div>
            
            <div class="login-body">
                <?php if($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="Enter your email" required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Enter your password" required id="passwordInput">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                        <a href="forgot-password.php" class="float-end">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                    
                    <div class="login-links">
                        <p class="mb-2">Don't have an account? 
                            <a href="register.php">Sign up here</a>
                        </p>
                        <p class="mb-0">
                            <a href="index.php">Back to Home</a>
                        </p>
                    </div>
                </form>
                
                <!-- Demo Accounts (for testing) -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-muted mb-2">Test Accounts:</h6>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <small class="text-muted">Admin:</small><br>
                            <small>admin@gmail.com / admin123</small>
                            <button class="btn btn-sm btn-outline-success demo-btn" onclick="fillDemo('admin@gmail.com', 'admin123')">
                                <i class="bi bi-rocket-takeoff"></i>
                            </button>
                        </div>
                        <div class="col-12 mb-2">
                            <small class="text-muted">Instructor:</small><br>
                            <small>instructor@gmail.com / instructor123</small>
                            <button class="btn btn-sm btn-outline-primary demo-btn" onclick="fillDemo('instructor@gmail.com', 'instructor123')">
                                <i class="bi bi-rocket-takeoff"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Student:</small><br>
                            <small>student@gmail.com / student123</small>
                            <button class="btn btn-sm btn-outline-info demo-btn" onclick="fillDemo('student@gmail.com', 'student123')">
                                <i class="bi bi-rocket-takeoff"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const icon = this.querySelector('i');
            
            if(passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Demo account auto-fill
        // function fillDemo(email, password) {
        //     document.querySelector('input[name="email"]').value = email;
        //     document.getElementById('passwordInput').value = password;
        // }
        
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="email"]').focus();
        });
    </script>
</body>
</html>