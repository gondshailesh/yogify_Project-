<?php
// This is a shared navigation component
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = dirname($_SERVER['PHP_SELF']);

// Check if we're in admin folder
$is_admin_page = strpos($current_dir, 'admin') !== false;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-flower1 me-2"></i>Yogify
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php' && !$is_admin_page) ? 'active' : ''; ?>" 
                       href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'courses.php') ? 'active' : ''; ?>" 
                       href="courses.php">Courses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>" 
                       href="schedule.php">Live Classes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" 
                       href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>" 
                       href="contact.php">Contact</a>
                </li>
                <?php if($is_logged_in && ($user_type == 'instructor' || $user_type == 'admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php' || $is_admin_page) ? 'active' : ''; ?>" 
                       href="dashboard.php">Dashboard</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex">
                <?php if($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($user_name); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">My Dashboard</a></li>
                            <li><a class="dropdown-item" href="my-courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="my-progress.php">My Progress</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <?php if($user_type == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="logout.php" class="w-100 ps-1 pe-1 btn btn-outline-danger btn-sm">
                                        <i class="bi bi-box-arrow-center"></i> Logout</a>
                                        <?php endif; ?></a>
                        </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-success me-2">Login</a>
                    <a href="register.php" class="btn btn-success">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>