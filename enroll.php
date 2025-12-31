<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Process enrollment
if(isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    
    // Get course details
    $course_query = "SELECT * FROM courses WHERE id = $course_id AND is_published = 1";
    $course_result = mysqli_query($conn, $course_query);
    
    if(mysqli_num_rows($course_result) == 0) {
        $_SESSION['message'] = "Course not found or not available!";
        $_SESSION['msg_type'] = "danger";
        header("Location: courses.php");
        exit();
    }
    
    $course = mysqli_fetch_assoc($course_result);
    
    // Check if already enrolled
    $check_enroll_query = "SELECT * FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
    $check_enroll_result = mysqli_query($conn, $check_enroll_query);
    
    if(mysqli_num_rows($check_enroll_result) > 0) {
        $_SESSION['message'] = "You are already enrolled in this course!";
        $_SESSION['msg_type'] = "warning";
        header("Location: course-details.php?id=$course_id");
        exit();
    }
    
    // Check if course is free or paid
    if($course['price'] > 0) {
        // Redirect to payment page
        header("Location: payment.php?course_id=$course_id");
        exit();
    } else {
        // Free course - enroll directly
        $enroll_query = "INSERT INTO enrollments (user_id, course_id) VALUES ($user_id, $course_id)";
        
        if(mysqli_query($conn, $enroll_query)) {
            // Create progress tracking for all modules
            $modules_query = "SELECT id FROM modules WHERE course_id = $course_id";
            $modules_result = mysqli_query($conn, $modules_query);
            
            while($module = mysqli_fetch_assoc($modules_result)) {
                $progress_query = "INSERT INTO user_progress (user_id, module_id) VALUES ($user_id, {$module['id']})";
                mysqli_query($conn, $progress_query);
            }
            
            $_SESSION['message'] = "Successfully enrolled in '{$course['title']}'!";
            $_SESSION['msg_type'] = "success";
            header("Location: course-player.php?course_id=$course_id");
            exit();
        } else {
            $_SESSION['message'] = "Error enrolling in course: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "danger";
            header("Location: course-details.php?id=$course_id");
            exit();
        }
    }
} else {
    header("Location: courses.php");
    exit();
}
?>