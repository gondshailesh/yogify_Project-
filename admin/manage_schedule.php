<?php
session_start();
include_once '../includes/dbconnect.php';

// Check if user is admin/instructor
if(!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'instructor')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle schedule operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_schedule'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
        $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
        $zoom_link = mysqli_real_escape_string($conn, $_POST['zoom_link']);
        $max_participants = intval($_POST['max_participants']);
        
        $insert_query = "INSERT INTO schedule (title, description, instructor_id, start_time, end_time, zoom_link, max_participants) 
                         VALUES ('$title', '$description', $user_id, '$start_time', '$end_time', '$zoom_link', $max_participants)";
        
        mysqli_query($conn, $insert_query);
        $_SESSION['message'] = "Class scheduled successfully!";
        $_SESSION['msg_type'] = "success";
    }
}

// Get scheduled classes
$schedule_query = "SELECT s.*, COUNT(sr.id) as registered_count 
                   FROM schedule s 
                   LEFT JOIN schedule_registrations sr ON s.id = sr.schedule_id 
                   WHERE s.instructor_id = $user_id 
                   GROUP BY s.id 
                   ORDER BY s.start_time DESC";
$schedule_result = mysqli_query($conn, $schedule_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - Yogify Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once 'includes/admin-sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2>Manage Live Classes</h2>
                
                <!-- Add Schedule Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Schedule New Class</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Class Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="datetime-local" name="start_time" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">End Time</label>
                                        <input type="datetime-local" name="end_time" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Zoom Link</label>
                                        <input type="url" name="zoom_link" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Max Participants</label>
                                        <input type="number" name="max_participants" class="form-control" value="50" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_schedule" class="btn btn-success">
                                <i class="bi bi-calendar-plus me-1"></i>Schedule Class
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Schedule List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Classes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date & Time</th>
                                        <th>Duration</th>
                                        <th>Participants</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($class = mysqli_fetch_assoc($schedule_result)): 
                                        $is_past = strtotime($class['start_time']) < time();
                                    ?>
                                    <tr>
                                        <td><?php echo $class['title']; ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($class['start_time'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($class['start_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $start = strtotime($class['start_time']);
                                            $end = strtotime($class['end_time']);
                                            $hours = floor(($end - $start) / 3600);
                                            $minutes = floor(($end - $start) / 60) % 60;
                                            echo $hours > 0 ? $hours . 'h ' : '';
                                            echo $minutes . 'min';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $class['registered_count']; ?>/<?php echo $class['max_participants']; ?>
                                        </td>
                                        <td>
                                            <?php if($is_past): ?>
                                                <span class="badge bg-secondary">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Upcoming</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_registrations.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-people"></i>
                                            </a>
                                            <button class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>