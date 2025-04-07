<?php
// Check direct access
if(!defined('BASEPATH')) exit('No direct script access allowed');

// Get repair ID and validate
$repair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($repair_id <= 0) {
    echo '<div class="alert alert-danger">Invalid repair ID.</div>';
    echo '<p><a href="index.php" class="btn btn-primary">Back to List</a></p>';
    exit;
}

// Connect to database
$conn = connectDB();
$message = '';

// Fetch repair data
$stmt = $conn->prepare("SELECT * FROM repairs WHERE id = ?");
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$result = $stmt->get_result();
$repair = $result->fetch_object();

if (!$repair) {
    echo '<div class="alert alert-danger">Repair not found.</div>';
    echo '<p><a href="index.php" class="btn btn-primary">Back to List</a></p>';
    exit;
}

// Handle form submission
if (isset($_POST['edit_repair_submit'])) {
    $data = [
        'customer_name' => sanitize_input($_POST['customer_name']),
        'phone' => sanitize_input($_POST['phone']),
        'email' => sanitize_input($_POST['email']),
        'service_note_number' => sanitize_input($_POST['service_note_number']),
        'device_type' => sanitize_input($_POST['device_type']),
        'device_model' => sanitize_input($_POST['device_model']),
        'issue_description' => sanitize_input($_POST['issue_description']),
        'status' => sanitize_input($_POST['status']),
        'estimated_completion' => sanitize_input($_POST['estimated_completion']),
        'repair_cost' => floatval($_POST['repair_cost']),
        'priority' => sanitize_input($_POST['priority'])
    ];
    
    // Check if service note number exists
    $stmt = $conn->prepare("SELECT id FROM repairs WHERE service_note_number = ? AND id != ?");
    $stmt->bind_param("si", $data['service_note_number'], $repair_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = '<div class="alert alert-danger">Service note number already exists.</div>';
    } else {
        $previous_status = $repair->status;
        
        // Update repair data
        $stmt = $conn->prepare("UPDATE repairs SET customer_name = ?, phone = ?, email = ?, service_note_number = ?, 
                             device_type = ?, device_model = ?, issue_description = ?, status = ?, 
                             estimated_completion = ?, repair_cost = ?, priority = ? WHERE id = ?");
                             
        $stmt->bind_param("sssssssssdssi", 
            $data['customer_name'], 
            $data['phone'], 
            $data['email'], 
            $data['service_note_number'], 
            $data['device_type'], 
            $data['device_model'], 
            $data['issue_description'], 
            $data['status'], 
            $data['estimated_completion'], 
            $data['repair_cost'], 
            $data['priority'],
            $repair_id
        );
        
        if ($stmt->execute()) {
            // Add status history if status changed
            if ($previous_status !== $data['status']) {
                add_status_history($repair_id, $previous_status, $data['status'], 'Status updated');
                
                if (isset($_POST['send_notification']) && $_POST['send_notification'] === 'yes') {
                    send_status_email($data['email'], $data['service_note_number'], $data['status'], (object)$data);
                }
            }
            
            $message = '<div class="alert alert-success">Repair updated successfully!</div>';
            
            // Refresh repair data
            $stmt = $conn->prepare("SELECT * FROM repairs WHERE id = ?");
            $stmt->bind_param("i", $repair_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $repair = $result->fetch_object();
        } else {
            $message = '<div class="alert alert-danger">Error updating repair: ' . $conn->error . '</div>';
        }
    }
}

// Close database connection
$stmt->close();
$conn->close();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Repair #<?php echo htmlspecialchars($repair->service_note_number); ?></h5>
    </div>
    <div class="card-body">
        <?php echo $message; ?>
        <form method="post">
            <div class="mb-4">
                <h5>Customer Information</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="customer_name" id="customer_name" 
                               value="<?php echo htmlspecialchars($repair->customer_name); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" id="phone" 
                               value="<?php echo htmlspecialchars($repair->phone); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="email" 
                               value="<?php echo htmlspecialchars($repair->email); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5>Device Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="service_note_number" class="form-label">Service Note Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="service_note_number" id="service_note_number" 
                               value="<?php echo htmlspecialchars($repair->service_note_number); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="device_type" class="form-label">Device Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="device_type" id="device_type" required>
                            <option value="Smartphone" <?php if($repair->device_type == 'Smartphone') echo 'selected'; ?>>Smartphone</option>
                            <option value="Tablet" <?php if($repair->device_type == 'Tablet') echo 'selected'; ?>>Tablet</option>
                            <option value="Laptop" <?php if($repair->device_type == 'Laptop') echo 'selected'; ?>>Laptop</option>
                            <option value="Desktop" <?php if($repair->device_type == 'Desktop') echo 'selected'; ?>>Desktop</option>
                            <option value="Gaming Console" <?php if($repair->device_type == 'Gaming Console') echo 'selected'; ?>>Gaming Console</option>
                            <option value="Other" <?php if($repair->device_type == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="device_model" class="form-label">Device Model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="device_model" id="device_model" 
                               value="<?php echo htmlspecialchars($repair->device_model); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="repair_cost" class="form-label">Repair Cost ($)</label>
                        <input type="number" class="form-control" name="repair_cost" id="repair_cost" step="0.01" 
                               value="<?php echo number_format($repair->repair_cost, 2, '.', ''); ?>">
                    </div>
                    <div class="col-12 mb-3">
                        <label for="issue_description" class="form-label">Issue Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="issue_description" id="issue_description" rows="3" required><?php echo htmlspecialchars($repair->issue_description); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5>Repair Status</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="Received" <?php if($repair->status == 'Received') echo 'selected'; ?>>Received</option>
                            <option value="In Progress" <?php if($repair->status == 'In Progress') echo 'selected'; ?>>In Progress</option>
                            <option value="Waiting for Parts" <?php if($repair->status == 'Waiting for Parts') echo 'selected'; ?>>Waiting for Parts</option>
                            <option value="Fixed" <?php if($repair->status == 'Fixed') echo 'selected'; ?>>Fixed</option>
                            <option value="Ready for Pickup" <?php if($repair->status == 'Ready for Pickup') echo 'selected'; ?>>Ready for Pickup</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="estimated_completion" class="form-label">Estimated Completion</label>
                        <input type="date" class="form-control" name="estimated_completion" id="estimated_completion"
                               value="<?php echo htmlspecialchars($repair->estimated_completion); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority Level</label>
                        <select class="form-select" name="priority" id="priority">
                            <option value="Low" <?php if($repair->priority == 'Low') echo 'selected'; ?>>Low</option>
                            <option value="Medium" <?php if($repair->priority == 'Medium') echo 'selected'; ?>>Medium</option>
                            <option value="High" <?php if($repair->priority == 'High') echo 'selected'; ?>>High</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="send_notification" value="yes" id="send_notification" checked>
                    <label class="form-check-label" for="send_notification">
                        Send email notification if status changes
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="edit_repair_submit" class="btn btn-primary">Update Repair</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>