<?php
session_start();
include_once '../includes/dbconnect.php';

// Check if user is admin/instructor
if(!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'instructor')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get course ID
$course_id = $_GET['course_id'] ?? 0;

// Verify course ownership
if($user_type == 'instructor') {
    $check_query = "SELECT * FROM courses WHERE id = $course_id AND instructor_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    if(mysqli_num_rows($check_result) == 0) {
        header("Location: manage_courses.php");
        exit();
    }
}

// Handle module operations
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_module'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $video_url = mysqli_real_escape_string($conn, $_POST['video_url']);
        $duration = mysqli_real_escape_string($conn, $_POST['duration']);
        
        // Get max module order
        $order_query = "SELECT MAX(module_order) as max_order FROM modules WHERE course_id = $course_id";
        $order_result = mysqli_query($conn, $order_query);
        $order_data = mysqli_fetch_assoc($order_result);
        $module_order = $order_data['max_order'] + 1;
        
        $insert_query = "INSERT INTO modules (course_id, title, description, video_url, duration, module_order) 
                         VALUES ($course_id, '$title', '$description', '$video_url', '$duration', $module_order)";
        
        mysqli_query($conn, $insert_query);
        $_SESSION['message'] = "Module added successfully!";
        $_SESSION['msg_type'] = "success";
    }
    
    if(isset($_POST['delete_module'])) {
        $module_id = $_POST['module_id'];
        $delete_query = "DELETE FROM modules WHERE id = $module_id";
        mysqli_query($conn, $delete_query);
        
        // Reorder modules
        $reorder_query = "SET @order_num = 0;
                         UPDATE modules SET module_order = (@order_num := @order_num + 1) 
                         WHERE course_id = $course_id ORDER BY module_order";
        mysqli_multi_query($conn, $reorder_query);
        
        $_SESSION['message'] = "Module deleted successfully!";
        $_SESSION['msg_type'] = "success";
    }
}

// Get course details
$course_query = "SELECT * FROM courses WHERE id = $course_id";
$course_result = mysqli_query($conn, $course_query);
$course = mysqli_fetch_assoc($course_result);

// Get modules
$modules_query = "SELECT * FROM modules WHERE course_id = $course_id ORDER BY module_order";
$modules_result = mysqli_query($conn, $modules_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - Yogify Admin</title>
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
                <h2>Manage Modules: <?php echo $course['title']; ?></h2>
                
                <!-- Add Module Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Module</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Module Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Video URL (YouTube/Vimeo)</label>
                                        <input type="url" name="video_url" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Duration (e.g., 15 min)</label>
                                        <input type="text" name="duration" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_module" class="btn btn-success">
                                <i class="bi bi-plus-circle me-1"></i>Add Module
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Modules List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Course Modules</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Duration</th>
                                        <th>Video</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($module = mysqli_fetch_assoc($modules_result)): ?>
                                    <tr>
                                        <td><?php echo $module['module_order']; ?></td>
                                        <td><?php echo $module['title']; ?></td>
                                        <td><?php echo $module['duration']; ?></td>
                                        <td>
                                            <?php if(!empty($module['video_url'])): ?>
                                                <a href="<?php echo $module['video_url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-play-btn"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-warning">No video</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                                <button type="submit" name="delete_module" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Delete this module?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <a href="edit_module.php?id=<?php echo $module['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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