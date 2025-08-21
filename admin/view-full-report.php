<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$reportId = $_GET['id'];

// Get report details
$stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.coordinator_id = u.id WHERE r.id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: reports.php");
    exit();
}

// Generate HTML report
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report: <?php echo htmlspecialchars($report['activity_title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #2c3e50; }
        .report-header { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .detail-row { margin-bottom: 10px; }
        .detail-label { font-weight: bold; width: 150px; display: inline-block; }
        .detail-section { margin-bottom: 20px; }
        .detail-section h4 { margin-bottom: 5px; color: #34495e; }
        .detail-section p { padding-left: 15px; border-left: 3px solid #3498db; }
        .footer { margin-top: 30px; text-align: right; font-style: italic; }
    </style>
</head>
<body>
    <div class="report-header">
        <h1><?php echo htmlspecialchars($report['activity_title']); ?></h1>
        <p><strong>Status:</strong> 
            <?php if ($report['status'] === 'approved'): ?>
                Approved
            <?php elseif ($report['status'] === 'rejected'): ?>
                Rejected
            <?php else: ?>
                Pending
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
            <span class="detail-label">Coordinator:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['username']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['email']); ?></span>
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
            <p><?php echo nl2br(htmlspecialchars($report['activities'])); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Issues and Concerns:</h4>
            <p><?php echo nl2br(htmlspecialchars($report['issues_concerns'])); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Recommendations:</h4>
            <p><?php echo nl2br(htmlspecialchars($report['recommendations'])); ?></p>
        </div>
        
        <?php if (!empty($report['admin_notes'])): ?>
            <div class="detail-section">
                <h4>Admin Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>Generated on: <?php echo date('M d, Y H:i:s'); ?></p>
    </div>
</body>
</html>
<?php
$html_content = ob_get_clean();

// Output the report
echo $html_content;
?>