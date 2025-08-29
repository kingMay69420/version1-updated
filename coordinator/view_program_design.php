<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: history.php");
    exit();
}

$designId = (int)$_GET['id'];

// Get the program design (only if it belongs to this coordinator)
$stmt = $pdo->prepare("
    SELECT d.*
    FROM program_designs d
    WHERE d.id = ? AND d.coordinator_id = ?
");
$stmt->execute([$designId, $_SESSION['user_id']]);
$design = $stmt->fetch();

if (!$design) {
    header("Location: history.php");
    exit();
}

// Fetch entries
$entriesStmt = $pdo->prepare("
    SELECT *
    FROM program_design_entries
    WHERE design_id = ?
    ORDER BY id ASC
");
$entriesStmt->execute([$designId]);
$entries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="view-report">
    <div style="display:flex; justify-content:space-between; align-items:center;" class="no-print">
        <h2>Program Design</h2>
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
    </div>

    <div class="report-header">
        <h3><?php echo htmlspecialchars($design['activity_title']); ?></h3>
        <p>
            <strong>Status:</strong>
            <?php if (($design['status'] ?? 'pending') === 'approved'): ?>
                <span class="status-badge approved">✅ Approved</span>
            <?php elseif (($design['status'] ?? 'pending') === 'rejected'): ?>
                <span class="status-badge rejected">❌ Rejected</span>
            <?php else: ?>
                <span class="status-badge pending">⏳ Pending</span>
            <?php endif; ?>
        </p>
        <p><strong>Date Submitted:</strong> <?php echo !empty($design['created_at']) ? date('M d, Y H:i', strtotime($design['created_at'])) : '—'; ?></p>
    </div>

    <div class="report-details">
        <div class="detail-row">
            <span class="detail-label">Department:</span>
            <span class="detail-value"><?php echo htmlspecialchars($design['department'] ?? '—'); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Location:</span>
            <span class="detail-value"><?php echo htmlspecialchars($design['location'] ?? '—'); ?></span>
        </div>

        <div class="detail-section">
            <h4>Participants</h4>
            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($design['participants'] ?? '—'); ?></p>
        </div>

        <div class="detail-section">
          <h4>Program Design Entries</h4>

          <?php if (count($entries) === 0): ?>
              <p>No entries added.</p>
          <?php else: ?>
              <div class="table-wrap">
                  <table class="entries-table">
                      <thead>
                          <tr>
                              <th>#</th>
                              <th>Program</th>
                              <th>Duration</th>
                              <th>Objectives</th>
                              <th>Persons Involved</th>
                              <th>Resources (School)</th>
                              <th>Resources (Community)</th>
                              <th>Collaborating Agencies</th>
                              <th>Budget</th>
                              <th>MOV</th>
                          </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($entries as $i => $row): ?>
                          <tr>
                              <td><?= $i + 1 ?></td>
                              <td><?= htmlspecialchars($row['program'] ?? '') ?></td>
                              <td><?= htmlspecialchars($row['duration'] ?? '') ?></td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['objectives'] ?? '') ?></td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['persons_involved'] ?? '') ?></td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['resources_school'] ?? '') ?></td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['resources_community'] ?? '') ?></td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['collaborating_agencies'] ?? '') ?></td>
                              <td>
                                  <?php
                                    $b = $row['budget'] ?? '';
                                    echo is_numeric($b) ? '₱ ' . number_format((float)$b, 2) : htmlspecialchars((string)$b);
                                  ?>
                              </td>
                              <td style="white-space:pre-wrap;"><?= htmlspecialchars($row['mov'] ?? '') ?></td>
                          </tr>
                      <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          <?php endif; ?>
      </div>


        <?php if (($design['status'] ?? '') === 'rejected' && !empty($design['admin_notes'])): ?>
            <div class="admin-notes no-print">
                <h4>Admin Feedback</h4>
                <div class="notes-content">
                    <?php echo nl2br(htmlspecialchars($design['admin_notes'])); ?>
                </div>
                <a href="/coor-report/program_design_form.php?edit=<?php echo $design['id']; ?>" class="btn">Resubmit Program Design</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="actions no-print">
        <a href="history.php?report_type=design" class="btn">Back to History</a>
    </div>
</div>

<style>
.view-report{
    max-width:1000px; margin:0 auto; padding:20px;
    background:#fff; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,.08);
}
.report-header{
    background:#f8f9fa; padding:20px; border-radius:6px; margin-bottom:20px;
    border-left:4px solid #007bff;
}
.report-details{ padding:0 4px; }
.detail-row{
    display:flex; gap:10px; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #eee;
}
.detail-label{ width:180px; font-weight:700; color:#555; }
.detail-value{ flex:1; }

.detail-section{
    margin:22px 0; padding:16px; background:#fafafa;
    border-left:3px solid #28a745; border-radius:6px;
}
.detail-section h4{
    margin:0 0 10px; color:#2c3e50; border-bottom:2px solid #eee; padding-bottom:8px;
}

.table-wrap{ overflow:auto; }
.entries-table{
    width:100%; border-collapse:collapse; background:#fff; border:1px solid #e9ecef; border-radius:6px; overflow:hidden;
}
.entries-table th, .entries-table td{
    border-bottom:1px solid #e9ecef; padding:10px; text-align:left; vertical-align:top;
}
.entries-table thead th{
    background:#f3f6fb; color:#192f5d; position:sticky; top:0; z-index:1;
}
.entries-table tr:nth-child(even) td{ background:#fbfdff; }

/* badges + buttons (match your existing style) */
.status-badge{ padding:4px 10px; border-radius:6px; font-weight:700; font-size:14px; }
.status-badge.approved{ background:#d4edda; color:#155724; }
.status-badge.rejected{ background:#f8d7da; color:#721c24; }
.status-badge.pending{ background:#fff3cd; color:#8a6d3b; }

.btn{
    display:inline-block; padding:10px 16px; background:#007bff; color:#fff; text-decoration:none;
    border-radius:6px; border:none; cursor:pointer;
}
.btn:hover{ background:#0056b3; }

.actions{ margin-top:20px; padding-top:12px; border-top:1px solid #eee; }

.admin-notes{
    background:#fff3cd; padding:16px; border-radius:6px; border-left:4px solid #ffc107; margin-top:20px;
}
.admin-notes h4{ margin:0 0 10px; color:#856404; }
.notes-content{
    background:#fff; border:1px solid #ffeaa7; border-radius:4px; padding:12px; margin-bottom:12px;
}

@media print{
    .no-print{ display:none !important; }
    .view-report{ box-shadow:none; padding:0; }
    .detail-section, .table-wrap{ page-break-inside: avoid; }
}

/* Mobile tweaks */
@media (max-width: 640px){
    .detail-row{ flex-direction:column; }
    .detail-label{ width:auto; }
}
</style>

<?php require_once '../includes/footer.php'; ?>
