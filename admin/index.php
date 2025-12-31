<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/dbconnect.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if tables exist before querying
function checkTableExists($conn, $table_name) {
    $check = $conn->query("SHOW TABLES LIKE '$table_name'");
    return $check->num_rows > 0;
}

// Initialize stats with defaults
$stats = [
    'total_users' => 0,
    'total_students' => 0,
    'total_instructors' => 0,
    'total_courses' => 0,
    'published_courses' => 0,
    'total_enrollments' => 0,
    'upcoming_classes' => 0,
    'total_revenue' => 0
];

// Build stats queries dynamically based on table existence
if(checkTableExists($conn, 'users')) {
    $stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'];
    $stats['total_instructors'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'instructor'")->fetch_assoc()['count'];
}

if(checkTableExists($conn, 'courses')) {
    $total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc();
    $stats['total_courses'] = $total_courses['count'];
    
    // Check if is_published column exists
    $col_check = $conn->query("SHOW COLUMNS FROM courses LIKE 'is_published'");
    if($col_check->num_rows > 0) {
        $published = $conn->query("SELECT COUNT(*) as count FROM courses WHERE is_published = 1")->fetch_assoc();
        $stats['published_courses'] = $published['count'];
    } else {
        $stats['published_courses'] = $stats['total_courses'];
    }
}

if(checkTableExists($conn, 'enrollments')) {
    $enrollments = $conn->query("SELECT COUNT(*) as count FROM enrollments")->fetch_assoc();
    $stats['total_enrollments'] = $enrollments['count'];
    
    // Calculate revenue if price column exists in courses
    if(checkTableExists($conn, 'courses')) {
        $col_check = $conn->query("SHOW COLUMNS FROM courses LIKE 'price'");
        if($col_check->num_rows > 0) {
            $revenue_result = $conn->query("
                SELECT COALESCE(SUM(c.price), 0) as total_revenue 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.id
            ");
            if($revenue_result) {
                $revenue = $revenue_result->fetch_assoc();
                $stats['total_revenue'] = $revenue['total_revenue'] ?? 0;
            }
        }
    }
}

if(checkTableExists($conn, 'schedule')) {
    $col_check = $conn->query("SHOW COLUMNS FROM schedule LIKE 'start_time'");
    if($col_check->num_rows > 0) {
        $upcoming = $conn->query("SELECT COUNT(*) as count FROM schedule WHERE start_time > NOW()")->fetch_assoc();
        $stats['upcoming_classes'] = $upcoming['count'];
    }
}

// Get recent users
$recent_users = [];
if(checkTableExists($conn, 'users')) {
    $recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
    $recent_users_result = $conn->query($recent_users_query);
    if($recent_users_result) {
        $recent_users = $recent_users_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get recent courses
$recent_courses = [];
if(checkTableExists($conn, 'courses') && checkTableExists($conn, 'users')) {
    $recent_courses_query = "SELECT c.*, u.full_name as instructor_name 
                             FROM courses c 
                             LEFT JOIN users u ON c.instructor_id = u.id 
                             ORDER BY c.created_at DESC LIMIT 5";
    $recent_courses_result = $conn->query($recent_courses_query);
    if($recent_courses_result) {
        $recent_courses = $recent_courses_result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: white;
            transform: translateX(5px);
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .stat-card-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card-6 { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        
        .stat-card h3 {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .stat-card i {
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }
        
        .badge {
            padding: 5px 10px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 admin-sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white mb-4">
                        <i class="bi bi-shield-lock me-2"></i>Admin Panel
                    </h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="bi bi-people me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_courses.php">
                                <i class="bi bi-book me-2"></i> Manage Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_modules.php">
                                <i class="bi bi-collection me-2"></i> Course Modules
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_schedule.php">
                                <i class="bi bi-calendar-event me-2"></i> Live Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="enrollments.php">
                                <i class="bi bi-clipboard-check me-2"></i> Enrollments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="messages.php">
                                <i class="bi bi-envelope me-2"></i> Messages
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-warning" href="../dashboard.php">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Admin Dashboard</h2>
                        <p class="text-muted mb-0">Manage your yoga platform from here</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-clock me-1"></i>
                            <?php echo date('h:i A'); ?>
                        </small>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card stat-card-1 position-relative">
                            <h3><?php echo $stats['total_users']; ?></h3>
                            <p>Total Users</p>
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card stat-card-2 position-relative">
                            <h3><?php echo $stats['total_courses']; ?></h3>
                            <p>Total Courses</p>
                            <i class="bi bi-book"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card stat-card-3 position-relative">
                            <h3><?php echo $stats['total_enrollments']; ?></h3>
                            <p>Total Enrollments</p>
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card stat-card-4 position-relative">
                            <h3>₹<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p>Total Revenue</p>
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Platform Overview</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Students</small>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-3">
                                            <div class="progress-bar bg-success" style="width: <?php echo $stats['total_users'] > 0 ? ($stats['total_students']/$stats['total_users'])*100 : 0; ?>%"></div>
                                        </div>
                                        <span class="fw-bold"><?php echo $stats['total_students']; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Instructors</small>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-3">
                                            <div class="progress-bar bg-info" style="width: <?php echo $stats['total_users'] > 0 ? ($stats['total_instructors']/$stats['total_users'])*100 : 0; ?>%"></div>
                                        </div>
                                        <span class="fw-bold"><?php echo $stats['total_instructors']; ?></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Published Courses</small>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-3">
                                            <div class="progress-bar bg-warning" style="width: <?php echo $stats['total_courses'] > 0 ? ($stats['published_courses']/$stats['total_courses'])*100 : 0; ?>%"></div>
                                        </div>
                                        <span class="fw-bold"><?php echo $stats['published_courses']; ?></span>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Upcoming Classes</small>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-3">
                                            <div class="progress-bar bg-danger" style="width: 100%;"></div>
                                        </div>
                                        <span class="fw-bold"><?php echo $stats['upcoming_classes']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users & Courses -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Users</h5>
                                <a href="manage_users.php" class="btn btn-sm btn-outline-success">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($recent_users)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Type</th>
                                                    <th>Joined</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($recent_users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if(!empty($user['profile_pic'])): ?>
                                                                <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                                                                     class="rounded-circle me-2" width="30" height="30" 
                                                                     style="object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                                                                     style="width: 30px; height: 30px;">
                                                                    <i class="bi bi-person text-white"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <span><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $user['user_type'] == 'admin' ? 'danger' : 
                                                                 ($user['user_type'] == 'instructor' ? 'warning' : 'success');
                                                        ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d', strtotime($user['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-people fs-1 text-muted mb-3"></i>
                                        <p class="text-muted">No users found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Courses</h5>
                                <a href="manage_courses.php" class="btn btn-sm btn-outline-success">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($recent_courses)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Instructor</th>
                                                    <th>Price</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($recent_courses as $course): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars(substr($course['title'] ?? '', 0, 30)); ?>...</strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($course['category'] ?? ''); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></td>
                                                    <td>
                                                        <?php if(($course['price'] ?? 0) > 0): ?>
                                                            <span class="fw-bold">₹<?php echo number_format($course['price'], 2); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Free</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($course['is_active'] ?? 1): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-book fs-1 text-muted mb-3"></i>
                                        <p class="text-muted">No courses found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Platform Overview Chart
        const ctx = document.getElementById('userChart').getContext('2d');
        const userChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 8, 15, 22, 18],
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    borderWidth: 2
                }, {
                    label: 'New Courses',
                    data: [3, 5, 2, 6, 4, 7],
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        },
                        ticks: {
                            padding: 10
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            padding: 10
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>