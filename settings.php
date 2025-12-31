<?php
session_start();
include_once 'includes/dbconnect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Handle password change
$password_updated = false;
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $check_query = "SELECT password FROM users WHERE id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    $user = mysqli_fetch_assoc($check_result);
    
    if(password_verify($current_password, $user['password'])) {
        if($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if(mysqli_query($conn, $update_query)) {
                $password_updated = true;
                $_SESSION['message'] = "Password updated successfully!";
                $_SESSION['msg_type'] = "success";
            }
        } else {
            $_SESSION['message'] = "New passwords do not match!";
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Current password is incorrect!";
        $_SESSION['msg_type'] = "danger";
    }
}

// Handle notification settings
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $course_updates = isset($_POST['course_updates']) ? 1 : 0;
    $live_class_reminders = isset($_POST['live_class_reminders']) ? 1 : 0;
    
    $update_query = "UPDATE users SET 
                     email_notifications = $email_notifications,
                     course_updates = $course_updates,
                     live_class_reminders = $live_class_reminders
                     WHERE id = $user_id";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Settings saved successfully!";
        $_SESSION['msg_type'] = "success";
    }
}

// Get current settings
$settings_query = "SELECT * FROM users WHERE id = $user_id";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <div class="row">
            <div class="col-lg-3">
                <?php include_once 'includes/profile-sidebar.php'; ?>
            </div>
            
            <div class="col-lg-9">
                <h2 class="mb-4">Account Settings</h2>
                
                <!-- Password Change -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-success">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Notification Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="email_notifications" 
                                       id="email_notifications" value="1" 
                                       <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Email Notifications
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="course_updates" 
                                       id="course_updates" value="1"
                                       <?php echo ($settings['course_updates'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="course_updates">
                                    Course Updates
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="live_class_reminders" 
                                       id="live_class_reminders" value="1"
                                       <?php echo ($settings['live_class_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="live_class_reminders">
                                    Live Class Reminders
                                </label>
                            </div>
                            <button type="submit" name="save_settings" class="btn btn-success">
                                Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>