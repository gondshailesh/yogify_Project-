<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get enrolled courses with progress
$courses_query = "SELECT c.*, e.progress_percent, e.completed, e.enrolled_at 
                  FROM enrollments e 
                  JOIN courses c ON e.course_id = c.id 
                  WHERE e.user_id = $user_id 
                  ORDER BY e.enrolled_at DESC";
$courses_result = mysqli_query($conn, $courses_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <!-- Navigation -->
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-3">My Courses</h1>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-all-tab" data-bs-toggle="tab" 
                                data-bs-target="#nav-all" type="button">
                            All Courses
                        </button>
                        <button class="nav-link" id="nav-inprogress-tab" data-bs-toggle="tab" 
                                data-bs-target="#nav-inprogress" type="button">
                            In Progress
                        </button>
                        <button class="nav-link" id="nav-completed-tab" data-bs-toggle="tab" 
                                data-bs-target="#nav-completed" type="button">
                            Completed
                        </button>
                    </div>
                </nav>
            </div>
        </div>
        
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-all">
                <div class="row">
                    <?php while($course = mysqli_fetch_assoc($courses_result)): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo $course['thumbnail'] ?: 'images/course-default.jpg'; ?>" 
                                 class="card-img-top" alt="<?php echo $course['title']; ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-<?php echo $course['completed'] ? 'success' : 'warning'; ?>">
                                        <?php echo $course['completed'] ? 'Completed' : 'In Progress'; ?>
                                    </span>
                                    <span class="badge bg-info"><?php echo ucfirst($course['level']); ?></span>
                                </div>
                                <h5 class="card-title"><?php echo $course['title']; ?></h5>
                                <p class="card-text text-muted"><?php echo substr($course['description'], 0, 100) . '...'; ?></p>
                                
                                <!-- Progress Bar -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Progress</small>
                                        <small><?php echo $course['progress_percent']; ?>%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo $course['progress_percent']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-grid gap-2">
                                    <?php if($course['progress_percent'] > 0 && $course['progress_percent'] < 100): ?>
                                        <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                           class="btn btn-success">
                                            <i class="bi bi-play-circle me-1"></i>Continue Learning
                                        </a>
                                    <?php elseif($course['progress_percent'] == 0): ?>
                                        <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                           class="btn btn-outline-success">
                                            <i class="bi bi-play-circle me-1"></i>Start Learning
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-check-circle me-1"></i>Course Completed
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>