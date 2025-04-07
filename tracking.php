<?php
// Define base path constant for security
define('BASEPATH', true);

// Include functions and configuration
require_once 'functions.php';

// Initialize variables
$message = '';
$repair = null;
$history = null;

// Process form submission
if (isset($_POST['track_repair_submit'])) {
    $service_note_number = sanitize_input($_POST['service_note_number']);
    
    // Connect to database
    $conn = connectDB();
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM repairs WHERE service_note_number = ?");
    $stmt->bind_param("s", $service_note_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $repair = $result->fetch_object();
        
        // Get repair history
        $history = get_repair_history($repair->id);
        
        $message = '<div class="alert alert-success">Repair found! Here are the details.</div>';
    } else {
        $message = '<div class="alert alert-danger">No repair found with that service note number.</div>';
    }
    
    $stmt->close();
    $conn->close();
}

// Status progression percentages
$status_order = [
    'Received' => 20, 
    'In Progress' => 40, 
    'Waiting for Parts' => 60, 
    'Fixed' => 80, 
    'Ready for Pickup' => 100
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Repair - PC Garage</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-center mb-4">
                    <h2>Track Your Repair</h2>
                    <p class="text-muted">PC Garage Repair Tracking System</p>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" class="tracking-form">
                            <div class="form-group">
                                <label for="service_note_number">Service Note Number</label>
                                <input type="text" class="form-control" name="service_note_number" id="service_note_number" 
                                    placeholder="e.g., REP202504040001" required>
                            </div>
                            <button type="submit" name="track_repair_submit" class="btn btn-primary">Check Status</button>
                        </form>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <?php if ($repair): ?>
                <div class="card repair-details mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Your Repair Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                style="width: <?php echo $status_order[$repair->status] ?? 0; ?>%;" 
                                aria-valuenow="<?php echo $status_order[$repair->status] ?? 0; ?>" 
                                aria-valuemin="0" aria-valuemax="100">
                                <?php echo htmlspecialchars($repair->status); ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-box">
                                    <h5>Device</h5>
                                    <p><?php echo htmlspecialchars($repair->device_type . ' - ' . $repair->device_model); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-box">
                                    <h5>Issue</h5>
                                    <p><?php echo htmlspecialchars($repair->issue_description); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-box">
                                    <h5>Repair Cost</h5>
                                    <p>$<?php echo number_format($repair->repair_cost, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($history && count($history) > 0): ?>
                        <div class="mt-4">
                            <h5>Status Updates</h5>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $entry): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($entry->created_at)); ?></td>
                                        <td>
                                            <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $entry->new_status)); ?>">
                                                <?php echo htmlspecialchars($entry->new_status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($entry->notes); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <p>Need help? Contact us at <?php echo SITE_PHONE; ?> or <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <a href="index.php" class="btn btn-outline-secondary">Admin Login</a>
                </div>
            </div>
        </div>
        
        <div class="footer text-center text-muted">
            <small>PC Garage Repair Tracking System &copy; <?php echo date('Y'); ?></small>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>