<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$designId = (int)$_GET['id'];

// Fetch program design with coordinator info
$stmt = $pdo->prepare("
    SELECT r.*, u.username, u.email
    FROM program_designs r
    JOIN users u ON r.coordinator_id = u.id
    WHERE r.id = ?
");
// Fetch rows from program_design_entries for this program design
$entriesStmt = $pdo->prepare("
    SELECT id, program, duration, objectives, persons_involved,
           resources_school, resources_community, collaborating_agencies,
           budget, mov, created_at
    FROM program_design_entries
    WHERE design_id = ?
    ORDER BY id ASC
");

$entriesStmt->execute([$designId]);
$entries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);




$stmt->execute([$designId]);
$design = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$design) {
    header("Location: reports.php");
    exit();
}

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action     = $_POST['action'];
    $adminNotes = $_POST['admin_notes'] ?? '';

    if (in_array($action, ['approve', 'reject', 'request_changes'], true)) {
        $status = $design['status']; // default to current
        if ($action === 'approve')         $status = 'approved';
        elseif ($action === 'reject')      $status = 'rejected';
        elseif ($action === 'request_changes') $status = 'pending';

        $upd = $pdo->prepare("
            UPDATE program_designs
               SET status = ?, admin_notes = ?, updated_at = NOW()
             WHERE id = ?
        ");
        $upd->execute([$status, $adminNotes, $designId]);

        header("Location: review-program-design.php?id=" . $designId);
        exit();
    }
}

require_once '../includes/header.php';
?>
<div class="review-report">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;" class="no-print">
      <h2>Review Program Design</h2>
      <?php if ($design['status'] === 'approved'): ?>
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
      <?php endif; ?>
  </div>

  <!-- Screen view -->
  <div class="report-details no-print">
      <h3><?= htmlspecialchars($design['activity_title']) ?></h3>

      <p><strong>Coordinator:</strong> <?= htmlspecialchars($design['username']) ?></p>
      <p><strong>Department:</strong> <?= htmlspecialchars($design['department'] ?? '—') ?></p>
      <p><strong>Location:</strong> <?= htmlspecialchars($design['location'] ?? '—') ?></p>
      <p><strong>Status:</strong>
          <?php if (($design['status'] ?? '') === 'approved'): ?>
              <span class="status-badge approved">✅ Approved</span>
          <?php elseif (($design['status'] ?? '') === 'rejected'): ?>
              <span class="status-badge rejected">❌ Rejected</span>
          <?php else: ?>
              <span class="status-badge pending">⏳ Pending</span>
          <?php endif; ?>
      </p>
      <p><strong>Date Submitted:</strong> <?= !empty($design['created_at']) ? date('M d, Y H:i', strtotime($design['created_at'])) : '—' ?></p>

      <h4>Participants / Target Audience</h4>
      <p style="white-space:pre-wrap;"><?= htmlspecialchars($design['participants'] ?? 'N/A') ?></p>

  <h4>Program Design Entries</h4>
<?php if (empty($entries)): ?>
  <p>No entries added.</p>
<?php else: ?>
  <div style="overflow:auto;">
    <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e9ecef;">
      <thead>
        <tr style="background:#f3f6fb;color:#192f5d;">
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">#</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Program</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Duration</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Objectives</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Persons Involved</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Resources (School)</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Resources (Community)</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Collaborating Agencies</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">Budget</th>
          <th style="padding:10px;border-bottom:1px solid #e9ecef;">MOV</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $i => $row): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;"><?= $i+1 ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;"><?= htmlspecialchars($row['program'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;"><?= htmlspecialchars($row['duration'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['objectives'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['persons_involved'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['resources_school'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['resources_community'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['collaborating_agencies'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;">
              <?php
                $b = $row['budget'] ?? '';
                echo is_numeric($b) ? '₱ '.number_format((float)$b, 2) : htmlspecialchars((string)$b);
              ?>
            </td>
            <td style="padding:10px;border-bottom:1px solid #e9ecef;white-space:pre-wrap;"><?= htmlspecialchars($row['mov'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

      <?php if (($design['status'] ?? '') === 'rejected' && !empty($design['admin_notes'])): ?>
        <div class="admin-notes">
            <h4>Admin Notes:</h4>
            <div class="notes-content"><?= nl2br(htmlspecialchars($design['admin_notes'])) ?></div>
        </div>
      <?php endif; ?>
  </div>

  <!-- Print view (approved only) -->
  <div class="print-view" style="display:none;">
      <h1 style="text-align:center;margin-bottom:24px;">PROGRAM DESIGN</h1>

      <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
          <tr>
              <td style="width:220px;font-weight:bold;border-bottom:1px solid #000;">Department</td>
              <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($design['department'] ?? '—') ?></td>
          </tr>
          <tr>
              <td style="font-weight:bold;border-bottom:1px solid #000;">Activity Title</td>
              <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($design['activity_title']) ?></td>
          </tr>
          <tr>
              <td style="font-weight:bold;border-bottom:1px solid #000;">Location</td>
              <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($design['location'] ?? '—') ?></td>
          </tr>
          <tr>
              <td style="font-weight:bold;border-bottom:1px solid #000;">Coordinator</td>
              <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($design['username']) ?></td>
          </tr>
          <tr>
              <td style="font-weight:bold;border-bottom:1px solid #000;">Date Submitted</td>
              <td style="border-bottom:1px solid #000;"><?= !empty($design['created_at']) ? date('M d, Y', strtotime($design['created_at'])) : '—' ?></td>
          </tr>
      </table>

      <h3 style="margin:18px 0 8px;">Participants / Target Audience</h3>
      <p style="white-space:pre-wrap;margin-left:16px;"><?= htmlspecialchars($design['participants'] ?? 'N/A') ?></p>

      <div style="margin-top:50px;">
          <p>Prepared by:</p>
          <p style="margin-top:50px;">_________________________</p>
          <p>CES Coordinator</p>
      </div>

      <div style="margin-top:40px;display:flex;justify-content:space-between;">
          <div>
              <p>Noted by:</p>
              <p style="margin-top:50px;">_________________________</p>
              <p>Dean</p>
          </div>
          <div>
              <p style="visibility:hidden;">Noted by:</p>
              <p>RHEAN L. SANCHEZ, Ed.D</p>
              <p>CES Head</p>
          </div>
      </div>

      <div style="margin-top:40px;">
          <p>Recommending Approval:</p>
          <p style="margin-top:30px;">BEVERLY D. JAMINAL, Ed.D</p>
          <p>Vice-President for Academic Affairs and Research</p>

          <p style="margin-top:30px;">REV. FR. EULOSIO C. JUNIO, CCB</p>
          <p>Vice-President for Administrative Affairs</p>
      </div>

      <div style="margin-top:40px;">
          <p>Approved by:</p>
          <p style="margin-top:30px;">REV. FR. RONNEL BABANO, STL</p>
          <p>School President</p>
      </div>
  </div>

  <?php if (($design['status'] ?? '') === 'pending'): ?>
  <form method="POST" class="review-form no-print" style="margin-top:18px;">
      <div class="form-group">
          <label for="admin_notes">Admin Notes:</label>
          <textarea id="admin_notes" name="admin_notes" rows="4"><?= htmlspecialchars($design['admin_notes'] ?? '') ?></textarea>
      </div>
      <div class="action-buttons">
          <button type="submit" name="action" value="approve" class="btn approve">✅ Approve</button>
          <button type="submit" name="action" value="request_changes" class="btn request-changes">✏️ Request Changes</button>
          <button type="submit" name="action" value="reject" class="btn reject">❌ Reject</button>
      </div>
  </form>
  <?php elseif (!empty($design['admin_notes'])): ?>
      <div class="admin-notes no-print" style="margin-top:16px;">
          <h4>Admin Notes:</h4>
          <div class="notes-content"><?= nl2br(htmlspecialchars($design['admin_notes'])) ?></div>
      </div>
  <?php endif; ?>
</div>

<style>
@media print{
  .no-print{ display:none !important; }
  .print-view{ display:block !important; }
  body{ font-family:Arial, sans-serif; line-height:1.5; padding:20px; }
}

/* Screen styles (match your other review pages) */
.review-report{ max-width:1000px; margin:0 auto; padding:20px; }
.report-details{
  background:#fff; padding:20px; border-radius:8px;
  box-shadow:0 0 10px rgba(0,0,0,.08);
}
.form-group{ margin:12px 0; }
.form-group label{ display:block; margin-bottom:6px; font-weight:700; }
.form-group textarea{
  width:100%; min-height:110px; border:1px solid #ddd; border-radius:6px; padding:10px;
}

.action-buttons{ display:flex; gap:10px; margin-top:14px; }
.btn{ padding:10px 14px; border:none; border-radius:6px; cursor:pointer; font-size:14px; }
.btn.approve{ background:#4CAF50; color:#fff; }
.btn.request-changes{ background:#FFC107; color:#000; }
.btn.reject{ background:#F44336; color:#fff; }

.status-badge{ padding:4px 10px; border-radius:6px; font-size:14px; font-weight:700; }
.status-badge.approved{ background:#d4edda; color:#155724; }
.status-badge.rejected{ background:#f8d7da; color:#721c24; }
.status-badge.pending{ background:#fff3cd; color:#8a6d3b; }

.admin-notes{
  background:#fff3cd; padding:16px; border-left:4px solid #ffc107;
  border-radius:6px; margin-top:18px;
}
.admin-notes .notes-content{
  background:#fff; border:1px solid #ffeaa7; padding:12px; border-radius:4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if (($design['status'] ?? '') === 'approved'): ?>
  // Swap to print view only while printing
  window.addEventListener('beforeprint', function(){
    document.querySelector('.print-view').style.display = 'block';
    document.querySelector('.report-details').style.display = 'none';
  });
  window.addEventListener('afterprint', function(){
    document.querySelector('.print-view').style.display = 'none';
    document.querySelector('.report-details').style.display = 'block';
  });
  <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>
