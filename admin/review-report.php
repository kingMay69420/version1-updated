<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$reportId = $_GET['id'];

// Get report details
$stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.coordinator_id = u.id WHERE r.id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    if (in_array($action, ['approve', 'reject', 'request_changes'])) {
        $status = '';
        switch ($action) {
            case 'approve':
                $status = 'approved';
                break;
            case 'reject':
                $status = 'rejected';
                break;
            case 'request_changes':
                $status = 'pending';
                break;
        }
        
        $stmt = $pdo->prepare("UPDATE reports SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $adminNotes, $reportId]);
        
        header("Location: review-report.php?id=" . $reportId);
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="review-report">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;" class="no-print">
        <h2>Review Report</h2>
        <?php if ($report['status'] === 'approved'): ?>
            <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Report</button>
        <?php endif; ?>
    </div>
    
    <!-- Regular View -->
    <div class="report-details no-print">
        <h3><?php echo htmlspecialchars($report['activity_title']); ?></h3>
        <p><strong>Coordinator:</strong> <?php echo htmlspecialchars($report['username']); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($report['department']); ?></p>
        <p><strong>Status:</strong> 
            <?php if ($report['status'] === 'approved'): ?>
                <span class="status-badge approved">✅ Approved</span>
            <?php elseif ($report['status'] === 'rejected'): ?>
                <span class="status-badge rejected">❌ Rejected</span>
            <?php else: ?>
                <span class="status-badge pending">⏳ Pending</span>
            <?php endif; ?>
        </p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($report['location']); ?></p>
        <p><strong>Beneficiaries:</strong> <?php echo htmlspecialchars($report['beneficiaries']); ?></p>
        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($report['date'])); ?></p>
        
        <h4>Activities:</h4>
        <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['activities']); ?></p>
        
        <h4>Issues and Concerns:</h4>
        <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['issues_concerns'] ?? 'N/A'); ?></p>
        
        <h4>Recommendations:</h4>
        <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['recommendations'] ?? 'N/A'); ?></p>
    </div>
    
    <!-- Print View (only shown when printing approved reports) -->
    <div class="print-view" style="display: none;">
        <h1 style="text-align: center; margin-bottom: 30px;">MONTHLY ACCOMPLISHMENT REPORT</h1>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="width: 150px; font-weight: bold; border-bottom: 1px solid #000;">Department</td>
                <td style="border-bottom: 1px solid #000;"><?php echo htmlspecialchars($report['department']); ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold; border-bottom: 1px solid #000;">Title of Activity</td>
                <td style="border-bottom: 1px solid #000;"><?php echo htmlspecialchars($report['activity_title']); ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold; border-bottom: 1px solid #000;">Location</td>
                <td style="border-bottom: 1px solid #000;"><?php echo htmlspecialchars($report['location']); ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold; border-bottom: 1px solid #000;">Beneficiaries</td>
                <td style="border-bottom: 1px solid #000;"><?php echo htmlspecialchars($report['beneficiaries']); ?></td>
            </tr>
        </table>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Date</th>
                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Activities</th>
                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Status</th>
                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Issues and Concerns</th>
                    <th style="border: 1px solid #000; padding: 8px; text-align: left;">Recommendations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; padding: 8px;"><?php echo date('M d, Y', strtotime($report['date'])); ?></td>
                    <td style="border: 1px solid #000; padding: 8px; white-space: pre-wrap;"><?php echo htmlspecialchars($report['activities']); ?></td>
                    <td style="border: 1px solid #000; padding: 8px;">Approved</td>
                    <td style="border: 1px solid #000; padding: 8px; white-space: pre-wrap;"><?php echo htmlspecialchars($report['issues_concerns'] ?? 'N/A'); ?></td>
                    <td style="border: 1px solid #000; padding: 8px; white-space: pre-wrap;"><?php echo htmlspecialchars($report['recommendations'] ?? 'N/A'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 50px;">
            <p>Prepared by:</p>
            <p style="margin-top: 50px;">_________________________</p>
            <p>CES Coordinator</p>
        </div>
        
        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div>
                <p>Noted by:</p>
                <p style="margin-top: 50px;">_________________________</p>
                <p>Dean</p>
            </div>
            <div>
                <p style="visibility: hidden;">Noted by:</p>
                <p>RHEAN L. SANCHEZ, Ed.D</p>
                <p>CES Head</p>
            </div>
        </div>
        
        <div style="margin-top: 50px;">
            <p>Recommending Approval:</p>
            <p style="margin-top: 30px;">BEVERLY D. JAMINAL, Ed.D</p>
            <p>Vice-President for Academic Affairs and Research</p>
            
            <p style="margin-top: 30px;">REV. FR. EULOSIO C. JUNIO, CCB</p>
            <p>Vice-President for Administrative Affairs</p>
        </div>
        
        <div style="margin-top: 50px;">
            <p>Approved by:</p>
            <p style="margin-top: 30px;">REV. FR. RONNEL BABANO, STL</p>
            <p>School President</p>
        </div>
    </div>
    
    <?php if ($report['status'] === 'pending'): ?>
    <form method="POST" class="review-form no-print">
        <div class="form-group">
            <label for="admin_notes">Admin Notes:</label>
            <textarea id="admin_notes" name="admin_notes" rows="4"><?php echo htmlspecialchars($report['admin_notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="action-buttons">
            <button type="submit" name="action" value="approve" class="btn approve">✅ Approve</button>
            <button type="submit" name="action" value="request_changes" class="btn request-changes">✏️ Request Changes</button>
            <button type="submit" name="action" value="reject" class="btn reject">❌ Reject</button>
        </div>
    </form>
    <?php elseif (!empty($report['admin_notes'])): ?>
        <div class="admin-notes no-print">
            <h4>Admin Notes:</h4>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .print-view {
            display: block !important;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.5;
            padding: 20px;
        }
        table {
            page-break-inside: avoid;
        }
    }
    
    /* Regular screen styles */
    .review-report {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .report-details {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-height: 100px;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }
    .btn.approve {
        background-color: #4CAF50;
        color: white;
    }
    .btn.request-changes {
        background-color: #FFC107;
        color: black;
    }
    .btn.reject {
        background-color: #F44336;
        color: white;
    }
    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 14px;
    }
    .status-badge.approved {
        background-color: #DFF0D8;
        color: #3C763D;
    }
    .status-badge.rejected {
        background-color: #F2DEDE;
        color: #A94442;
    }
    .status-badge.pending {
        background-color: #FCF8E3;
        color: #8A6D3B;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show print view when printing (only for approved reports)
    <?php if ($report['status'] === 'approved'): ?>
    window.addEventListener('beforeprint', function() {
        document.querySelector('.print-view').style.display = 'block';
        document.querySelector('.report-details').style.display = 'none';
    });
    
    window.addEventListener('afterprint', function() {
        document.querySelector('.print-view').style.display = 'none';
        document.querySelector('.report-details').style.display = 'block';
    });
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>