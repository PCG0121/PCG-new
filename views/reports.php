<?php
// Check direct access
if(!defined('BASEPATH')) exit('No direct script access allowed');

// Connect to database
$conn = connectDB();

// Get counts by status
$status_counts = [
    'Received' => 0,
    'In Progress' => 0,
    'Waiting for Parts' => 0,
    'Fixed' => 0,
    'Ready for Pickup' => 0
];

$result = $conn->query("SELECT status, COUNT(*) as count FROM repairs GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
        }
    }
}

// Get total repair value
$total_repair_value = 0;
$result = $conn->query("SELECT SUM(repair_cost) as total FROM repairs");
if ($result && $row = $result->fetch_assoc()) {
    $total_repair_value = $row['total'] ?? 0;
}

// Get counts by device type
$device_counts = [];
$result = $conn->query("SELECT device_type, COUNT(*) as count FROM repairs GROUP BY device_type ORDER BY count DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $device_counts[$row['device_type']] = $row['count'];
    }
}

// Get recent repairs
$recent_repairs = [];
$result = $conn->query("SELECT * FROM repairs ORDER BY created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_object()) {
        $recent_repairs[] = $row;
    }
}

$conn->close();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Repair Status Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($status_counts as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $count; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td><strong>Total</strong></td>
                                    <td><strong><?php echo array_sum($status_counts); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Device Type Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="deviceChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Repair Summary</h5>
            </div>
            <div class="card-body">
                <div class="summary-stat">
                    <h3><?php echo array_sum($status_counts); ?></h3>
                    <p>Total Repairs</p>
                </div>
                <div class="summary-stat">
                    <h3>$<?php echo number_format($total_repair_value, 2); ?></h3>
                    <p>Total Repair Value</p>
                </div>
                <div class="summary-stat">
                    <h3><?php echo $status_counts['Ready for Pickup']; ?></h3>
                    <p>Ready for Pickup</p>
                </div>
                <div class="summary-stat">
                    <h3><?php echo $status_counts['In Progress'] + $status_counts['Waiting for Parts']; ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Repairs</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($recent_repairs as $repair): ?>
                    <a href="?view=edit&id=<?php echo $repair->id; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">#<?php echo htmlspecialchars($repair->service_note_number); ?></h6>
                            <small><?php echo date('M j', strtotime($repair->created_at)); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($repair->device_type . ' - ' . $repair->device_model); ?></p>
                        <small>
                            <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $repair->status)); ?>">
                                <?php echo htmlspecialchars($repair->status); ?>
                            </span>
                        </small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($status_counts)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($status_counts)); ?>,
                backgroundColor: [
                    '#95a5a6', // Received
                    '#3498db', // In Progress
                    '#f1c40f', // Waiting for Parts
                    '#2ecc71', // Fixed
                    '#1abc9c'  // Ready for Pickup
                ],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Device Chart
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');
    new Chart(deviceCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($device_counts)); ?>,
            datasets: [{
                label: 'Number of Repairs',
                data: <?php echo json_encode(array_values($device_counts)); ?>,
                backgroundColor: '#3498db',
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });
});
</script>

<style>
.summary-stat {
    text-align: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}
.summary-stat:last-child {
    border-bottom: none;
}
.summary-stat h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}
.summary-stat p {
    margin: 5px 0 0;
    color: #6c757d;
}
</style>