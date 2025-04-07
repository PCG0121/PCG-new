<?php
// Check direct access
if(!defined('BASEPATH')) exit('No direct script access allowed');

// Get repairs from database
$conn = connectDB();
$repairs = [];
$result = $conn->query("SELECT * FROM repairs ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_object()) {
        $repairs[] = $row;
    }
}
$conn->close();
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
                            <td>
                                <span class="badge status-<?php echo strtolower(str_replace(' ', '-', $repair->status)); ?>">
                                    <?php echo htmlspecialchars($repair->status); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($repair->repair_cost, 2); ?></td>
                            <td>
                                <a href="?view=edit&id=<?php echo $repair->id; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="deleteRepair(<?php echo $repair->id; ?>)">Delete</button>
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

<script>
function deleteRepair(id) {
    if (confirm('Are you sure you want to delete this repair?')) {
        window.location.href = 'index.php?action=delete&id=' + id;
    }
}
</script>