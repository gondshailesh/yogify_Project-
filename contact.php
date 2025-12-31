<?php
session_start();
include_once 'includes/dbconnect.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_type = $_SESSION['user_type'] ?? '';

// Handle contact form submission
$message_sent = false;
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $subject = mysqli_real_escape_string($conn, $_POST['subject'] ?? '');
    $message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
    
    // Validate inputs
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Check if contact_messages table exists, if not create it
        $table_check = "SHOW TABLES LIKE 'contact_messages'";
        $table_result = mysqli_query($conn, $table_check);
        
        if(mysqli_num_rows($table_result) == 0) {
            // Create contact_messages table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(200),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_read BOOLEAN DEFAULT FALSE
            )";
            
            if(!mysqli_query($conn, $create_table)) {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
        
        // Save to database if no error
        if(empty($error)) {
            $query = "INSERT INTO contact_messages (name, email, subject, message) 
                      VALUES ('$name', '$email', '$subject', '$message')";
            
            if(mysqli_query($conn, $query)) {
                $message_sent = true;
            } else {
                $error = "Failed to send message: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .contact-hero {
            background: linear-gradient(rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.9));
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .contact-info-card {
            padding: 25px 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            height: 100%;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .contact-info-card:hover {
            transform: translateY(-5px);
        }
        .contact-icon {
            font-size: 2rem;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        body {
            padding-top: 76px;
        }
        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #388E3C 0%, #689F38 100%);
            transform: translateY(-2px);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .contact-hero {
                padding: 40px 0;
                margin-bottom: 20px;
            }
            .contact-hero h1 {
                font-size: 2rem;
            }
            .contact-hero .lead {
                font-size: 1rem;
            }
            .card-body {
                padding: 20px !important;
            }
            .contact-info-card {
                padding: 20px 15px;
                margin-bottom: 15px;
            }
            .contact-icon {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .contact-hero {
                padding: 30px 0;
            }
            .contact-hero h1 {
                font-size: 1.8rem;
            }
            .row.mb-3 > .col-md-6 {
                margin-bottom: 15px;
            }
            .btn-lg {
                padding: 10px 20px;
                font-size: 1rem;
            }
            .accordion-button {
                font-size: 0.9rem;
                padding: 12px 15px;
            }
            .accordion-body {
                padding: 15px;
                font-size: 0.9rem;
            }
        }
        
        @media (min-width: 992px) {
            .contact-info-card {
                min-height: 200px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }
        
        /* Animation for form submission */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            animation: fadeIn 0.5s ease;
        }
        
        /* Mobile navigation fix */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                padding: 15px;
                border-radius: 5px;
                margin-top: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include_once 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="contact-hero">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-5 fw-bold mb-3">Contact Us</h1>
                    <p class="lead mb-0">Have questions? We're here to help!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Form & Info -->
    <div class="container py-4">
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-12">
                <div class="card shadow border-0 h-100">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="mb-4">Send us a Message</h3>
                        
                        <?php if($message_sent): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Thank you for your message! We'll get back to you soon.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php elseif(!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name *</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo $is_logged_in ? htmlspecialchars($user_name) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Your Email *</label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo $is_logged_in ? ($_SESSION['user_email'] ?? '') : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" class="form-control" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Message *</label>
                                <textarea name="message" class="form-control" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="row justify-content-between mt-5">
                <div class="contact-info-card col-3">
                    <i class="bi bi-geo-alt contact-icon"></i>
                    <h4 class="h5 mb-2">Our Location</h4>
                    <p class="mb-0">123 Yoga Street<br>Mindfulness City, MC 12345</p>
                </div>
                
                <div class="contact-info-card col-3" >
                    <i class="bi bi-envelope contact-icon"></i>
                    <h4 class="h5 mb-2">Email Us</h4>
                    <p class="mb-0">support@yogify.com<br>info@yogify.com</p>
                </div>
                
                <div class="contact-info-card col-3">
                    <i class="bi bi-phone contact-icon"></i>
                    <h4 class="h5 mb-2">Call Us</h4>
                    <p class="mb-0">+1 (555) 123-4567<br>Mon-Fri, 9am-6pm EST</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-3">Frequently Asked Questions</h2>
                    <p class="text-muted">Find quick answers to common questions</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="bi bi-question-circle me-2"></i>
                                    Do I need any special equipment?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    All you need is a yoga mat and comfortable clothing. Some classes may use props like blocks or straps, but these are optional.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="bi bi-question-circle me-2"></i>
                                    Can beginners join?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolutely! We have beginner-friendly courses and our instructors provide modifications for all levels.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="bi bi-question-circle me-2"></i>
                                    Are the courses accessible forever?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! Once you enroll in a course, you have lifetime access to the content.
                                </div>
                            </div>
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
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if(!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // Remove validation styling on input
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>