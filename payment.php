<?php
session_start();
include_once 'includes/dbconnect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;

if(!$course_id) {
    header("Location: courses.php");
    exit();
}

// Get course details
$course_query = "SELECT * FROM courses WHERE id = $course_id AND is_published = 1";
$course_result = mysqli_query($conn, $course_query);

if(mysqli_num_rows($course_result) == 0) {
    header("Location: courses.php");
    exit();
}

$course = mysqli_fetch_assoc($course_result);

// Check if already enrolled
$check_enroll = "SELECT * FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
$check_result = mysqli_query($conn, $check_enroll);

if(mysqli_num_rows($check_result) > 0) {
    header("Location: course-player.php?course_id=$course_id");
    exit();
}

// Handle payment submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // In real app: Process payment via PayPal/Stripe
    // For demo: Direct enrollment
    
    $enroll_query = "INSERT INTO enrollments (user_id, course_id) VALUES ($user_id, $course_id)";
    
    if(mysqli_query($conn, $enroll_query)) {
        // Create progress records
        $modules_query = "SELECT id FROM modules WHERE course_id = $course_id";
        $modules_result = mysqli_query($conn, $modules_query);
        
        while($module = mysqli_fetch_assoc($modules_result)) {
            $progress_query = "INSERT INTO user_progress (user_id, module_id) VALUES ($user_id, {$module['id']})";
            mysqli_query($conn, $progress_query);
        }
        
        $_SESSION['message'] = "Payment successful! Course enrolled.";
        $_SESSION['msg_type'] = "success";
        header("Location: course-player.php?course_id=$course_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Complete Your Enrollment</h4>
                    </div>
                    <div class="card-body p-5">
                        <!-- Course Details -->
                        <div class="mb-5">
                            <h5>Course Details:</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <?php if($course['thumbnail']): ?>
                                        <img src="<?php echo $course['thumbnail']; ?>" alt="Course" width="100">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?php echo $course['title']; ?></h6>
                                    <p class="text-muted mb-1"><?php echo $course['category']; ?> • <?php echo ucfirst($course['level']); ?> Level</p>
                                    <p class="text-muted mb-0">Duration: <?php echo $course['duration']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Summary -->
                        <div class="mb-5">
                            <h5>Payment Summary:</h5>
                            <table class="table">
                                <tr>
                                    <td>Course Price</td>
                                    <td class="text-end">$<?php echo $course['price']; ?></td>
                                </tr>
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end">$0.00</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount</strong></td>
                                    <td class="text-end"><strong>$<?php echo $course['price']; ?></strong></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Payment Methods -->
                        <form method="POST" action="">
                            <h5 class="mb-3">Select Payment Method:</h5>
                            
                            <div class="mb-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" value="credit_card" id="creditCard" checked>
                                    <label class="form-check-label" for="creditCard">
                                        <i class="bi bi-credit-card me-2"></i>Credit/Debit Card
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" value="paypal" id="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <i class="bi bi-paypal me-2"></i>PayPal
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="free" id="free" disabled>
                                    <label class="form-check-label text-muted" for="free">
                                        <i class="bi bi-currency-dollar me-2"></i>Free Enrollment (Contact Admin)
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Credit Card Form (Demo Only) -->
                            <div id="creditCardForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Card Number</label>
                                        <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">CVV</label>
                                        <input type="text" class="form-control" placeholder="123" maxlength="3">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cardholder Name</label>
                                    <input type="text" class="form-control" placeholder="John Doe">
                                </div>
                            </div>
                            
                            <!-- PayPal Form (Demo Only) -->
                            <div id="paypalForm" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    You will be redirected to PayPal to complete your payment.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="course-details.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary me-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-lock me-2"></i>Pay $<?php echo $course['price']; ?>
                                </button>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <small class="text-muted">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Your payment is secure and encrypted
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle payment forms
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('creditCardForm').style.display = 'none';
                document.getElementById('paypalForm').style.display = 'none';
                
                if(this.value === 'credit_card') {
                    document.getElementById('creditCardForm').style.display = 'block';
                } else if(this.value === 'paypal') {
                    document.getElementById('paypalForm').style.display = 'block';
                }
            });
        });
        
        // Format card number
        document.querySelector('input[placeholder*="Card Number"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let matches = value.match(/\d{4,16}/g);
            let match = matches && matches[0] || '';
            let parts = [];
            
            for (let i = 0, len = match.length; i < len; i += 4) {
                parts.push(match.substring(i, i + 4));
            }
            
            if (parts.length) {
                e.target.value = parts.join(' ');
            } else {
                e.target.value = value;
            }
        });
    </script>
</body>
</html>