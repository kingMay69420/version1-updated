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

$uid = (int)$_SESSION['user_id'];
$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth   = date('Y-m-t 23:59:59');

// ===== Stats =====
$counts = [
  'submitted_month' => 0,
  'pending'         => 0,
  'rejected'        => 0,
  'unread_msgs'     => 0,
];

// submitted this month (all 4 types)
$sumMonth = 0;
foreach ([
  ["reports", "created_at"],
  ["community_needs_reports", "created_at"],
  ["program_designs", "created_at"],
  ["three_year_development_plans", "created_at"],
] as [$tbl, $datecol]) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE coordinator_id = ? AND {$datecol} BETWEEN ? AND ?");
  $stmt->execute([$uid, $startOfMonth, $endOfMonth]);
  $sumMonth += (int)$stmt->fetchColumn();
}
$counts['submitted_month'] = $sumMonth;

// pending + rejected (all 4 types)
$pending = 0; $rejected = 0;
foreach (["reports","community_needs_reports","program_designs","three_year_development_plans"] as $tbl) {
  $p = $pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE coordinator_id = ? AND status = 'pending'");
  $p->execute([$uid]); $pending += (int)$p->fetchColumn();

  $r = $pdo->prepare("SELECT COUNT(*) FROM {$tbl} WHERE coordinator_id = ? AND status = 'rejected'");
  $r->execute([$uid]); $rejected += (int)$r->fetchColumn();
}
$counts['pending']  = $pending;
$counts['rejected'] = $rejected;

// unread chat
$c = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE receiver_id = ? AND is_read = 0");
$c->execute([$uid]);
$counts['unread_msgs'] = (int)$c->fetchColumn();

// ===== Upcoming (simple reminders) =====
$upcoming = [];
$deadline = date('Y-m-t'); // end of this month
$upcoming[] = [
  'title' => 'File your Monthly Accomplishment Report',
  'date'  => $deadline,
  'link'  => '/coor-report/new-report.php'
];

// ===== Recent activity (5 most recent across all) =====
$recent = [];
// union-like approach in PHP
foreach ([
  ['reports','activity_title','created_at','/coordinator/view-report.php?id='],
  ['community_needs_reports','participant_name','created_at','/coordinator/view_cnacr.php?id='],
  ['program_designs','activity_title','created_at','/coordinator/view_program_design.php?id='],
  ['three_year_development_plans','project_title','created_at','/coordinator/view_3_year_plan.php?id='],
] as [$tbl,$titleCol,$dateCol,$viewBase]) {
  $q = $pdo->prepare("SELECT id, '{$tbl}' AS src, {$titleCol} AS title, {$dateCol} AS created_at, status
                      FROM {$tbl}
                      WHERE coordinator_id = ?
                      ORDER BY {$dateCol} DESC
                      LIMIT 5");
  $q->execute([$uid]);
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    $r['view'] = $viewBase.$r['id'];
    $recent[]  = $r;
  }
}
usort($recent, fn($a,$b)=>strtotime($b['created_at'])<=>strtotime($a['created_at']));
$recent = array_slice($recent, 0, 5);

require_once '../includes/header.php';
?>

<div class="coordinator-dashboard">
    <h2>Coordinator Dashboard</h2>
    
    <?php if ($showNotification): ?>
        <div class="alert alert-info">
            <p><strong>Reminder:</strong> You have not yet filed your accomplishment report for <?php echo date('F Y'); ?>. Please <a href="new-report.php">file your report</a>.</p>
        </div>
    <?php endif; ?>
    

    <div class="card-buttons-row">
  <a href="/coor-report/new-report.php" class="card-button">
    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
    <div class="card-content">
      <h3>Monthly Accomplishment Report</h3>
      <p>Create New Report</p>
    </div>
  </a>

  <a href="/coor-report/cnacr.php" class="card-button">
    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
    <div class="card-content">
      <h3>Community Needs Assessment Consolidated Report</h3>
      <p>Create New Report</p>
    </div>
  </a>

  <a href="/coor-report/3year_development_plan.php" class="card-button">
    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
    <div class="card-content">
      <h3>3-Year Development Plan</h3>
      <p>Create New Report</p>
    </div>
  </a>

  <a href="/coor-report/program_design_form.php" class="card-button">
    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
    <div class="card-content">
      <h3>Program Design Form</h3>
      <p>Create New Report</p>
    </div>
  </a>

</div>

    <!-- Stats row -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-kpi"><?= $counts['submitted_month'] ?></div>
    <div class="stat-label">Submitted this month</div>
  </div>
  <div class="stat-card">
    <div class="stat-kpi"><?= $counts['pending'] ?></div>
    <div class="stat-label">Pending approvals</div>
  </div>
  <div class="stat-card">
    <div class="stat-kpi"><?= $counts['rejected'] ?></div>
    <div class="stat-label">Rejected (needs fixes)</div>
  </div>
  <a class="stat-card link" href="/coordinator/chat.php">
    <div class="stat-kpi"><?= $counts['unread_msgs'] ?></div>
    <div class="stat-label">Unread messages</div>
  </a>
</div>

<!-- Two-column: Upcoming + Recent -->
<div class="two-col">
  <div class="card">
    <div class="card-title">Upcoming</div>
    <ul class="upcoming-list">
      <?php foreach ($upcoming as $u): ?>
        <li>
          <div>
            <div class="u-title"><?= htmlspecialchars($u['title']) ?></div>
            <div class="u-date"><?= date('M d, Y', strtotime($u['date'])) ?></div>
          </div>
          <a class="btn btn-small" href="<?= htmlspecialchars($u['link']) ?>">Open</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="card">
    <div class="card-title">Recent Activity</div>
    <?php if (empty($recent)): ?>
      <p class="muted">No recent items.</p>
    <?php else: ?>
      <ul class="timeline">
        <?php foreach ($recent as $r): ?>
          <li>
            <span class="tag <?= $r['src'] ?>">
              <?= $r['src']==='reports'?'Activity':
                  ($r['src']==='community_needs_reports'?'Community':
                  ($r['src']==='program_designs'?'Design':'3-Year Plan')) ?>
            </span>
            <a href="<?= htmlspecialchars($r['view']) ?>"><?= htmlspecialchars($r['title'] ?: '(untitled)') ?></a>
            <span class="time">· <?= date('M d, Y H:i', strtotime($r['created_at'])) ?></span>
            <span class="status s-<?= htmlspecialchars($r['status'] ?? 'pending') ?>">
              <?= htmlspecialchars(ucfirst($r['status'] ?? 'pending')) ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

   
</div>

<?php require_once '../includes/footer.php'; ?>