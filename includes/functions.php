<?php
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user type
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

// Function to display messages
function displayMessage() {
    if(isset($_SESSION['message'])) {
        $type = $_SESSION['msg_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    }
}

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

// Function to check if user is enrolled in a course
function isEnrolled($user_id, $course_id) {
    global $conn;
    $query = "SELECT * FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Function to get user progress in a course
function getCourseProgress($user_id, $course_id) {
    global $conn;
    $query = "SELECT progress_percent FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        return $row['progress_percent'];
    }
    return 0;
}

// Function to format duration
function formatDuration($minutes) {
    if($minutes < 60) {
        return $minutes . ' min';
    } else {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . ' hr ' . $mins . ' min';
    }
}

// Function to generate random color for avatar
function getAvatarColor($name) {
    $colors = ['#4CAF50', '#2196F3', '#FF9800', '#E91E63', '#9C27B0', '#00BCD4'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}
?>