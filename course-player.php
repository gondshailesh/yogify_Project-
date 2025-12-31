<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if module_id or course_id is provided
if(isset($_GET['module_id'])) {
    $module_id = intval($_GET['module_id']);
    
    // Get module details and check enrollment
    $module_query = "SELECT m.*, c.id as course_id, c.title as course_title 
                     FROM modules m 
                     JOIN courses c ON m.course_id = c.id 
                     WHERE m.id = $module_id";
    $module_result = mysqli_query($conn, $module_query);
    
    if(mysqli_num_rows($module_result) == 0) {
        header("Location: dashboard.php");
        exit();
    }
    
    $module = mysqli_fetch_assoc($module_result); // FIXED: was $module_query
    $course_id = $module['course_id'];
    
    // Check enrollment
    $enroll_check = "SELECT * FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
    $enroll_result = mysqli_query($conn, $enroll_check);
    
    if(mysqli_num_rows($enroll_result) == 0) {
        header("Location: course-details.php?id=$course_id");
        exit();
    }
    
} elseif(isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    
    // Check enrollment
    $enroll_check = "SELECT * FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
    $enroll_result = mysqli_query($conn, $enroll_check);
    
    if(mysqli_num_rows($enroll_result) == 0) {
        header("Location: course-details.php?id=$course_id");
        exit();
    }
    
    // Get first module
    $first_module_query = "SELECT * FROM modules WHERE course_id = $course_id ORDER BY module_order LIMIT 1";
    $first_module_result = mysqli_query($conn, $first_module_query);
    
    if(mysqli_num_rows($first_module_result) > 0) {
        $module = mysqli_fetch_assoc($first_module_result);
        $module_id = $module['id'];
    } else {
        header("Location: course-details.php?id=$course_id");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}

// Get all modules for sidebar
$modules_query = "SELECT * FROM modules WHERE course_id = $course_id ORDER BY module_order";
$modules_result = mysqli_query($conn, $modules_query);
$total_modules = mysqli_num_rows($modules_result);

// Get current module position
$current_module_order = $module ? $module['module_order'] : 0;

// Mark module as completed
if(isset($_POST['mark_complete'])) {
    if($module_id > 0) {
        // Check if progress record exists
        $check_progress = "SELECT * FROM user_progress WHERE user_id = $user_id AND module_id = $module_id";
        $check_result = mysqli_query($conn, $check_progress);
        
        if(mysqli_num_rows($check_result) > 0) {
            $update_query = "UPDATE user_progress SET completed = 1, completed_at = NOW() 
                            WHERE user_id = $user_id AND module_id = $module_id";
        } else {
            $update_query = "INSERT INTO user_progress (user_id, module_id, completed, completed_at) 
                            VALUES ($user_id, $module_id, 1, NOW())";
        }
        
        if(mysqli_query($conn, $update_query)) {
            // Update overall progress
            $total_modules_query = "SELECT COUNT(*) as total FROM modules WHERE course_id = $course_id";
            $completed_modules_query = "SELECT COUNT(*) as completed FROM user_progress up 
                                        JOIN modules m ON up.module_id = m.id 
                                        WHERE up.user_id = $user_id AND up.completed = 1 AND m.course_id = $course_id";
            
            $total_result = mysqli_query($conn, $total_modules_query);
            $completed_result = mysqli_query($conn, $completed_modules_query);
            
            $total = mysqli_fetch_assoc($total_result)['total'];
            $completed = mysqli_fetch_assoc($completed_result)['completed'];
            
            $progress_percent = $total > 0 ? round(($completed / $total) * 100) : 0;
            
            // Update enrollment progress
            $update_enrollment = "UPDATE enrollments SET progress_percent = $progress_percent 
                                 WHERE user_id = $user_id AND course_id = $course_id";
            mysqli_query($conn, $update_enrollment);
            
            // Check if course is completed
            if($progress_percent >= 100) {
                $complete_course = "UPDATE enrollments SET completed = 1 
                                   WHERE user_id = $user_id AND course_id = $course_id";
                mysqli_query($conn, $complete_course);
            }
            
            $_SESSION['message'] = "Module marked as completed!";
            $_SESSION['msg_type'] = "success";
            
            // Redirect to next module if exists
            $next_module_query = "SELECT * FROM modules WHERE course_id = $course_id AND module_order > $current_module_order ORDER BY module_order LIMIT 1";
            $next_module_result = mysqli_query($conn, $next_module_query);
            
            if(mysqli_num_rows($next_module_result) > 0) {
                $next_module = mysqli_fetch_assoc($next_module_result);
                header("Location: course-player.php?module_id=" . $next_module['id']);
                exit();
            } else {
                // Stay on current module
                header("Location: course-player.php?module_id=$module_id");
                exit();
            }
        }
    }
}

// Get progress status
$progress_query = "SELECT completed FROM user_progress WHERE user_id = $user_id AND module_id = $module_id";
$progress_result = mysqli_query($conn, $progress_query);
$progress = $progress_result ? mysqli_fetch_assoc($progress_result) : null;
$is_completed = $progress ? $progress['completed'] : false;

// Get course details for navigation
$course_query = "SELECT * FROM courses WHERE id = $course_id";
$course_result = mysqli_query($conn, $course_query);
$course = mysqli_fetch_assoc($course_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $module['title'] ?? 'Course Player'; ?> - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .player-container {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .video-container {
            background: #000;
            height: 70vh;
        }
        .module-sidebar {
            height: 100vh;
            overflow-y: auto;
            background: white;
            border-left: 1px solid #dee2e6;
        }
        .module-item.active {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .nav-arrows {
            position: absolute;
            top: 50%;
            width: 100%;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 10;
        }
        .nav-arrow {
            background: rgba(0,0,0,0.5);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        .nav-arrow:hover {
            background: rgba(76, 175, 80, 0.8);
            transform: scale(1.1);
        }
        .module-content {
            max-height: 70vh;
            overflow-y: auto;
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

    <div class="container-fluid player-container mt-5 pt-4">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-9 col-md-8 p-0">
                <!-- Video Player -->
                <div class="video-container position-relative">
                    <?php if(!empty($module['video_url'])): ?>
                        <!-- Video Player -->
                        <div class="ratio ratio-16x9 h-100">
                            <?php 
                            // Check video type
                            $video_url = $module['video_url'];
                            if(strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false): 
                                // Extract YouTube ID
                                $video_id = '';
                                if(preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
                                    $video_id = $matches[1];
                                }
                            ?>
                                <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen
                                        allow="autoplay">
                                </iframe>
                            <?php elseif(strpos($video_url, 'vimeo.com') !== false): ?>
                                <!-- Vimeo Embed -->
                                <?php 
                                preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)([0-9]+)/', $video_url, $matches);
                                $video_id = $matches[1] ?? '';
                                ?>
                                <iframe src="https://player.vimeo.com/video/<?php echo $video_id; ?>" 
                                        frameborder="0" 
                                        allow="autoplay; fullscreen; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            <?php else: ?>
                                <!-- Direct video file -->
                                <video id="yogaVideo" controls class="w-100 h-100">
                                    <source src="<?php echo $video_url; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                <script>
                                    // Auto-mark as complete when video ends
                                    document.getElementById('yogaVideo').addEventListener('ended', function() {
                                        document.querySelector('form[name="completeForm"] button').click();
                                    });
                                </script>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Navigation Arrows -->
                        <div class="nav-arrows">
                            <?php if($current_module_order > 1): ?>
                                <?php 
                                $prev_module_query = "SELECT * FROM modules WHERE course_id = $course_id AND module_order < $current_module_order ORDER BY module_order DESC LIMIT 1";
                                $prev_module_result = mysqli_query($conn, $prev_module_query);
                                if($prev_module = mysqli_fetch_assoc($prev_module_result)):
                                ?>
                                <a href="course-player.php?module_id=<?php echo $prev_module['id']; ?>" class="nav-arrow">
                                    <i class="bi bi-chevron-left fs-3"></i>
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if($current_module_order < $total_modules): ?>
                                <?php 
                                $next_module_query = "SELECT * FROM modules WHERE course_id = $course_id AND module_order > $current_module_order ORDER BY module_order LIMIT 1";
                                $next_module_result = mysqli_query($conn, $next_module_query);
                                if($next_module = mysqli_fetch_assoc($next_module_result)):
                                ?>
                                <a href="course-player.php?module_id=<?php echo $next_module['id']; ?>" class="nav-arrow">
                                    <i class="bi bi-chevron-right fs-3"></i>
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- No Video Placeholder -->
                        <div class="d-flex align-items-center justify-content-center h-100 text-white bg-dark">
                            <div class="text-center p-5">
                                <i class="bi bi-camera-video-off fs-1 mb-3"></i>
                                <h3>Video content coming soon</h3>
                                <p class="text-muted">This module is still being prepared. Check back later!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Module Details -->
                <div class="module-content p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                                    <li class="breadcrumb-item"><a href="course-details.php?id=<?php echo $course_id; ?>"><?php echo $course['title']; ?></a></li>
                                    <li class="breadcrumb-item active">Module <?php echo $current_module_order; ?></li>
                                </ol>
                            </nav>
                            <h1 class="mb-2"><?php echo $module['title']; ?></h1>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-secondary">
                                    <i class="bi bi-clock me-1"></i><?php echo $module['duration']; ?>
                                </span>
                                <span class="badge bg-<?php echo $is_completed ? 'success' : 'warning'; ?>">
                                    <i class="bi bi-<?php echo $is_completed ? 'check-circle' : 'circle'; ?> me-1"></i>
                                    <?php echo $is_completed ? 'Completed' : 'In Progress'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <?php if(!$is_completed): ?>
                                <form method="POST" name="completeForm" class="d-inline">
                                    <button type="submit" name="mark_complete" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle me-2"></i>Mark Complete
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline-success btn-lg" disabled>
                                    <i class="bi bi-check2-circle me-2"></i>Completed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Module Content -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-3">About this module</h4>
                            <div class="module-description">
                                <?php echo nl2br(htmlspecialchars($module['description'])); ?>
                            </div>
                            
                            <?php if(!empty($module['video_url'])): ?>
                            <div class="mt-4">
                                <h5><i class="bi bi-link-45deg me-2"></i>Video Link</h5>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $module['video_url']; ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Course Progress -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Your Progress</h5>
                            <?php 
                            // Get overall progress
                            $overall_progress_query = "SELECT progress_percent FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
                            $overall_progress_result = mysqli_query($conn, $overall_progress_query);
                            $overall_progress = mysqli_fetch_assoc($overall_progress_result);
                            $progress_percent = $overall_progress ? $overall_progress['progress_percent'] : 0;
                            ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1 me-3">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $progress_percent; ?>%">
                                        </div>
                                    </div>
                                </div>
                                <div class="fw-bold"><?php echo $progress_percent; ?>%</div>
                            </div>
                            <p class="text-muted mb-0">
                                <small>
                                    Module <?php echo $current_module_order; ?> of <?php echo $total_modules; ?> 
                                    (<?php echo round($progress_percent); ?>% complete)
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Sidebar -->
            <div class="col-lg-3 col-md-4 p-0 module-sidebar">
                <div class="p-3">
                    <!-- Course Info -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="progress-circle me-3">
                                <?php echo $progress_percent; ?>%
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo $course['title']; ?></h6>
                                <small class="text-muted"><?php echo $course['level']; ?> Level</small>
                            </div>
                        </div>
                        <a href="course-details.php?id=<?php echo $course_id; ?>" class="btn btn-outline-success btn-sm w-100">
                            <i class="bi bi-info-circle me-1"></i>Course Details
                        </a>
                    </div>
                    
                    <!-- Course Modules -->
                    <h6 class="mb-3">Course Content</h6>
                    <div class="list-group list-group-flush">
                        <?php 
                        mysqli_data_seek($modules_result, 0);
                        while($mod = mysqli_fetch_assoc($modules_result)): 
                            // Check if this module is completed
                            $mod_progress_query = "SELECT completed FROM user_progress WHERE user_id = $user_id AND module_id = {$mod['id']}";
                            $mod_progress_result = mysqli_query($conn, $mod_progress_query);
                            $mod_progress = $mod_progress_result ? mysqli_fetch_assoc($mod_progress_result) : null;
                            $mod_completed = $mod_progress ? $mod_progress['completed'] : false;
                            $is_active = ($mod['id'] == $module_id);
                        ?>
                        <a href="course-player.php?module_id=<?php echo $mod['id']; ?>" 
                           class="list-group-item list-group-item-action border-0 py-3 px-2 <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if($mod_completed): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 24px; height: 24px;">
                                            <small class="text-muted"><?php echo $mod['module_order']; ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 <?php echo $is_active ? 'text-success' : ''; ?>">
                                            <?php echo $mod['title']; ?>
                                        </h6>
                                        <?php if($mod_completed): ?>
                                            <i class="bi bi-check text-success"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?php echo $mod['duration']; ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Resources (Optional) -->
                    <div class="mt-4">
                        <h6 class="mb-3">Resources</h6>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                                <i class="bi bi-file-pdf me-2 text-danger"></i>Course Notes (PDF)
                            </a>
                            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                                <i class="bi bi-music-note-list me-2 text-primary"></i>Meditation Music
                            </a>
                            <a href="#" class="list-group-item list-group-item-action border-0 py-2">
                                <i class="bi bi-journal-text me-2 text-success"></i>Practice Journal
                            </a>
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
        // Copy to clipboard function
        function copyToClipboard(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            
            // Visual feedback
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
        
        // Auto-scroll to active module in sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const activeModule = document.querySelector('.module-item.active');
            if(activeModule) {
                activeModule.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Left arrow - previous module
            if(e.key === 'ArrowLeft') {
                const prevLink = document.querySelector('.nav-arrow[href*="module_id"]:first-child');
                if(prevLink) prevLink.click();
            }
            // Right arrow - next module
            if(e.key === 'ArrowRight') {
                const nextLink = document.querySelector('.nav-arrow[href*="module_id"]:last-child');
                if(nextLink) nextLink.click();
            }
            // Space - mark complete
            if(e.key === ' ' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const completeButton = document.querySelector('form[name="completeForm"] button');
                if(completeButton) completeButton.click();
            }
        });
    </script>
</body>
</html>