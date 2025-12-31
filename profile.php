<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if(in_array($file_type, $allowed_types)) {
            $upload_dir = 'uploads/profiles/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                // Delete old profile image if exists
                if($profile_image && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $destination;
            }
        }
    }
    
    $update_query = "UPDATE users SET full_name = '$full_name', bio = '$bio', 
                     profile_image = '$profile_image' WHERE id = $user_id";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['msg_type'] = "success";
        $_SESSION['user_name'] = $full_name;
        $user_name = $full_name;
        header("Location: profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .profile-hero {
            background: linear-gradient(rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.9));
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .profile-img-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            display: block;
        }
        .file-upload {
            position: relative;
            display: inline-block;
        }
        .file-upload-input {
            display: none;
        }
        .file-upload-label {
            display: block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .file-upload-label:hover {
            background: #388E3C;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Messages -->
    <?php 
    if(isset($_SESSION['message'])): 
        $type = $_SESSION['msg_type'] ?? 'info';
    ?>
    <div class="container mt-5 pt-5">
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php 
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    endif; 
    ?>

    <!-- Hero Section -->
    <div class="profile-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="profile-img-container">
                        <?php if(!empty($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" alt="Profile" class="profile-img">
                        <?php else: ?>
                            <div class="profile-img bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-fill fs-1 text-secondary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="display-5 fw-bold mb-3"><?php echo $user['full_name'] ?? $user_name; ?></h1>
                    <p class="lead mb-0">
                        <i class="bi bi-envelope me-2"></i><?php echo $user_email; ?>
                    </p>
                    <p class="mb-0">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="bi bi-person-badge me-1"></i><?php echo ucfirst($user_type); ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6 ms-2">
                            <i class="bi bi-calendar me-1"></i>
                            Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="profile-sidebar">
                    <h4 class="mb-4">Profile Menu</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a class="nav-link active" href="profile.php">
                                <i class="bi bi-person me-2"></i>Profile Details
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="my-courses.php">
                                <i class="bi bi-book me-2"></i>My Courses
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="my-progress.php">
                                <i class="bi bi-graph-up me-2"></i>My Progress
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a class="nav-link" href="settings.php">
                                <i class="bi bi-gear me-2"></i>Account Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="my-4">
                    
                    <!-- Quick Stats -->
                    <h5 class="mb-3">Quick Stats</h5>
                    <?php
                    // Get user stats
                    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM enrollments WHERE user_id = $user_id) as enrolled_courses,
                        (SELECT COUNT(*) FROM enrollments WHERE user_id = $user_id AND completed = 1) as completed_courses,
                        (SELECT COUNT(*) FROM schedule_registrations WHERE user_id = $user_id) as live_classes";
                    $stats_result = mysqli_query($conn, $stats_query);
                    $stats = mysqli_fetch_assoc($stats_result);
                    ?>
                    <div class="stats-card">
                        <span class="stats-number"><?php echo $stats['enrolled_courses']; ?></span>
                        <small>Enrolled Courses</small>
                    </div>
                    <div class="stats-card">
                        <span class="stats-number"><?php echo $stats['completed_courses']; ?></span>
                        <small>Completed</small>
                    </div>
                    <div class="stats-card">
                        <span class="stats-number"><?php echo $stats['live_classes']; ?></span>
                        <small>Live Classes</small>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Profile Form -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Edit Profile</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?php echo $user['full_name'] ?? $user_name; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo $user_email; ?>" disabled>
                                        <small class="text-muted">Contact support to change email</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">User Type</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($user_type); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea name="bio" class="form-control" rows="4" 
                                                  placeholder="Tell us about yourself..."><?php echo $user['bio'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <div class="profile-img-container mb-3" style="width: 120px; height: 120px;">
                                            <?php if(!empty($user['profile_image'])): ?>
                                                <img src="<?php echo $user['profile_image']; ?>" alt="Profile" class="profile-img" id="profilePreview">
                                            <?php else: ?>
                                                <div class="profile-img bg-light d-flex align-items-center justify-content-center" id="profilePreview">
                                                    <i class="bi bi-person-fill fs-1 text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="file-upload">
                                            <input type="file" name="profile_image" id="profileImage" 
                                                   class="file-upload-input" accept="image/*">
                                            <label for="profileImage" class="file-upload-label">
                                                <i class="bi bi-camera me-1"></i>Change Photo
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">JPG, PNG or GIF. Max 2MB.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Recent Activity</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $activity_query = "SELECT 
                            c.title as course_title,
                            e.enrolled_at,
                            'enrolled' as type
                            FROM enrollments e
                            JOIN courses c ON e.course_id = c.id
                            WHERE e.user_id = $user_id
                            UNION
                            SELECT 
                            s.title as course_title,
                            sr.registered_at as enrolled_at,
                            'registered' as type
                            FROM schedule_registrations sr
                            JOIN schedule s ON sr.schedule_id = s.id
                            WHERE sr.user_id = $user_id
                            ORDER BY enrolled_at DESC
                            LIMIT 5";
                        
                        $activity_result = mysqli_query($conn, $activity_query);
                        
                        if(mysqli_num_rows($activity_result) > 0):
                        ?>
                            <div class="list-group list-group-flush">
                                <?php while($activity = mysqli_fetch_assoc($activity_result)): ?>
                                <div class="list-group-item border-0 px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php if($activity['type'] == 'enrolled'): ?>
                                                    <i class="bi bi-book text-success me-2"></i>
                                                    Enrolled in <?php echo $activity['course_title']; ?>
                                                <?php else: ?>
                                                    <i class="bi bi-calendar-check text-info me-2"></i>
                                                    Registered for <?php echo $activity['course_title']; ?>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('M d, Y h:i A', strtotime($activity['enrolled_at'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $activity['type'] == 'enrolled' ? 'success' : 'info'; ?>">
                                            <?php echo ucfirst($activity['type']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-activity fs-1 text-muted mb-3"></i>
                                <p class="text-muted">No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile image preview
        document.getElementById('profileImage').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    if(preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace div with img
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'profile-img';
                        img.id = 'profilePreview';
                        preview.parentNode.replaceChild(img, preview);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
        
        // File size validation
        document.getElementById('profileImage').addEventListener('change', function() {
            const file = this.files[0];
            if(file && file.size > 2 * 1024 * 1024) { // 2MB
                alert('File size must be less than 2MB');
                this.value = '';
            }
        });
    </script>
</body>
</html>