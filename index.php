<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/dbconnect.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';

// Get filter parameters
$category = $_GET['category'] ?? '';
$level = $_GET['level'] ?? '';
$search = $_GET['search'] ?? '';

// Function to check if table exists
function tableExists($conn, $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    return $check->num_rows > 0;
}

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $check->num_rows > 0;
}

// Build base query
$query = "SELECT c.*, u.full_name as instructor_name";

// Add enrolled_students count only if enrollments table exists
if(tableExists($conn, 'enrollments')) {
    $query .= ", COUNT(DISTINCT e.id) as enrolled_students";
} else {
    $query .= ", 0 as enrolled_students";
}

$query .= " FROM courses c 
           LEFT JOIN users u ON c.instructor_id = u.id";

// Add enrollments join only if table exists
if(tableExists($conn, 'enrollments')) {
    $query .= " LEFT JOIN enrollments e ON c.id = e.course_id";
}

$query .= " WHERE 1=1";

// Add is_published filter only if column exists
if(columnExists($conn, 'courses', 'is_published')) {
    $query .= " AND (c.is_published = 1 OR c.is_published IS NULL)";
}

// Add is_active filter if column exists
if(columnExists($conn, 'courses', 'is_active')) {
    $query .= " AND c.is_active = 1";
}

// Add filters with prepared statements
$params = [];
$types = "";

if(!empty($category)) {
    $query .= " AND c.category = ?";
    $params[] = $category;
    $types .= "s";
}

if(!empty($level)) {
    $query .= " AND c.level = ?";
    $params[] = $level;
    $types .= "s";
}

if(!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

// Execute query with prepared statement
if(!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $courses_result = $stmt->get_result();
} else {
    $courses_result = $conn->query($query);
}

// Get distinct categories for filters
$categories = [];
if(tableExists($conn, 'courses')) {
    $categories_query = "SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $cat_result = $conn->query($categories_query);
    if($cat_result) {
        $categories = $cat_result->fetch_all(MYSQLI_ASSOC);
    }
}

$levels = ['beginner', 'intermediate', 'advanced'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/yogify.css">
    <style>
        .courses-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        
        .filter-sidebar {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 90px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-group {
            margin-bottom: 25px;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        
        .course-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .course-img {
            height: 180px;
            object-fit: cover;
            width: 100%;
        }
        
        .price-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .level-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 0.8rem;
        }
        
        .form-check-input:checked {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="courses-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">Discover Your Yoga Journey</h1>
                    <p class="lead mb-4">Browse through our collection of yoga courses for all levels</p>
                    
                    <!-- Search Bar -->
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group input-group-lg">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search courses by title or description..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-light" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Courses Section -->
    <div class="container py-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h4 class="mb-4">Filter Courses</h4>
                    
                    <form method="GET" action="" id="filterForm">
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h6 class="filter-title">Category</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="category" value="" 
                                       id="cat-all" <?php echo empty($category) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cat-all">
                                    All Categories
                                </label>
                            </div>
                            <?php foreach($categories as $cat): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="category" 
                                       value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                       id="cat-<?php echo strtolower(str_replace(' ', '-', $cat['category'])); ?>"
                                       <?php echo $category == $cat['category'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cat-<?php echo strtolower(str_replace(' ', '-', $cat['category'])); ?>">
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Level Filter -->
                        <div class="filter-group">
                            <h6 class="filter-title">Difficulty Level</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="level" value="" 
                                       id="level-all" <?php echo empty($level) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="level-all">
                                    All Levels
                                </label>
                            </div>
                            <?php foreach($levels as $lvl): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="level" value="<?php echo $lvl; ?>" 
                                       id="level-<?php echo $lvl; ?>"
                                       <?php echo $level == $lvl ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="level-<?php echo $lvl; ?>">
                                    <?php echo ucfirst($lvl); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Hidden search field -->
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-funnel me-1"></i> Apply Filters
                        </button>
                        <a href="courses.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3>Available Courses</h3>
                        <?php if(!empty($search)): ?>
                            <p class="text-muted mb-0">Search results for "<?php echo htmlspecialchars($search); ?>"</p>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-success fs-6">
                        <?php echo $courses_result ? $courses_result->num_rows : 0; ?> courses found
                    </span>
                </div>
                
                <?php if($courses_result && $courses_result->num_rows > 0): ?>
                    <div class="row g-4">
                        <?php while($course = $courses_result->fetch_assoc()): 
                            $is_enrolled = false;
                            if($is_logged_in && tableExists($conn, 'enrollments')) {
                                $check_query = "SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?";
                                $check_stmt = $conn->prepare($check_query);
                                $check_stmt->bind_param("ii", $_SESSION['user_id'], $course['id']);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                $is_enrolled = $check_result->num_rows > 0;
                            }
                            
                            // Get course image
                            $course_image = 'images/course-default.jpg';
                            if(!empty($course['image_url']) && file_exists('uploads/courses/' . $course['image_url'])) {
                                $course_image = 'uploads/courses/' . $course['image_url'];
                            } elseif(!empty($course['thumbnail'])) {
                                $course_image = htmlspecialchars($course['thumbnail']);
                            }
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="course-card">
                                <div class="position-relative">
                                    <img src="<?php echo $course_image; ?>" 
                                         class="course-img" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                                         onerror="this.src='images/course-default.jpg'">
                                    
                                    <?php if(($course['price'] ?? 0) > 0): ?>
                                        <span class="price-tag">₹<?php echo number_format($course['price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="price-tag bg-success">Free</span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($course['level'])): ?>
                                    <span class="level-badge badge bg-<?php 
                                        echo $course['level'] == 'beginner' ? 'success' : 
                                             ($course['level'] == 'intermediate' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($course['level']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php 
                                        $description = strip_tags($course['description'] ?? '');
                                        echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                                        ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <i class="bi bi-person me-1 text-muted"></i>
                                            <small class="text-muted"><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></small>
                                        </div>
                                        <div>
                                            <i class="bi bi-people me-1 text-muted"></i>
                                            <small class="text-muted"><?php echo $course['enrolled_students']; ?> students</small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-between">
                                        <?php if(!empty($course['duration'])): ?>
                                        <div>
                                            <i class="bi bi-clock text-muted me-1"></i>
                                            <small class="text-muted"><?php echo htmlspecialchars($course['duration']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($course['category'])): ?>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($course['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-white border-0 pt-0">
                                    <?php if($is_enrolled): ?>
                                        <a href="course-player.php?course_id=<?php echo $course['id']; ?>" 
                                           class="btn btn-success w-100">
                                            <i class="bi bi-play-circle me-1"></i>Continue Learning
                                        </a>
                                    <?php else: ?>
                                        <a href="course-details.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-outline-success w-100">
                                            <i class="bi bi-eye me-1"></i>View Course
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <h4>No courses found</h4>
                        <p class="text-muted">
                            <?php if(!empty($search) || !empty($category) || !empty($level)): ?>
                                Try adjusting your filters or search terms
                            <?php else: ?>
                                No courses are available at the moment
                            <?php endif; ?>
                        </p>
                        <a href="courses.php" class="btn btn-success">
                            <i class="bi bi-arrow-clockwise me-1"></i>Browse All Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <?php if(!$is_logged_in): ?>
    <div class="bg-light py-5 mt-5">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Start Your Yoga Journey?</h2>
            <p class="lead mb-4">Join our community of yoga enthusiasts</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.php" class="btn btn-success btn-lg">
                    <i class="bi bi-person-plus me-2"></i>Sign Up Free
                </a>
                <a href="about.php" class="btn btn-outline-success btn-lg">
                    <i class="bi bi-info-circle me-2"></i>Learn More
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit filter form on change for desktop
        if(window.innerWidth > 768) {
            document.querySelectorAll('#filterForm input[type="radio"]').forEach(input => {
                input.addEventListener('change', function() {
                    this.form.submit();
                });
            });
        }
        
        // Search field enter key support
        const searchInput = document.querySelector('input[name="search"]');
        if(searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>