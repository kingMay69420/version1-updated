<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

// Get pending reports
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reports r JOIN users u ON r.coordinator_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
$stmt->execute();
$pendingReports = $stmt->fetchAll();

// Get all reports
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reports r JOIN users u ON r.coordinator_id = u.id ORDER BY r.created_at DESC LIMIT 10");
$stmt->execute();
$allReports = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-dashboard">
    <h2>Admin Dashboard</h2>
    
    <section class="pending-reports">
        <h3>Pending Reports</h3>
        <?php if (empty($pendingReports)): ?>
            <p>No pending reports.</p>
        <?php else: ?>
            <div class="reports-grid">
                <?php foreach ($pendingReports as $report): ?>
                    <div class="report-card">
                        <h4><?php echo htmlspecialchars($report['activity_title']); ?></h4>
                        <p>From: <?php echo htmlspecialchars($report['username']); ?></p>
                        <p>Department: <?php echo htmlspecialchars($report['department']); ?></p>
                        <p>Date: <?php echo date('M d, Y', strtotime($report['date'])); ?></p>
                        <a href="review-report.php?id=<?php echo $report['id']; ?>" class="btn">Review</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    
    <section class="all-reports">
        <h3>Recent Reports</h3>
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
                    <?php foreach ($allReports as $report): ?>
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
                                <a href="review-report.php?id=<?php echo $report['id']; ?>" class="btn-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>