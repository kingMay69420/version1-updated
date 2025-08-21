<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

// Check if a report has been filed for the current month to show a notification
$showNotification = false;
$currentYear = date('Y');
$currentMonth = date('m');

$stmt_check = $pdo->prepare("SELECT id FROM reports WHERE coordinator_id = ? AND YEAR(date) = ? AND MONTH(date) = ?");
$stmt_check->execute([$_SESSION['user_id'], $currentYear, $currentMonth]);
$reportForCurrentMonth = $stmt_check->fetch();

if (!$reportForCurrentMonth) {
    $showNotification = true;
}

// Get coordinator's reports
$stmt = $pdo->prepare("SELECT * FROM reports WHERE coordinator_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$reports = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="coordinator-dashboard">
    <h2>Coordinator Dashboard</h2>
    
    <?php if ($showNotification): ?>
        <div class="alert alert-info">
            <p><strong>Reminder:</strong> You have not yet filed your accomplishment report for <?php echo date('F Y'); ?>. Please <a href="new-report.php">file your report</a>.</p>
        </div>
    <?php endif; ?>
    
  <a href="new-report.php" class="card-button">
  <div class="card-icon">
    <i class="fas fa-plus-circle"></i>
  </div>
  <div class="card-content">
    <h3>Create New Report</h3>
    <p>Start a new monthly accomplishment report</p>
  </div>
</a>
    
    <section class="recent-reports">
        <h3>Recent Reports</h3>
        <?php if (empty($reports)): ?>
            <p>No reports filed yet.</p>
        <?php else: ?>
            <div class="reports-grid">
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <h4><?php echo htmlspecialchars($report['activity_title']); ?></h4>
                        <p>Department: <?php echo htmlspecialchars($report['department']); ?></p>
                        <p>Date: <?php echo date('M d, Y', strtotime($report['date'])); ?></p>
                        <p>Status: 
                            <?php if ($report['status'] === 'approved'): ?>
                                <span class="status-badge approved">✅ Approved</span>
                            <?php elseif ($report['status'] === 'rejected'): ?>
                                <span class="status-badge rejected">❌ Rejected</span>
                            <?php else: ?>
                                <span class="status-badge pending">⏳ Pending</span>
                            <?php endif; ?>
                        </p>
                        <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn-small">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="history.php" class="btn">View All Reports</a>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>