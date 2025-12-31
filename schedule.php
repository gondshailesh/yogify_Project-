<?php
session_start();
include_once 'includes/dbconnect.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

// Get upcoming live classes
$upcoming_query = "SELECT s.*, u.full_name as instructor_name, 
                  (SELECT COUNT(*) FROM schedule_registrations WHERE schedule_id = s.id) as registered_count
                  FROM schedule s 
                  JOIN users u ON s.instructor_id = u.id 
                  WHERE s.start_time > NOW() 
                  ORDER BY s.start_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_query);

// Get past classes
$past_query = "SELECT s.*, u.full_name as instructor_name 
               FROM schedule s 
               JOIN users u ON s.instructor_id = u.id 
               WHERE s.start_time < NOW() 
               ORDER BY s.start_time DESC 
               LIMIT 10";
$past_result = mysqli_query($conn, $past_query);

// Handle class registration
if($is_logged_in && isset($_GET['register']) && isset($_GET['schedule_id'])) {
    $schedule_id = intval($_GET['schedule_id']);
    
    // Check if already registered
    $check_query = "SELECT * FROM schedule_registrations 
                    WHERE user_id = $user_id AND schedule_id = $schedule_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) == 0) {
        $register_query = "INSERT INTO schedule_registrations (user_id, schedule_id) 
                           VALUES ($user_id, $schedule_id)";
        if(mysqli_query($conn, $register_query)) {
            $_SESSION['message'] = "Successfully registered for the class!";
            $_SESSION['msg_type'] = "success";
        }
    } else {
        $_SESSION['message'] = "You are already registered for this class!";
        $_SESSION['msg_type'] = "warning";
    }
    header("Location: schedule.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Classes - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .schedule-hero {
            background: linear-gradient(rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.9));
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        .class-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-date {
            background: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .class-date .day {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .class-date .month {
            font-size: 1rem;
            text-transform: uppercase;
        }
        .class-time {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }
        .zoom-link {
            background: #2D8CFF;
            color: white;
            padding: 10px;
            text-align: center;
            margin-top: 15px;
            border-radius: 5px;
            text-decoration: none;
            display: block;
        }
        .zoom-link:hover {
            background: #1a73e8;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="schedule-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">Live Yoga Classes</h1>
                    <p class="lead mb-4">Join our live sessions with expert instructors. Real-time guidance, real community.</p>
                    <?php if($is_logged_in): ?>
                        <a href="#upcoming" class="btn btn-light btn-lg">View Schedule</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-light btn-lg">Sign Up to Join</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Classes -->
    <div class="container py-5" id="upcoming">
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Upcoming Classes</h2>
                <p class="lead">Join these live sessions</p>
            </div>
        </div>
        
        <div class="row">
            <?php if(mysqli_num_rows($upcoming_result) > 0): ?>
                <?php while($class = mysqli_fetch_assoc($upcoming_result)): 
                    $class_date = date('Y-m-d', strtotime($class['start_time']));
                    $class_day = date('d', strtotime($class['start_time']));
                    $class_month = date('M', strtotime($class['start_time']));
                    $start_time = date('h:i A', strtotime($class['start_time']));
                    $end_time = date('h:i A', strtotime($class['end_time']));
                    
                    // Check if user is registered
                    $is_registered = false;
                    if($is_logged_in) {
                        $check_reg_query = "SELECT * FROM schedule_registrations 
                                           WHERE user_id = $user_id AND schedule_id = {$class['id']}";
                        $check_reg_result = mysqli_query($conn, $check_reg_query);
                        $is_registered = mysqli_num_rows($check_reg_result) > 0;
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="class-card">
                        <div class="class-date">
                            <span class="day"><?php echo $class_day; ?></span>
                            <span class="month"><?php echo $class_month; ?></span>
                        </div>
                        <div class="class-time">
                            <i class="bi bi-clock me-1"></i>
                            <?php echo $start_time; ?> - <?php echo $end_time; ?>
                        </div>
                        <div class="p-4">
                            <h4><?php echo $class['title']; ?></h4>
                            <p class="text-muted">
                                <i class="bi bi-person me-1"></i>
                                <?php echo $class['instructor_name']; ?>
                            </p>
                            <p><?php echo substr($class['description'], 0, 100) . '...'; ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <i class="bi bi-people me-1"></i>
                                    <small><?php echo $class['registered_count']; ?> registered</small>
                                </div>
                                <div>
                                    <?php if($is_logged_in): ?>
                                        <?php if($is_registered): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Registered
                                            </span>
                                        <?php else: ?>
                                            <?php if($class['registered_count'] < $class['max_participants']): ?>
                                                <a href="schedule.php?register=1&schedule_id=<?php echo $class['id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="bi bi-calendar-plus me-1"></i>Register
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Full</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-sm btn-outline-success">
                                            Login to Register
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if($is_registered && !empty($class['zoom_link'])): ?>
                                <a href="<?php echo $class['zoom_link']; ?>" 
                                   target="_blank" 
                                   class="zoom-link mt-3">
                                    <i class="bi bi-camera-video me-1"></i>Join Zoom Class
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                    <h4>No upcoming classes scheduled</h4>
                    <p class="text-muted">Check back soon for new live sessions!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Past Classes -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold mb-3">Recent Classes</h2>
                    <p class="lead">Missed a class? Recordings available for enrolled students</p>
                </div>
            </div>
            
            <div class="row">
                <?php if(mysqli_num_rows($past_result) > 0): ?>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Class</th>
                                        <th>Instructor</th>
                                        <th>Recording</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($class = mysqli_fetch_assoc($past_result)): 
                                        $class_date = date('M d, Y', strtotime($class['start_time']));
                                    ?>
                                    <tr>
                                        <td><?php echo $class_date; ?></td>
                                        <td><?php echo $class['title']; ?></td>
                                        <td><?php echo $class['instructor_name']; ?></td>
                                        <td>
                                            <?php if($is_logged_in): ?>
                                                <a href="#" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-play-circle me-1"></i>Watch
                                                </a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-sm btn-outline-secondary">
                                                    Login to Access
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>