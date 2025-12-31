<?php
session_start();
include_once 'includes/dbconnect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h2 class="mb-0">Privacy Policy</h2>
                        <p class="text-muted mb-0">Last updated: <?php echo date('F d, Y'); ?></p>
                    </div>
                    <div class="card-body p-5">
                        <div class="mb-5">
                            <h4 class="mb-3">1. Information We Collect</h4>
                            <p>We collect personal information such as name, email, and payment details when you register.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">2. How We Use Your Information</h4>
                            <p>Your information is used to provide services, process payments, and communicate updates.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">3. Data Security</h4>
                            <p>We implement security measures to protect your personal information.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">4. Third-Party Services</h4>
                            <p>We may use third-party services for payment processing and analytics.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">5. Your Rights</h4>
                            <p>You have the right to access, modify, or delete your personal information.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">6. Contact Us</h4>
                            <p>For privacy concerns, contact us at privacy@yogify.com</p>
                        </div>
                        
                        <div class="text-center mt-5">
                            <a href="register.php" class="btn btn-success">Back to Registration</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>