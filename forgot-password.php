<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$error = '';

// Handle password reset request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $query = "SELECT * FROM users WHERE email = '$email' AND is_active = 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate reset token (in real app, you would send email)
        $reset_token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database (you need to add reset_token and reset_expiry columns)
        $update_query = "UPDATE users SET reset_token = '$reset_token', reset_expiry = '$expiry' WHERE id = {$user['id']}";
        mysqli_query($conn, $update_query);
        
        // In production: Send email with reset link
        // $reset_link = "https://yogify.com/reset-password.php?token=$reset_token";
        // mail($email, "Password Reset", "Click here to reset: $reset_link");
        
        $message = "Password reset instructions have been sent to your email.";
    } else {
        $error = "No account found with this email!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white text-center py-4">
                        <i class="bi bi-key fs-1 mb-3"></i>
                        <h3>Forgot Password</h3>
                    </div>
                    <div class="card-body p-5">
                        <?php if($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-success">Back to Login</a>
                            </div>
                        <?php else: ?>
                            <?php if($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <p class="text-muted mb-4">
                                Enter your email address and we'll send you instructions to reset your password.
                            </p>
                            
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control form-control-lg" 
                                           placeholder="Enter your email" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                                    <i class="bi bi-envelope me-2"></i>Send Reset Link
                                </button>
                                
                                <div class="text-center">
                                    <a href="login.php" class="text-success">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Login
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>