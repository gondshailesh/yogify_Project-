<?php
session_start();
include_once 'includes/dbconnect.php';

$token = $_GET['token'] ?? '';
$message = '';
$error = '';

if(empty($token)) {
    header("Location: login.php");
    exit();
}

// Check if token exists and not expired
$query = "SELECT * FROM users WHERE reset_token = '$token' AND reset_expiry > NOW()";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    $error = "Invalid or expired reset token!";
} else {
    $user = mysqli_fetch_assoc($result);
    
    // Handle password reset
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif(strlen($password) < 6) {
            $error = "Password must be at least 6 characters!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE users SET password = '$hashed_password', 
                           reset_token = NULL, reset_expiry = NULL 
                           WHERE id = {$user['id']}";
            
            if(mysqli_query($conn, $update_query)) {
                $message = "Password reset successful! You can now login.";
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white text-center py-4">
                        <i class="bi bi-shield-lock fs-1 mb-3"></i>
                        <h3>Reset Your Password</h3>
                    </div>
                    <div class="card-body p-5">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <div class="text-center">
                                <a href="forgot-password.php" class="btn btn-warning">Request New Reset Link</a>
                            </div>
                        <?php elseif($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-success">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-4">
                                Please enter your new password below.
                            </p>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter new password" required minlength="6">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           placeholder="Confirm new password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                                    <i class="bi bi-check-circle me-2"></i>Reset Password
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