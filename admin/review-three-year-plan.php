<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$planId = $_GET['id'];

// Fetch plan with coordinator info
// Assumes: three_year_development_plans has columns like
// project_title, project_description, general_objectives, program_justification,
// beneficiaries, program_plan, status (optional), admin_notes (optional), department (optional), created_at
$stmt = $pdo->prepare("SELECT r.*, u.username, u.email
                       FROM three_year_development_plans r
                       JOIN users u ON r.coordinator_id = u.id
                       WHERE r.id = ?");
$stmt->execute([$planId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header("Location: reports.php");
    exit();
}

// Fetch activities for this plan
$act = $pdo->prepare("SELECT program, milestones, objectives, strategies, persons_agencies_involved,
                             resources_needed, budget, time_frame, remarks
                      FROM development_plan_activities
                      WHERE plan_id = ?
                      ORDER BY id ASC");
$act->execute([$planId]);
$activities = $act->fetchAll(PDO::FETCH_ASSOC);

// Handle admin action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $adminNotes = $_POST['admin_notes'] ?? '';

    if (in_array($action, ['approve', 'reject', 'request_changes'], true)) {
        $status = $plan['status'] ?? 'pending';
        if ($action === 'approve')        $status = 'approved';
        elseif ($action === 'reject')     $status = 'rejected';
        elseif ($action === 'request_changes') $status = 'pending';

        // If status/admin_notes columns don't exist yet, add them to your table or remove from query.
        $upd = $pdo->prepare("UPDATE three_year_development_plans
                              SET status = ?, admin_notes = ?, updated_at = NOW()
                              WHERE id = ?");
        $upd->execute([$status, $adminNotes, $planId]);

        header("Location: review-three-year-plan.php?id=".$planId);
        exit();
    }
}

require_once '../includes/header.php';
?>
<div class="review-report">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;" class="no-print">
    <h2>Review 3-Year Development Plan</h2>
    <?php if (($plan['status'] ?? 'pending') === 'approved'): ?>
      <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print Plan</button>
    <?php endif; ?>
  </div>

  <!-- Regular View -->
  <div class="report-details no-print">
    <h3>Project: <?= htmlspecialchars($plan['project_title']) ?></h3>

    <p><strong>Coordinator:</strong> <?= htmlspecialchars($plan['username']) ?> (<?= htmlspecialchars($plan['email']) ?>)</p>
    <p><strong>Department:</strong> <?= htmlspecialchars($plan['department'] ?? '—') ?></p>
    <p><strong>Status:</strong>
      <?php $status = $plan['status'] ?? 'pending'; ?>
      <?php if ($status === 'approved'): ?>
        <span class="status-badge approved">✅ Approved</span>
      <?php elseif ($status === 'rejected'): ?>
        <span class="status-badge rejected">❌ Rejected</span>
      <?php else: ?>
        <span class="status-badge pending">⏳ Pending</span>
      <?php endif; ?>
    </p>
    <p><strong>Date Submitted:</strong> <?= !empty($plan['created_at']) ? date('M d, Y H:i', strtotime($plan['created_at'])) : '—' ?></p>

    <h4>Description of the Project/Program</h4>
    <p style="white-space:pre-wrap;"><?= htmlspecialchars($plan['project_description']) ?></p>

    <h4>General Objectives</h4>
    <p style="white-space:pre-wrap;"><?= htmlspecialchars($plan['general_objectives']) ?></p>

    <h4>Program Justification</h4>
    <p style="white-space:pre-wrap;"><?= htmlspecialchars($plan['program_justification']) ?></p>

    <h4>Beneficiaries</h4>
    <p style="white-space:pre-wrap;"><?= htmlspecialchars($plan['beneficiaries']) ?></p>

    <h4>Program Plan</h4>
    <p style="white-space:pre-wrap;"><?= htmlspecialchars($plan['program_plan']) ?></p>

    <h4>Program Activities</h4>
    <?php if (empty($activities)): ?>
      <p>No activities recorded.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="activities-table">
          <thead>
            <tr>
              <th>Program</th>
              <th>Milestones</th>
              <th>Objectives</th>
              <th>Strategies</th>
              <th>Persons/Agencies Involved</th>
              <th>Resources Needed</th>
              <th style="text-align:right;">Budget</th>
              <th>Time Frame</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activities as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['program']) ?></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['milestones'] ?? '')) ?></div></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['objectives'] ?? '')) ?></div></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['strategies'] ?? '')) ?></div></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['persons_agencies_involved'] ?? '')) ?></div></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['resources_needed'] ?? '')) ?></div></td>
                <td style="text-align:right;">
                  <?php
                    $b = $a['budget'];
                    echo ($b === '' || $b === null) ? '—' : number_format((float)$b, 2);
                  ?>
                </td>
                <td><?= htmlspecialchars($a['time_frame'] ?? '') ?></td>
                <td><div class="pre"><?= nl2br(htmlspecialchars($a['remarks'] ?? '')) ?></div></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Print View (only shown when printing approved plans) -->
  <div class="print-view" style="display:none;">
    <h1 style="text-align:center;margin-bottom:30px;">3-YEAR DEVELOPMENT PLAN</h1>

    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
      <tr>
        <td style="width:220px;font-weight:bold;border-bottom:1px solid #000;">Department</td>
        <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($plan['department'] ?? '—') ?></td>
      </tr>
      <tr>
        <td style="font-weight:bold;border-bottom:1px solid #000;">Project Title</td>
        <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($plan['project_title']) ?></td>
      </tr>
      <tr>
        <td style="font-weight:bold;border-bottom:1px solid #000;">Coordinator</td>
        <td style="border-bottom:1px solid #000;"><?= htmlspecialchars($plan['username']) ?></td>
      </tr>
      <tr>
        <td style="font-weight:bold;border-bottom:1px solid #000;">Date Submitted</td>
        <td style="border-bottom:1px solid #000;"><?= !empty($plan['created_at']) ? date('M d, Y', strtotime($plan['created_at'])) : '—' ?></td>
      </tr>
    </table>

    <h3 style="margin-top:24px;">I. Project/Program Information</h3>
    <p><strong>Description:</strong></p>
    <p style="white-space:pre-wrap;margin-left:20px;"><?= htmlspecialchars($plan['project_description']) ?></p>

    <p><strong>General Objectives:</strong></p>
    <p style="white-space:pre-wrap;margin-left:20px;"><?= htmlspecialchars($plan['general_objectives']) ?></p>

    <p><strong>Program Justification:</strong></p>
    <p style="white-space:pre-wrap;margin-left:20px;"><?= htmlspecialchars($plan['program_justification']) ?></p>

    <p><strong>Beneficiaries:</strong></p>
    <p style="white-space:pre-wrap;margin-left:20px;"><?= htmlspecialchars($plan['beneficiaries']) ?></p>

    <p><strong>Program Plan:</strong></p>
    <p style="white-space:pre-wrap;margin-left:20px;"><?= htmlspecialchars($plan['program_plan']) ?></p>

    <h3 style="margin-top:24px;">II. Program Activities</h3>
    <table style="width:100%;border-collapse:collapse;margin-top:10px;">
      <thead>
        <tr>
          <th style="border:1px solid #000;padding:6px;">Program</th>
          <th style="border:1px solid #000;padding:6px;">Milestones</th>
          <th style="border:1px solid #000;padding:6px;">Objectives</th>
          <th style="border:1px solid #000;padding:6px;">Strategies</th>
          <th style="border:1px solid #000;padding:6px;">Persons/Agencies</th>
          <th style="border:1px solid #000;padding:6px;">Resources</th>
          <th style="border:1px solid #000;padding:6px;">Budget</th>
          <th style="border:1px solid #000;padding:6px;">Time Frame</th>
          <th style="border:1px solid #000;padding:6px;">Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($activities)): ?>
          <?php foreach ($activities as $a): ?>
          <tr>
            <td style="border:1px solid #000;padding:6px;"><?= htmlspecialchars($a['program']) ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['milestones'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['objectives'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['strategies'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['persons_agencies_involved'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['resources_needed'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;text-align:right;"><?= ($a['budget'] === '' || $a['budget'] === null) ? '—' : number_format((float)$a['budget'], 2) ?></td>
            <td style="border:1px solid #000;padding:6px;"><?= htmlspecialchars($a['time_frame'] ?? '') ?></td>
            <td style="border:1px solid #000;padding:6px;white-space:pre-wrap;"><?= htmlspecialchars($a['remarks'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" style="border:1px solid #000;padding:6px;">No activities recorded.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div style="margin-top:50px;">
      <p>Prepared by:</p>
      <p style="margin-top:50px;">_________________________</p>
      <p>CES Coordinator</p>
    </div>

    <div style="margin-top:50px;display:flex;justify-content:space-between;">
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

    <div style="margin-top:50px;">
      <p>Recommending Approval:</p>
      <p style="margin-top:30px;">BEVERLY D. JAMINAL, Ed.D</p>
      <p>Vice-President for Academic Affairs and Research</p>

      <p style="margin-top:30px;">REV. FR. EULOSIO C. JUNIO, CCB</p>
      <p>Vice-President for Administrative Affairs</p>
    </div>

    <div style="margin-top:50px;">
      <p>Approved by:</p>
      <p style="margin-top:30px;">REV. FR. RONNEL BABANO, STL</p>
      <p>School President</p>
    </div>
  </div>

  <?php if (($plan['status'] ?? 'pending') === 'pending'): ?>
  <form method="POST" class="review-form no-print">
    <div class="form-group">
      <label for="admin_notes">Admin Notes:</label>
      <textarea id="admin_notes" name="admin_notes" rows="4"><?= htmlspecialchars($plan['admin_notes'] ?? '') ?></textarea>
    </div>

    <div class="action-buttons">
      <button type="submit" name="action" value="approve" class="btn approve">✅ Approve</button>
      <button type="submit" name="action" value="request_changes" class="btn request-changes">✏️ Request Changes</button>
      <button type="submit" name="action" value="reject" class="btn reject">❌ Reject</button>
    </div>
  </form>
  <?php elseif (!empty($plan['admin_notes'])): ?>
    <div class="admin-notes no-print">
      <h4>Admin Notes:</h4>
      <div class="notes-content">
        <?= nl2br(htmlspecialchars($plan['admin_notes'])) ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<style>
  @media print{
    .no-print{ display:none !important; }
    .print-view{ display:block !important; }
    body{ font-family: Arial, sans-serif; line-height:1.5; padding:20px; }
  }

  /* Screen styles */
  .review-report{ max-width:1100px; margin:0 auto; padding:20px; }
  .report-details{
    background:#fff; padding:20px; border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,.08);
  }
  .table-wrap{ overflow:auto; margin-top:10px; }

  .activities-table{
    width:100%; border-collapse:separate; border-spacing:0;
    background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;
  }
  .activities-table thead th{
    background:#f0f4fa; color:#192f5d; text-align:left; padding:10px;
    font-weight:700; font-size:.95rem; border-bottom:1px solid #e5e7eb; white-space:nowrap;
  }
  .activities-table td{
    padding:10px; border-bottom:1px solid #eee; vertical-align:top;
  }
  .activities-table tr:nth-child(even) td{ background:#fbfdff; }
  .pre{ white-space:pre-wrap; word-wrap:break-word; }

  .form-group{ margin:16px 0; }
  .form-group label{ display:block; margin-bottom:6px; font-weight:700; }
  .form-group textarea{
    width:100%; min-height:110px; padding:10px;
    border:1px solid #ddd; border-radius:6px;
  }
  .action-buttons{ display:flex; gap:10px; margin-top:12px; }
  .btn{
    padding:10px 15px; border:none; border-radius:8px; cursor:pointer; font-size:14px;
    transition: filter .15s ease;
  }
  .btn:hover{ filter: brightness(0.98); }
  .btn.approve{ background:#4CAF50; color:#fff; }
  .btn.request-changes{ background:#FFC107; color:#111; }
  .btn.reject{ background:#F44336; color:#fff; }

  .status-badge{ padding:3px 8px; border-radius:6px; font-size:14px; font-weight:700; }
  .status-badge.approved{ background:#d4edda; color:#155724; }
  .status-badge.rejected{ background:#f8d7da; color:#721c24; }
  .status-badge.pending{ background:#fff3cd; color:#8a6d3b; }

  .admin-notes{
    background:#fff8e1; padding:16px; border-radius:8px;
    border-left:4px solid #f59e0b; margin-top:16px;
  }
  .admin-notes h4{ margin:0 0 10px; color:#92400e; }
  .notes-content{ background:#fff; border:1px solid #fde68a; border-radius:6px; padding:12px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Show print-friendly view when printing (only for approved)
  <?php if (($plan['status'] ?? 'pending') === 'approved'): ?>
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
