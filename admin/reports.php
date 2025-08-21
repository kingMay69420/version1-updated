<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

// Get filter parameters
$start_date  = $_GET['start_date']  ?? '';
$end_date    = $_GET['end_date']    ?? '';
$department  = $_GET['department']  ?? '';
$status      = $_GET['status']      ?? '';

// Build query with filters
$query = "SELECT r.*, u.username 
          FROM reports r 
          JOIN users u ON r.coordinator_id = u.id 
          WHERE 1=1";
$params = [];

// Date filter (only if provided)
if (!empty($start_date)) {
    $query .= " AND r.date >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND r.date <= ?";
    $params[] = $end_date;
}

// Department filter
if (!empty($department)) {
    $query .= " AND r.department = ?";
    $params[] = $department;
}

// Status filter
if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique departments for dropdown
$deptStmt = $pdo->query("SELECT DISTINCT department FROM reports ORDER BY department");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

require_once '../includes/header.php';
?>

<div class="admin-reports">
    <h2>Reports Management</h2>
    
    <div class="filter-controls">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            
            <div class="form-group">
                <label for="department">Department:</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                            <?php echo $department === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"  <?php echo $status === 'pending'  ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <button type="submit" class="btn">Apply Filters</button>
            <a href="reports.php" class="btn">Clear Filters</a>
        </form>
    </div>
    
    <div class="reports-table">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Coordinator</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['activity_title']); ?></td>
                            <td><?php echo htmlspecialchars($report['username']); ?></td>
                            <td><?php echo htmlspecialchars($report['department']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($report['date'])); ?></td>
                            <td>
                                <?php if ($report['status'] === 'approved'): ?>
                                    <span class="status-badge approved">✅ Approved</span>
                                <?php elseif ($report['status'] === 'rejected'): ?>
                                    <span class="status-badge rejected">❌ Rejected</span>
                                <?php else: ?>
                                    <span class="status-badge pending">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="review-report.php?id=<?php echo $report['id']; ?>" class="btn-small">Review</a>
    
                                 <a href="delete-report.php?id=<?php echo $report['id']; ?>" 
                                class="btn-small danger" 
                                onclick="return confirm('Are you sure you want to delete this report?');">
                                Delete
                                </a>
                            </td>

                            
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No reports found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
