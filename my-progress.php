<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's enrolled courses with progress
$progress_query = "SELECT 
    c.*,
    e.progress_percent,
    e.completed as course_completed,
    e.enrolled_at,
    u.full_name as instructor_name,
    (SELECT COUNT(*) FROM modules WHERE course_id = c.id) as total_modules,
    (SELECT COUNT(*) FROM user_progress up 
     JOIN modules m ON up.module_id = m.id 
     WHERE up.user_id = $user_id AND up.completed = 1 AND m.course_id = c.id) as completed_modules
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = $user_id
    ORDER BY e.enrolled_at DESC";
$progress_result = mysqli_query($conn, $progress_query);

// Calculate overall stats
$stats_query = "SELECT 
    COUNT(*) as total_courses,
    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_courses,
    AVG(progress_percent) as avg_progress
    FROM enrollments 
    WHERE user_id = $user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .progress-container {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4CAF50;
            display: block;
            line-height: 1;
        }
        .course-progress-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-left: 5px solid #4CAF50;
            transition: all 0.3s ease;
        }
        .course-progress-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .progress-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .badge-completed {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: white;
        }
        .badge-inprogress {
            background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
            color: white;
        }
        .nav-pills .nav-link.active {
            background-color: #4CAF50;
        }
        .module-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .module-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        .module-item:hover {
            background: #f8f9fa;
        }
        .module-item.completed {
            background: #f0f9f0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="progress-container mt-5 pt-4">
        <div class="container py-5">
            <!-- Header -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5 fw-bold mb-2">My Learning Progress</h1>
                            <p class="lead text-muted">Track your yoga journey and achievements</p>
                        </div>
                        <div>
                            <a href="dashboard.php" class="btn btn-outline-success">
                                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="stats-card">
                        <span class="stats-number"><?php echo $stats['total_courses']; ?></span>
                        <p>Enrolled Courses</p>
                        <i class="bi bi-book fs-1 text-muted"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <span class="stats-number"><?php echo $stats['completed_courses']; ?></span>
                        <p>Completed Courses</p>
                        <i class="bi bi-check-circle fs-1 text-muted"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <span class="stats-number"><?php echo round($stats['avg_progress']); ?>%</span>
                        <p>Average Progress</p>
                        <i class="bi bi-graph-up fs-1 text-muted"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <span class="stats-number">
                            <?php 
                            // Calculate learning hours (demo calculation)
                            $hours = $stats['total_courses'] * 5; // 5 hours per course average
                            echo $hours;
                            ?>
                        </span>
                        <p>Learning Hours</p>
                        <i class="bi bi-clock fs-1 text-muted"></i>
                    </div>
                </div>
            </div>
            
            <!-- Progress Overview -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Overall Progress</h4>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 me-4">
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $stats['avg_progress']; ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="fw-bold fs-4"><?php echo round($stats['avg_progress']); ?>%</div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success rounded-circle me-2" style="width: 15px; height: 15px;"></div>
                                        <span>Completed: <?php echo $stats['completed_courses']; ?> courses</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning rounded-circle me-2" style="width: 15px; height: 15px;"></div>
                                        <span>In Progress: <?php echo $stats['total_courses'] - $stats['completed_courses']; ?> courses</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Progress Tabs -->
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-pills mb-4" id="progressTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" 
                                    data-bs-target="#all" type="button">
                                All Courses (<?php echo mysqli_num_rows($progress_result); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" 
                                    data-bs-target="#completed" type="button">
                                <i class="bi bi-check-circle me-1"></i>Completed
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inprogress-tab" data-bs-toggle="tab" 
                                    data-bs-target="#inprogress" type="button">
                                <i class="bi bi-clock me-1"></i>In Progress
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="progressTabContent">
                        <!-- All Courses Tab -->
                        <div class="tab-pane fade show active" id="all">
                            <div class="row">
                                <?php 
                                mysqli_data_seek($progress_result, 0); // Reset pointer
                                if(mysqli_num_rows($progress_result) > 0):
                                    while($course = mysqli_fetch_assoc($progress_result)): 
                                        $completion_rate = $course['total_modules'] > 0 ? 
                                            round(($course['completed_modules'] / $course['total_modules']) * 100) : 0;
                                ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="course-progress-card">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="mb-2"><?php echo $course['title']; ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-person me-1"></i><?php echo $course['instructor_name']; ?>
                                                </p>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small>Progress</small>
                                                        <small><?php echo $completion_rate; ?>%</small>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-success" 
                                                             style="width: <?php echo $completion_rate; ?>%"></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge <?php echo $course['course_completed'] ? 'badge-completed' : 'badge-inprogress'; ?> me-3">
                                                        <?php echo $course['course_completed'] ? 'Completed' : 'In Progress'; ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <i class="bi bi-modules me-1"></i>
                                                        <?php echo $course['completed_modules']; ?>/<?php echo $course['total_modules']; ?> modules
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="progress-circle mb-3 mx-auto">
                                                    <?php echo $completion_rate; ?>%
                                                </div>
                                                <?php if($completion_rate < 100): ?>
                                                    <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                                       class="btn btn-success btn-sm w-100">
                                                        <i class="bi bi-play-circle me-1"></i>Continue
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-success btn-sm w-100" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>Completed
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-graph-up fs-1 text-muted mb-3"></i>
                                        <h4>No courses enrolled yet</h4>
                                        <p class="text-muted mb-4">Start your yoga journey by enrolling in a course</p>
                                        <a href="courses.php" class="btn btn-success">
                                            <i class="bi bi-book me-1"></i>Browse Courses
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Completed Courses Tab -->
                        <div class="tab-pane fade" id="completed">
                            <div class="row">
                                <?php 
                                mysqli_data_seek($progress_result, 0);
                                $has_completed = false;
                                while($course = mysqli_fetch_assoc($progress_result)): 
                                    if($course['course_completed'] == 1):
                                        $has_completed = true;
                                        $completion_rate = 100;
                                ?>
                                <div class="col-lg-4 mb-4">
                                    <div class="course-progress-card">
                                        <div class="text-center">
                                            <div class="progress-circle mb-3 mx-auto">
                                                100%
                                            </div>
                                            <h6 class="mb-2"><?php echo $course['title']; ?></h6>
                                            <p class="text-muted mb-3">
                                                <small><?php echo $course['instructor_name']; ?></small>
                                            </p>
                                            <span class="badge badge-completed mb-3">Completed</span>
                                            <div class="mt-3">
                                                <small class="text-muted d-block">
                                                    Completed on: <?php echo date('M d, Y'); ?>
                                                </small>
                                                <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-success btn-sm mt-2">
                                                    <i class="bi bi-eye me-1"></i>Review Course
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                                
                                <?php if(!$has_completed): ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-trophy fs-1 text-muted mb-3"></i>
                                        <h4>No completed courses yet</h4>
                                        <p class="text-muted">Complete a course to see it here</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- In Progress Courses Tab -->
                        <div class="tab-pane fade" id="inprogress">
                            <div class="row">
                                <?php 
                                mysqli_data_seek($progress_result, 0);
                                $has_inprogress = false;
                                while($course = mysqli_fetch_assoc($progress_result)): 
                                    if($course['course_completed'] == 0):
                                        $has_inprogress = true;
                                        $completion_rate = $course['total_modules'] > 0 ? 
                                            round(($course['completed_modules'] / $course['total_modules']) * 100) : 0;
                                ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="course-progress-card">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="progress-circle" style="width: 60px; height: 60px;">
                                                    <?php echo $completion_rate; ?>%
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo $course['title']; ?></h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php echo $course['completed_modules']; ?>/<?php echo $course['total_modules']; ?> modules
                                                    </small>
                                                    <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                                       class="btn btn-success btn-sm">
                                                        Continue
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; endwhile; ?>
                                
                                <?php if(!$has_inprogress): ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-clock-history fs-1 text-muted mb-3"></i>
                                        <h4>All courses completed!</h4>
                                        <p class="text-muted mb-4">Great job! You've completed all your courses</p>
                                        <a href="courses.php" class="btn btn-success">
                                            <i class="bi bi-plus-circle me-1"></i>Find New Courses
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row mt-5">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="module-list">
                                <?php 
                                // Get recent module completions
                                $activity_query = "SELECT 
                                    m.title as module_title,
                                    c.title as course_title,
                                    up.completed_at,
                                    c.id as course_id
                                    FROM user_progress up
                                    JOIN modules m ON up.module_id = m.id
                                    JOIN courses c ON m.course_id = c.id
                                    WHERE up.user_id = $user_id AND up.completed = 1
                                    ORDER BY up.completed_at DESC
                                    LIMIT 10";
                                
                                $activity_result = mysqli_query($conn, $activity_query);
                                
                                if(mysqli_num_rows($activity_result) > 0):
                                    while($activity = mysqli_fetch_assoc($activity_result)):
                                ?>
                                <div class="module-item completed">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo $activity['module_title']; ?></h6>
                                            <small class="text-muted"><?php echo $activity['course_title']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?php echo date('M d', strtotime($activity['completed_at'])); ?>
                                            </small>
                                            <br>
                                            <a href="course-player.php?course_id=<?php echo $activity['course_id']; ?>" 
                                               class="btn btn-sm btn-outline-success mt-1">
                                                Review
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-activity fs-1 text-muted mb-3"></i>
                                    <p class="text-muted">No recent activity yet</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Achievement Stats -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Achievements</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Calculate achievements
                            $total_hours = $stats['total_courses'] * 5; // Demo calculation
                            $streak_days = 7; // Demo streak
                            ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-trophy text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $stats['completed_courses']; ?> Courses Mastered</h6>
                                    <small class="text-muted">Yoga Journey</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-clock text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $total_hours; ?> Learning Hours</h6>
                                    <small class="text-muted">Time invested</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-fire text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $streak_days; ?> Day Streak</h6>
                                    <small class="text-muted">Consistent practice</small>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="certificate.php" class="btn btn-outline-success">
                                    <i class="bi bi-award me-1"></i>View Certificates
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tabs
        const triggerTabList = document.querySelectorAll('#progressTab button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
            });
        });
        
        // Filter courses based on progress
        function filterCourses(status) {
            const cards = document.querySelectorAll('.course-progress-card');
            cards.forEach(card => {
                const badge = card.querySelector('.badge');
                if(status === 'all' || badge.textContent.includes(status)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Update progress animation
        document.querySelectorAll('.progress-bar').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    </script>
</body>
</html>