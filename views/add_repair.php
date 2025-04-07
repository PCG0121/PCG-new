<?php
// Check direct access
if(!defined('BASEPATH')) exit('No direct script access allowed');

// Initialize variables
$message = '';
$suggested_service_note = 'REP' . date('Ymd') . sprintf('%04d', mt_rand(1, 9999));

// Handle form submission
if (isset($_POST['add_repair_submit'])) {
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
        'priority' => sanitize_input($_POST['priority']),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $conn = connectDB();
    
    // Check if service note number exists
    $stmt = $conn->prepare("SELECT id FROM repairs WHERE service_note_number = ?");
    $stmt->bind_param("s", $data['service_note_number']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = '<div class="alert alert-danger">Service note number already exists.</div>';
    } else {
        // Insert repair data
        $stmt = $conn->prepare("INSERT INTO repairs (customer_name, phone, email, service_note_number, device_type, device_model, issue_description, status, estimated_completion, repair_cost, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssdss", 
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
            $data['created_at']
        );
        
        if ($stmt->execute()) {
            $repair_id = $conn->insert_id;
            add_status_history($repair_id, null, $data['status'], 'Initial status');
            
            if (isset($_POST['send_notification']) && $_POST['send_notification'] === 'yes') {
                send_status_email($data['email'], $data['service_note_number'], $data['status'], (object)$data);
            }
            
            $message = '<div class="alert alert-success">Repair added successfully! <a href="index.php">View List</a></div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding repair: ' . $conn->error . '</div>';
        }
    }
    
    $stmt->close();
    $conn->close();
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Add New Repair</h5>
    </div>
    <div class="card-body">
        <?php echo $message; ?>
        <form method="post">
            <div class="mb-4">
                <h5>Customer Information</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="customer_name" id="customer_name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" id="phone" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5>Device Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="service_note_number" class="form-label">Service Note Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="service_note_number" id="service_note_number" 
                               value="<?php echo $suggested_service_note; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="device_type" class="form-label">Device Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="device_type" id="device_type" required>
                            <option value="">Select Device Type</option>
                            <option value="Smartphone">Smartphone</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Desktop">Desktop</option>
                            <option value="Gaming Console">Gaming Console</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="device_model" class="form-label">Device Model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="device_model" id="device_model" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="repair_cost" class="form-label">Repair Cost ($)</label>
                        <input type="number" class="form-control" name="repair_cost" id="repair_cost" step="0.01" value="0.00">
                    </div>
                    <div class="col-12 mb-3">
                        <label for="issue_description" class="form-label">Issue Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="issue_description" id="issue_description" rows="3" required></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5>Repair Status</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Initial Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="Received">Received</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Waiting for Parts">Waiting for Parts</option>
                            <option value="Fixed">Fixed</option>
                            <option value="Ready for Pickup">Ready for Pickup</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="estimated_completion" class="form-label">Estimated Completion</label>
                        <input type="date" class="form-control" name="estimated_completion" id="estimated_completion">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority Level</label>
                        <select class="form-select" name="priority" id="priority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="send_notification" value="yes" id="send_notification" checked>
                    <label class="form-check-label" for="send_notification">
                        Send email notification
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="add_repair_submit" class="btn btn-primary">Add Repair</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>