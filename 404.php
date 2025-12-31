<?php
session_start();
include_once 'includes/dbconnect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Yogify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .error-card {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-code">404</div>
            <h1 class="mb-3">Page Not Found</h1>
            <p class="lead mb-4">The page you are looking for doesn't exist or has been moved.</p>
            
            <div class="row g-3 justify-content-center">
                <div class="col-auto">
                    <a href="index.php" class="btn btn-success">
                        <i class="bi bi-house me-2"></i>Go Home
                    </a>
                </div>
                <div class="col-auto">
                    <a href="courses.php" class="btn btn-outline-success">
                        <i class="bi bi-book me-2"></i>Browse Courses
                    </a>
                </div>
                <div class="col-auto">
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Go Back
                    </button>
                </div>
            </div>
            
            <div class="mt-5">
                <p class="text-muted">
                    If you believe this is an error, please 
                    <a href="contact.php">contact support</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>