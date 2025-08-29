<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: history.php");
    exit();
}

$reportId = $_GET['id'];

// Get community needs assessment report details
$stmt = $pdo->prepare("SELECT * FROM community_needs_reports WHERE id = ? AND coordinator_id = ?");
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
        <h2>Community Needs Assessment Report</h2>
        <button onclick="window.print()" class="btn no-print"><i class="fas fa-print"></i> Print</button>
    </div>
    
    <div class="report-header">
        <h3>Participant: <?php echo htmlspecialchars($report['participant_name']); ?></h3>
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
            <span class="detail-label">Assessment Date:</span>
            <span class="detail-value"><?php echo date('M d, Y', strtotime($report['assessment_date'])); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Location:</span>
            <span class="detail-value"><?php echo htmlspecialchars($report['location']); ?></span>
        </div>
        
        <div class="detail-section">
            <h4>Family Profile:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['family_profile']); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Community Concerns:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['community_concerns'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Other Identified Needs:</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['other_needs'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="detail-section">
            <h4>Identified Prevailing Needs:</h4>
            
            <div class="sub-section">
                <h5>Kabayani ng Buhay:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['kabayani_buhay']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Panginoon:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['kabayani_panginoon']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kalikasan:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['kabayani_kalikasan']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kultura:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['kabayani_kultura']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Turismo:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['kabayani_turismo']); ?></p>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>Recommended Outreach Programs:</h4>
            
            <div class="sub-section">
                <h5>Kabayani ng Buhay:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['program_buhay']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Panginoon:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['program_panginoon']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kalikasan:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['program_kalikasan']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kultura:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['program_kultura']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Turismo:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['program_turismo']); ?></p>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>Allocation of Resources:</h4>
            
            <div class="sub-section">
                <h5>Kabayani ng Buhay:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['resources_buhay']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Panginoon:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['resources_panginoon']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kalikasan:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['resources_kalikasan']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Kultura:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['resources_kultura']); ?></p>
            </div>
            
            <div class="sub-section">
                <h5>Kabayani ng Turismo:</h5>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['resources_turismo']); ?></p>
            </div>
        </div>
        
        <?php if ($report['status'] === 'rejected' && !empty($report['admin_notes'])): ?>
            <div class="admin-notes no-print">
                <h4>Admin Feedback:</h4>
                <div class="notes-content">
                    <?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?>
                </div>
                <a href="/coor-report/cnacr.php?edit=<?php echo $report['id']; ?>" class="btn">Resubmit Assessment</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="actions no-print">
        <a href="history.php" class="btn">Back to History</a>
    </div>
</div>

<style>
    .view-report {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    .report-header {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }
    
    .report-details {
        padding: 0 20px;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .detail-label {
        font-weight: bold;
        width: 150px;
        color: #555;
    }
    
    .detail-value {
        flex: 1;
    }
    
    .detail-section {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #fafafa;
        border-radius: 5px;
        border-left: 3px solid #28a745;
    }
    
    .detail-section h4 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }
    
    .sub-section {
        margin-bottom: 20px;
        padding: 15px;
        background-color: white;
        border-radius: 5px;
        border-left: 2px solid #6c757d;
    }
    
    .sub-section h5 {
        margin-top: 0;
        color: #495057;
        font-size: 16px;
    }
    
    .admin-notes {
        background-color: #fff3cd;
        padding: 20px;
        border-radius: 5px;
        border-left: 4px solid #ffc107;
        margin-top: 30px;
    }
    
    .admin-notes h4 {
        margin-top: 0;
        color: #856404;
    }
    
    .notes-content {
        background-color: white;
        padding: 15px;
        border-radius: 3px;
        margin-bottom: 15px;
        border: 1px solid #ffeaa7;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .status-badge.approved {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        margin-right: 10px;
    }
    
    .btn:hover {
        background-color: #0056b3;
    }
    
    .actions {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    @media print {
        .no-print {
            display: none !important;
        }
        .view-report {
            box-shadow: none;
            padding: 0;
        }
        .detail-section, .sub-section {
            page-break-inside: avoid;
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>