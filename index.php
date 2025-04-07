<?php
// Start session for authentication
session_start();

// Define base path constant for security
define('BASEPATH', true);

// Include functions and configuration
require_once 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle login form submission
$error = '';
if (isset($_POST['login_submit'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (verify_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '<div class="alert alert-danger">Invalid username or password.</div>';
    }
}

// Handle delete action
if (is_logged_in() && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $repair_id = (int)$_GET['id'];
    if (delete_repair($repair_id)) {
        $delete_message = '<div class="alert alert-success">Repair deleted successfully.</div>';
    } else {
        $delete_message = '<div class="alert alert-danger">Error deleting repair.</div>';
    }
}

// Get current view
$view = isset($_GET['view']) ? $_GET['view'] : 'list';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Garage - Repair Tracking System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (is_logged_in()): ?>
            <!-- Admin Dashboard -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
                <a class="navbar-brand" href="index.php">PC Garage Repair</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item <?php echo ($view === 'list') ? 'active' : ''; ?>">
                            <a class="nav-link" href="index.php">Dashboard</a>
                        </li>
                        <li class="nav-item <?php echo ($view === 'add') ? 'active' : ''; ?>">
                            <a class="nav-link" href="index.php?view=add">Add New Repair</a>
                        </li>
                    </ul>
                    <span class="navbar-text mr-3">
                        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="index.php?logout=1" class="btn btn-sm btn-outline-light">Logout</a>
                </div>
            </nav>
            
            <?php
            // Handle different admin views
            if (isset($delete_message)) {
                echo $delete_message;
            }
            
            switch ($view) {
                case 'add':
                    include 'views/add_repair.php';
                    break;
                case 'edit':
                    include 'views/edit_repair.php';
                    break;
                default:
                    // Main repair list view
                    $repairs = get_all_repairs();
                    ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Repair Device List</h5>
                            <a href="?view=add" class="btn btn-primary">Add New Repair</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Service Note</th>
                                            <th>Customer Name</th>
                                            <th>Device</th>
                                            <th>Status</th>
                                            <th>Cost</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($repairs) > 0): ?>
                                            <?php foreach ($repairs as $repair): ?>
                                            <tr>
                                                <td><?php echo $repair->id; ?></td>
                                                <td><?php echo htmlspecialchars($repair->service_note_number); ?></td>
                                                <td><?php echo htmlspecialchars($repair->customer_name); ?></td>
                                                <td><?php echo htmlspecialchars($repair->device_type . ' - ' . $repair->device_model); ?></td>
                                                <td><?php echo get_status_badge($repair->status); ?></td>
                                                <td>$<?php echo number_format($repair->repair_cost, 2); ?></td>
                                                <td>
                                                    <a href="?view=edit&id=<?php echo $repair->id; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                                    <a href="?action=delete&id=<?php echo $repair->id; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this repair?');">Delete</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="text-center">No repairs found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
            
        <?php else: ?>
            <!-- Login Form -->
            <div class="card login-card">
                <div class="card-header text-center">
                    <h4>PC Garage Repair</h4>
                    <p class="text-muted">Admin Login</p>
                </div>
                <div class="card-body">
                    <?php echo $error; ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <button type="submit" name="login_submit" class="btn btn-primary btn-block">Login</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="tracking.php">Track your repair</a>
                </div>
            </div>
        <?php endif; ?>
        <div class="footer mt-4 text-center text-muted">
            <small>PC Garage Repair Tracking System &copy; <?php echo date('Y'); ?></small>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>