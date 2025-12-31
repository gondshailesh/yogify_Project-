<?php
session_start();
include_once 'includes/dbconnect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/navbar.php'; ?>
    
    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h2 class="mb-0">Terms & Conditions</h2>
                        <p class="text-muted mb-0">Last updated: <?php echo date('F d, Y'); ?></p>
                    </div>
                    <div class="card-body p-5">
                        <div class="mb-5">
                            <h4 class="mb-3">1. Acceptance of Terms</h4>
                            <p>By accessing and using Yogify, you accept and agree to be bound by these Terms & Conditions.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">2. User Accounts</h4>
                            <p>You are responsible for maintaining the confidentiality of your account and password.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">3. Course Content</h4>
                            <p>All course materials are for personal use only. Redistribution or commercial use is prohibited.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">4. Payments & Refunds</h4>
                            <p>All payments are non-refundable. Course access is granted for lifetime once purchased.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">5. Limitation of Liability</h4>
                            <p>Yogify is not liable for any injuries that may occur during yoga practice. Practice at your own risk.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h4 class="mb-3">6. Changes to Terms</h4>
                            <p>We reserve the right to modify these terms at any time. Continued use constitutes acceptance.</p>
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