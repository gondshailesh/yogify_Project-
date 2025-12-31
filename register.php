<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success_message = '';

// Handle registration form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    
    // Validate inputs
    if(empty($username)) $errors[] = "Username is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($password)) $errors[] = "Password is required";
    if(empty($full_name)) $errors[] = "Full name is required";
    
    // Validate email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check password match
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check password strength
    if(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if username exists
    $check_username = "SELECT id FROM users WHERE username = '$username'";
    $result_username = mysqli_query($conn, $check_username);
    if(mysqli_num_rows($result_username) > 0) {
        $errors[] = "Username already taken";
    }
    
    // Check if email exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result_email = mysqli_query($conn, $check_email);
    if(mysqli_num_rows($result_email) > 0) {
        $errors[] = "Email already registered";
    }
    
    // If no errors, register user
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, full_name, user_type) 
                  VALUES ('$username', '$email', '$hashed_password', '$full_name', '$user_type')";
        
        if(mysqli_query($conn, $query)) {
            $success_message = "Registration successful! You can now login.";
            
            // Auto login after registration (optional)
            // $user_id = mysqli_insert_id($conn);
            // $_SESSION['user_id'] = $user_id;
            // $_SESSION['user_name'] = $full_name;
            // $_SESSION['user_email'] = $email;
            // $_SESSION['user_type'] = $user_type;
            // header("Location: dashboard.php");
            // exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
            padding: 30px;
        }
        .btn-register {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #388E3C 0%, #689F38 100%);
            color: white;
        }
        .form-check-input:checked {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #28a745; width: 75%; }
        .strength-very-strong { background: #4CAF50; width: 100%; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-flower1 fs-1 mb-3"></i>
                <h2>Join Yogify</h2>
                <p class="mb-0">Create your free account</p>
            </div>
            
            <div class="register-body">
                <?php if($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" id="password" 
                                   class="form-control" required minlength="6">
                            <div id="passwordStrength" class="password-strength"></div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="confirmPassword" 
                                   class="form-control" required>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">I want to join as *</label>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" 
                                           value="student" id="student" checked>
                                    <label class="form-check-label" for="student">
                                        <i class="bi bi-person me-1"></i>Student
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" 
                                           value="instructor" id="instructor">
                                    <label class="form-check-label" for="instructor">
                                        <i class="bi bi-person-badge me-1"></i>Instructor
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="user_type" 
                                           value="admin" id="admin" disabled>
                                    <label class="form-check-label text-muted" for="admin">
                                        <i class="bi bi-shield-lock me-1"></i>Admin
                                    </label>
                                    <small class="d-block text-muted">(Contact support)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php">Terms & Conditions</a> and 
                            <a href="privacy.php">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-register w-100 mb-3">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-success">Sign in here</a>
                        </p>
                        <p class="mt-2">
                            <a href="index.php" class="text-muted">
                                <i class="bi bi-arrow-left me-1"></i>Back to Home
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            let strength = 0;
            
            // Length check
            if(password.length >= 6) strength++;
            if(password.length >= 8) strength++;
            
            // Character type checks
            if(/[A-Z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength ';
            if(strength <= 1) {
                strengthBar.className += 'strength-weak';
            } else if(strength <= 2) {
                strengthBar.className += 'strength-medium';
            } else if(strength <= 3) {
                strengthBar.className += 'strength-strong';
            } else {
                strengthBar.className += 'strength-very-strong';
            }
        });
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if(confirm === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if(password === confirm) {
                matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</small>';
            }
        }
        
        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            if(password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if(!terms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions!');
                return;
            }
        });
    </script>
</body>
</html>