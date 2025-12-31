<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$user_name = $_SESSION['user_name'];

// Get user stats based on user type
if($user_type == 'student') {
    // Student Dashboard
    $enrolled_courses = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM enrollments WHERE user_id = $user_id");
    $completed_courses = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM enrollments WHERE user_id = $user_id AND completed = 1");
    $upcoming_classes = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM schedule_registrations sr 
         JOIN schedule s ON sr.schedule_id = s.id 
         WHERE sr.user_id = $user_id AND s.start_time > NOW()");
} elseif($user_type == 'instructor') {
    // Instructor Dashboard
    $my_courses = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM courses WHERE instructor_id = $user_id");
    $total_students = mysqli_query($conn, 
        "SELECT COUNT(DISTINCT e.user_id) as count 
         FROM enrollments e 
         JOIN courses c ON e.course_id = c.id 
         WHERE c.instructor_id = $user_id");
    $upcoming_sessions = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM schedule WHERE instructor_id = $user_id AND start_time > NOW()");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        .stat-card-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">Yogify Dashboard</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <?php if($user_type == 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="my-courses.php">
                                    <i class="bi bi-book me-2"></i> My Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my-progress.php">
                                    <i class="bi bi-graph-up me-2"></i> My Progress
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="live-classes.php">
                                    <i class="bi bi-camera-video me-2"></i> Live Classes
                                </a>
                            </li>
                        <?php elseif($user_type == 'instructor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="manage-courses.php">
                                    <i class="bi bi-book me-2"></i> My Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="create-course.php">
                                    <i class="bi bi-plus-circle me-2"></i> Create Course
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="schedule-class.php">
                                    <i class="bi bi-calendar-plus me-2"></i> Schedule Class
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="students.php">
                                    <i class="bi bi-people me-2"></i> Students
                                </a>
                            </li>
                        <?php elseif($user_type == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/users.php">
                                    <i class="bi bi-people me-2"></i> Manage Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/courses.php">
                                    <i class="bi bi-book me-2"></i> All Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/settings.php">
                                    <i class="bi bi-gear me-2"></i> Settings
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome, <?php echo $user_name; ?>!</h2>
                    <span class="badge bg-success"><?php echo ucfirst($user_type); ?></span>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <?php if($user_type == 'student'): ?>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-1">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($enrolled_courses);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>Enrolled Courses</p>
                                <i class="bi bi-book fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-2">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($completed_courses);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>Completed Courses</p>
                                <i class="bi bi-check-circle fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-3">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($upcoming_classes);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>Upcoming Classes</p>
                                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                            </div>
                        </div>
                        
                    <?php elseif($user_type == 'instructor'): ?>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-1">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($my_courses);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>My Courses</p>
                                <i class="bi bi-book fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-2">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($total_students);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>Total Students</p>
                                <i class="bi bi-people fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card stat-card-3">
                                <h3>
                                    <?php 
                                    $row = mysqli_fetch_assoc($upcoming_sessions);
                                    echo $row['count'];
                                    ?>
                                </h3>
                                <p>Upcoming Sessions</p>
                                <i class="bi bi-camera-video fs-1 opacity-50"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if($user_type == 'student'): ?>
                            <ul class="list-group list-group-flush">
                                <?php
                                // Get recent enrollments
                                $recent_activity = mysqli_query($conn, 
                                    "SELECT c.title, e.enrolled_at 
                                     FROM enrollments e 
                                     JOIN courses c ON e.course_id = c.id 
                                     WHERE e.user_id = $user_id 
                                     ORDER BY e.enrolled_at DESC 
                                     LIMIT 5");
                                
                                while($activity = mysqli_fetch_assoc($recent_activity)):
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Enrolled in <strong><?php echo $activity['title']; ?></strong>
                                    <span class="text-muted"><?php echo date('M d, Y', strtotime($activity['enrolled_at'])); ?></span>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>