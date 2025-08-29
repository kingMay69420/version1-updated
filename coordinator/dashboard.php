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

    
   
</div>

<?php require_once '../includes/footer.php'; ?>