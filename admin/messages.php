<?php
session_start();
include_once '../includes/dbconnect.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get messages
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Mark as read
if(isset($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $update_query = "UPDATE contact_messages SET is_read = 1 WHERE id = $message_id";
    mysqli_query($conn, $update_query);
    header("Location: messages.php");
    exit();
}

// Delete message
if(isset($_GET['delete'])) {
    $message_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM contact_messages WHERE id = $message_id";
    mysqli_query($conn, $delete_query);
    header("Location: messages.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include_once 'includes/admin-sidebar.php'; ?>
            
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2>Contact Messages</h2>
                
                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Messages</h5>
                                <h3><?php echo mysqli_num_rows($result); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Unread</h5>
                                <h3>
                                    <?php 
                                    $unread_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
                                    $unread_result = mysqli_query($conn, $unread_query);
                                    echo mysqli_fetch_assoc($unread_result)['count'];
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Replied</h5>
                                <h3>
                                    <?php 
                                    $replied_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 1";
                                    $replied_result = mysqli_query($conn, $replied_query);
                                    echo mysqli_fetch_assoc($replied_result)['count'];
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Messages List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($message = mysqli_fetch_assoc($result)): ?>
                                    <tr class="<?php echo !$message['is_read'] ? 'table-warning' : ''; ?>">
                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                        <td>
                                            <a href="mailto:<?php echo $message['email']; ?>">
                                                <?php echo $message['email']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td>
                                            <small><?php echo substr($message['message'], 0, 50); ?>...</small>
                                            <button class="btn btn-sm btn-link" data-bs-toggle="modal" 
                                                    data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                                Read More
                                            </button>
                                        </td>
                                        <td>
                                            <?php echo date('M d', strtotime($message['created_at'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($message['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if($message['is_read']): ?>
                                                <span class="badge bg-success">Read</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Unread</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!$message['is_read']): ?>
                                                <a href="messages.php?mark_read=<?php echo $message['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Mark as Read">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="mailto:<?php echo $message['email']; ?>?subject=Re: <?php echo urlencode($message['subject']); ?>" 
                                               class="btn btn-sm btn-primary" title="Reply">
                                                <i class="bi bi-reply"></i>
                                            </a>
                                            <a href="messages.php?delete=<?php echo $message['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this message?')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Message Modal -->
                                    <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Message from <?php echo htmlspecialchars($message['name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?><br>
                                                        <strong>Email:</strong> <?php echo $message['email']; ?><br>
                                                        <strong>Subject:</strong> <?php echo htmlspecialchars($message['subject']); ?><br>
                                                        <strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($message['created_at'])); ?>
                                                    </div>
                                                    <hr>
                                                    <div>
                                                        <strong>Message:</strong>
                                                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="mailto:<?php echo $message['email']; ?>?subject=Re: <?php echo urlencode($message['subject']); ?>" 
                                                       class="btn btn-primary">
                                                        <i class="bi bi-reply me-1"></i>Reply
                                                    </a>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if(mysqli_num_rows($result) == 0): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-envelope fs-1 text-muted mb-3"></i>
                                <h5>No messages yet</h5>
                                <p class="text-muted">All contact messages will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>