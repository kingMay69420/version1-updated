<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

// Get all coordinator's reports
$stmt = $pdo->prepare("SELECT * FROM reports WHERE coordinator_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$reports = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="report-history">
    <h2>Report History</h2>
    
    <?php if (empty($reports)): ?>
        <p>No reports filed yet.</p>
    <?php else: ?>
        <div class="reports-table">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['activity_title']); ?></td>
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
                                <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn-small">View</a>
                                <?php if ($report['status'] === 'rejected'): ?>
                                    <a href="new-report.php?edit=<?php echo $report['id']; ?>" class="btn-small">Resubmit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>