<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/dbconnect.php';

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
$instructor = isset($_GET['instructor']) ? $_GET['instructor'] : '';

// Check if courses table exists
$table_check = $conn->query("SHOW TABLES LIKE 'courses'");
if($table_check->num_rows == 0) {
    die("<div class='container py-5 text-center'>
            <h3>Courses database not set up yet</h3>
            <p>Please run the SQL setup script to create the courses table.</p>
            <a href='index.php' class='btn btn-primary'>Go Home</a>
         </div>");
}

// Build basic query
$query = "SELECT c.*, u.full_name as instructor_name FROM courses c 
          LEFT JOIN users u ON c.instructor_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
    $types .= "ss";
}

// Add category filter
if (!empty($category) && $category != 'all') {
    $query .= " AND c.category = ?";
    $params[] = $category;
    $types .= "s";
}

// Add level filter
if (!empty($level) && $level != 'all') {
    $query .= " AND c.level = ?";
    $params[] = $level;
    $types .= "s";
}

// Add instructor filter
if (!empty($instructor) && $instructor != 'all') {
    $query .= " AND c.instructor_id = ?";
    $params[] = $instructor;
    $types .= "i";
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch($sort) {
    case 'popular':
        $query .= " ORDER BY c.id DESC"; // Fallback - use ID if no enrollment data
        break;
    case 'rating':
        $query .= " ORDER BY c.id DESC"; // Fallback
        break;
    case 'price_low':
        $query .= " ORDER BY c.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY c.price DESC";
        break;
    default:
        $query .= " ORDER BY c.created_at DESC";
}

// Pagination
$per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = str_replace("SELECT c.*, u.full_name as instructor_name", "SELECT COUNT(*) as total", $query);
$count_query = explode("ORDER BY", $count_query)[0]; // Remove ORDER BY

// Execute count query
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}

$total_courses = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_courses / $per_page);

// Add limit to main query
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

// Execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$courses = $result->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$cat_query = "SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != '' ORDER BY category";
$cat_result = $conn->query($cat_query);
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// Get levels for filter
$level_query = "SELECT DISTINCT level FROM courses WHERE level IS NOT NULL AND level != '' ORDER BY level";
$level_result = $conn->query($level_query);
$levels = $level_result->fetch_all(MYSQLI_ASSOC);

// Get instructors for filter
$inst_query = "SELECT DISTINCT u.id, u.full_name FROM users u 
               INNER JOIN courses c ON u.id = c.instructor_id 
               WHERE u.user_type = 'instructor' 
               ORDER BY u.full_name";
$inst_result = $conn->query($inst_query);
$instructors = $inst_result->fetch_all(MYSQLI_ASSOC);

// Function to get course rating (with fallback if reviews table doesn't exist)
function getCourseRating($conn, $course_id) {
    // Check if reviews table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if($table_check->num_rows == 0) {
        return null; // Table doesn't exist
    }
    
    $query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return $data['avg_rating'];
}

// Function to get enrolled count (with fallback if enrollments table doesn't exist)
function getEnrolledCount($conn, $course_id) {
    // Check if enrollments table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'enrollments'");
    if($table_check->num_rows == 0) {
        return 0; // Table doesn't exist
    }
    
    $query = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return $data['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/yogify.css">
    <style>
        .course-card {
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
        }
        
        .course-img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        
        .badge-category {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
        }
        
        .rating {
            color: #FFD700;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .free-badge {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
        }
        
        .filter-sidebar {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 90px;
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
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center text-white">
                    <h1 class="display-4 fw-bold mb-3">Discover Your Yoga Journey</h1>
                    <p class="lead mb-4">Browse through our carefully curated collection of yoga courses</p>
                    
                    <!-- Search Form -->
                    <form method="GET" action="" class="row g-3 justify-content-center">
                        <div class="col-md-8">
                            <div class="input-group input-group-lg">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search courses..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-light" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-4">Filter Courses</h5>
                    
                    <form method="GET" action="" id="filterForm">
                        <!-- Category Filter -->
                        <?php if(!empty($categories)): ?>
                        <div class="mb-4">
                            <h6 class="mb-3">Category</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="category" value="all" 
                                       id="cat-all" <?php echo ($category == '' || $category == 'all') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cat-all">All Categories</label>
                            </div>
                            <?php foreach($categories as $cat): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="category" 
                                           value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                           id="cat-<?php echo strtolower(str_replace(' ', '-', $cat['category'])); ?>"
                                           <?php echo ($category == $cat['category']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat-<?php echo strtolower(str_replace(' ', '-', $cat['category'])); ?>">
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Level Filter -->
                        <?php if(!empty($levels)): ?>
                        <div class="mb-4">
                            <h6 class="mb-3">Level</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="level" value="all" 
                                       id="level-all" <?php echo ($level == '' || $level == 'all') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="level-all">All Levels</label>
                            </div>
                            <?php foreach($levels as $lvl): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="level" 
                                           value="<?php echo htmlspecialchars($lvl['level']); ?>" 
                                           id="level-<?php echo $lvl['level']; ?>"
                                           <?php echo ($level == $lvl['level']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="level-<?php echo $lvl['level']; ?>">
                                        <?php echo ucfirst($lvl['level']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Sort Options -->
                        <div class="mb-4">
                            <h6 class="mb-3">Sort By</h6>
                            <select name="sort" class="form-select">
                                <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <!-- Hidden fields -->
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-success w-100">Apply Filters</button>
                        <a href="courses.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">All Courses</h4>
                        <p class="text-muted mb-0">
                            <?php echo $total_courses; ?> course<?php echo ($total_courses != 1) ? 's' : ''; ?> found
                            <?php if(!empty($search)): ?>
                                for "<?php echo htmlspecialchars($search); ?>"
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <?php if(empty($courses)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <h4>No courses found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms</p>
                        <a href="courses.php" class="btn btn-success">Browse All Courses</a>
                    </div>
                <?php else: ?>
                    <!-- Courses Grid -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach($courses as $course): 
                            $rating = getCourseRating($conn, $course['id']);
                            $enrolled_count = getEnrolledCount($conn, $course['id']);
                        ?>
                            <div class="col">
                                <div class="card course-card h-100 shadow-sm">
                                    <!-- Course Image -->
                                    <div class="position-relative">
                                        <?php if(!empty($course['image_url'])): ?>
                                            <img src="uploads/courses/<?php echo htmlspecialchars($course['image_url']); ?>" 
                                                 class="card-img-top course-img" 
                                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php else: ?>
                                            <div class="course-img bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-flower1" style="font-size: 3rem; color: #4CAF50;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Category Badge -->
                                        <?php if(!empty($course['category'])): ?>
                                            <span class="badge free-badge badge-category">
                                                <?php echo htmlspecialchars($course['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Level Badge -->
                                        <?php if(!empty($course['level'])): ?>
                                            <span class="badge bg-<?php 
                                                echo ($course['level'] == 'beginner') ? 'success' : 
                                                     (($course['level'] == 'intermediate') ? 'warning' : 'danger'); 
                                            ?> position-absolute" style="top: 10px; right: 10px;">
                                                <?php echo ucfirst($course['level']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <!-- Course Title -->
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        
                                        <!-- Course Description -->
                                        <p class="card-text flex-grow-1">
                                            <?php 
                                            $description = strip_tags($course['description'] ?? '');
                                            echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                            ?>
                                        </p>
                                        
                                        <!-- Instructor -->
                                        <?php if(!empty($course['instructor_name'])): ?>
                                        <p class="text-muted mb-2">
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($course['instructor_name']); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <!-- Rating -->
                                        <?php if($rating): ?>
                                            <div class="mb-3">
                                                <span class="rating">
                                                    <?php 
                                                    $rating = round($rating, 1);
                                                    $fullStars = floor($rating);
                                                    $halfStar = ($rating - $fullStars) >= 0.5;
                                                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                                    
                                                    for($i = 0; $i < $fullStars; $i++) {
                                                        echo '<i class="bi bi-star-fill"></i> ';
                                                    }
                                                    if($halfStar) {
                                                        echo '<i class="bi bi-star-half"></i> ';
                                                    }
                                                    for($i = 0; $i < $emptyStars; $i++) {
                                                        echo '<i class="bi bi-star"></i> ';
                                                    }
                                                    ?>
                                                </span>
                                                <small class="text-muted ms-1">(<?php echo $rating; ?>)</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Price & Enroll Button -->
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <div class="price">
                                                <?php if(($course['price'] ?? 0) == 0): ?>
                                                    <span class="text-success">Free</span>
                                                <?php else: ?>
                                                    ₹<?php echo number_format($course['price'], 2); ?>
                                                <?php endif; ?>
                                            </div>
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" 
                                               class="btn btn-outline-success btn-sm">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Enrollment Count -->
                                    <?php if($enrolled_count > 0): ?>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <small class="text-muted">
                                            <i class="bi bi-people"></i> 
                                            <?php echo $enrolled_count; ?> enrolled
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Page -->
                                <li class="page-item <?php echo ($page == 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?<?php 
                                           echo http_build_query(array_merge($_GET, ['page' => $page - 1]));
                                       ?>">
                                        Previous
                                    </a>
                                </li>
                                
                                <!-- Page Numbers -->
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?<?php 
                                               echo http_build_query(array_merge($_GET, ['page' => $i]));
                                           ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Next Page -->
                                <li class="page-item <?php echo ($page == $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" 
                                       href="?<?php 
                                           echo http_build_query(array_merge($_GET, ['page' => $page + 1]));
                                       ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>