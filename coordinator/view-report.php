<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: history.php");
    exit();
}

$reportId = $_GET['id'];

// Get report details
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND coordinator_id = ?");
$stmt->execute([$reportId, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: history.php");
    exit();
}

require_once '../includes/header.php';
?>

<div class="view-report">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Report Details</h2>
        <button onclick="window.print()" class="btn no-print"><i class="fas fa-print"></i> Print</button>
    </div>
    
    <div class="report-header">
        <h3><?php echo htmlspecialchars($report['activity_title']); ?></h3>
        <p><strong>Status:</strong> 
            <?php if ($report['status'] === 'approved'): ?>
                <span class="status-badge approved">✅ Approved</span>
            <?php elseif ($report['status'] === 'rejected'): ?>
                <span class="status-badge rejected">❌ Rejected</span>
            <?php else: ?>
                <span class="status-badge pending">⏳ Pending</span>
            <?php endif; ?>
        </p>
        <p><strong>Date Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></p>
    </div>
    
    <div class="report-details">
        <div class="detail-row">
            <span class="detail-label">Department:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['department']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Location:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['location']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Beneficiaries:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['beneficiaries']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Date:</span>
            <span class="detail-value"><?php echo date('M d, Y', strtotime($report['date'])); ?></span>
        </div>
        
        <div class="detail-section">
            <h4>Activities:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['activities']); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Issues and Concerns:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['issues_concerns'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Recommendations:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['recommendations'] ?? 'N/A'); ?></p>
        </div>
        
        <?php if ($report['status'] === 'rejected' && !empty($report['admin_notes'])): ?>
            <div class="admin-notes no-print">
                <h4>Admin Feedback:</h4>
                <div class="notes-content">
                    <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
                </div>
                <a href="/coor-report/new-report.php?edit=<?php echo $report['id']; ?>" class="btn">Resubmit Report</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="actions no-print">
        <a href="history.php" class="btn">Back to History</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>