<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: history.php");
    exit();
}

$planId = $_GET['id'];

// Fetch plan (scoped to logged-in coordinator)
$stmt = $pdo->prepare("SELECT * FROM three_year_development_plans WHERE id = ? AND coordinator_id = ?");
$stmt->execute([$planId, $_SESSION['user_id']]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header("Location: history.php");
    exit();
}

// Fetch activities
$act = $pdo->prepare("SELECT program, milestones, objectives, strategies, persons_agencies_involved,
                             resources_needed, budget, time_frame, remarks
                      FROM development_plan_activities
                      WHERE plan_id = ?
                      ORDER BY id ASC");
$act->execute([$planId]);
$activities = $act->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="view-report">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <h2>3-Year Development Plan</h2>
    <button onclick="window.print()" class="btn no-print"><i class="fas fa-print"></i> Print</button>
  </div>

  <div class="report-header">
    <h3><?php echo htmlspecialchars($plan['project_title']); ?></h3>
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
    <p><strong>Date Submitted:</strong>
       <?php echo !empty($plan['created_at']) ? date('M d, Y H:i', strtotime($plan['created_at'])) : '—'; ?>
    </p>
  </div>

  <div class="report-details">
    <div class="detail-row">
      <span class="detail-label">Department:</span>
      <span class="detail-value"><?php echo htmlspecialchars($plan['department'] ?? '—'); ?></span>
    </div>

    <div class="detail-section">
      <h4>Description of the Project/Program</h4>
      <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($plan['project_description']); ?></p>
    </div>

    <div class="detail-section">
      <h4>General Objectives</h4>
      <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($plan['general_objectives']); ?></p>
    </div>

    <div class="detail-section">
      <h4>Program Justification</h4>
      <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($plan['program_justification']); ?></p>
    </div>

    <div class="detail-section">
      <h4>Beneficiaries</h4>
      <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($plan['beneficiaries']); ?></p>
    </div>

    <div class="detail-section">
      <h4>Program Plan</h4>
      <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($plan['program_plan']); ?></p>
    </div>

    <div class="detail-section">
      <h4>Program Activities</h4>
      <?php if (empty($activities)): ?>
        <p>No activities recorded.</p>
      <?php else: ?>
        <div style="overflow:auto;">
          <table class="activities-table" style="width:100%;border-collapse:collapse;">
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
                  <td><?php echo htmlspecialchars($a['program']); ?></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['milestones'] ?? ''); ?></div></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['objectives'] ?? ''); ?></div></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['strategies'] ?? ''); ?></div></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['persons_agencies_involved'] ?? ''); ?></div></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['resources_needed'] ?? ''); ?></div></td>
                  <td style="text-align:right;"><?php
                      $b = $a['budget'];
                      echo ($b === '' || $b === null) ? '—' : number_format((float)$b, 2);
                  ?></td>
                  <td><?php echo htmlspecialchars($a['time_frame'] ?? ''); ?></td>
                  <td><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($a['remarks'] ?? ''); ?></div></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($status === 'rejected' && !empty($plan['admin_notes'])): ?>
      <div class="admin-notes no-print">
        <h4>Admin Feedback:</h4>
        <div class="notes-content">
          <?php echo nl2br(htmlspecialchars($plan['admin_notes'])); ?>
        </div>
        <a href="/coor-report/3year_development_plan.php?edit=<?php echo $plan['id']; ?>" class="btn">Resubmit Plan</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="actions no-print">
    <a href="history.php" class="btn">Back to History</a>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
