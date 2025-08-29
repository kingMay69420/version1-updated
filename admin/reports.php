<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

// Get filter parameters
$start_date  = $_GET['start_date']  ?? '';
$end_date    = $_GET['end_date']    ?? '';
$department  = $_GET['department']  ?? '';
$status      = $_GET['status']      ?? '';
$report_type = $_GET['report_type'] ?? 'activity'; // 'activity' | 'community' | 'plan' | 'program'

// Build query with filters based on report type
switch ($report_type) {
    case 'community':
        $query = "SELECT r.*, u.username, 'community' as report_type
                  FROM community_needs_reports r
                  JOIN users u ON r.coordinator_id = u.id
                  WHERE 1=1";
        $date_field   = 'assessment_date';
        $title_field  = 'participant_name';
        $has_location = true;
        break;

    case 'plan':
        $query = "SELECT r.*, u.username, 'plan' as report_type
                  FROM three_year_development_plans r
                  JOIN users u ON r.coordinator_id = u.id
                  WHERE 1=1";
        $date_field   = 'created_at';
        $title_field  = 'project_title';
        $has_location = false;
        break;

    case 'program': // NEW: Program Designs
        $query = "SELECT r.*, u.username, 'program' as report_type
                  FROM program_designs r
                  JOIN users u ON r.coordinator_id = u.id
                  WHERE 1=1";
        $date_field   = 'created_at';
        $title_field  = 'activity_title';
        $has_location = true; // program designs have a location column
        break;

    case 'activity':
    default:
        $query = "SELECT r.*, u.username, 'activity' as report_type
                  FROM reports r
                  JOIN users u ON r.coordinator_id = u.id
                  WHERE 1=1";
        $date_field   = 'date';
        $title_field  = 'activity_title';
        $has_location = false;
        break;
}

$params = [];

// Date filter (only if provided)
if (!empty($start_date)) {
    $query .= " AND r.$date_field >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND r.$date_field <= ?";
    $params[] = $end_date;
}

// Department filter
if (!empty($department)) {
    $query .= " AND r.department = ?";
    $params[] = $department;
}

// Status filter
if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build departments list from all sources that have a department column
$departments = [];

$deptStmt1 = $pdo->query("SELECT DISTINCT department FROM reports WHERE department IS NOT NULL AND department <> '' ORDER BY department");
$departments = array_merge($departments, $deptStmt1->fetchAll(PDO::FETCH_COLUMN));

$deptStmt2 = $pdo->query("SELECT DISTINCT department FROM community_needs_reports WHERE department IS NOT NULL AND department <> '' ORDER BY department");
$departments = array_merge($departments, $deptStmt2->fetchAll(PDO::FETCH_COLUMN));

try {
    $deptStmt3 = $pdo->query("SELECT DISTINCT department FROM three_year_development_plans WHERE department IS NOT NULL AND department <> '' ORDER BY department");
    $departments = array_merge($departments, $deptStmt3->fetchAll(PDO::FETCH_COLUMN));
} catch (\Throwable $e) { /* ignore if not present */ }

try {
    // NEW: program designs
    $deptStmt4 = $pdo->query("SELECT DISTINCT department FROM program_designs WHERE department IS NOT NULL AND department <> '' ORDER BY department");
    $departments = array_merge($departments, $deptStmt4->fetchAll(PDO::FETCH_COLUMN));
} catch (\Throwable $e) { /* ignore if not present */ }

$departments = array_values(array_unique($departments));
sort($departments);

require_once '../includes/header.php';
?>

<div class="admin-reports">
    <h2>Reports Management</h2>

    <div class="filter-controls">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="report_type">Report Type:</label>
                <select id="report_type" name="report_type">
                    <option value="activity"  <?= $report_type === 'activity'  ? 'selected' : '' ?>>Activity Reports</option>
                    <option value="community" <?= $report_type === 'community' ? 'selected' : '' ?>>Community Needs Assessments</option>
                    <option value="plan"      <?= $report_type === 'plan'      ? 'selected' : '' ?>>3-Year Development Plans</option>
                    <option value="program"   <?= $report_type === 'program'   ? 'selected' : '' ?>>Program Designs</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>

            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>

            <div class="form-group">
                <label for="department">Department:</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"  <?= $status === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <button type="submit" class="btn">Apply Filters</button>
            <a href="reports.php" class="btn">Clear Filters</a>
        </form>
    </div>

    <div class="reports-table">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>
                        <?php
                          if     ($report_type === 'community') { echo 'Participant'; }
                          elseif ($report_type === 'plan')      { echo 'Project Title'; }
                          elseif ($report_type === 'program')   { echo 'Activity Title'; }
                          else                                   { echo 'Title'; }
                        ?>
                    </th>
                    <th>Coordinator</th>
                    <th>Department</th>
                    <th>
                        <?= $report_type === 'community'
                              ? 'Assessment Date'
                              : ($report_type === 'plan' ? 'Submitted' : 'Date'); ?>
                    </th>
                    <?php if ($report_type === 'community' || $report_type === 'program'): ?>
                        <th>Location</th>
                    <?php endif; ?>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($reports) > 0): ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <span class="badge community-badge">Community</span>
                            <?php elseif ($report['report_type'] === 'plan'): ?>
                                <span class="badge plan-badge">3-Year Plan</span>
                            <?php elseif ($report['report_type'] === 'program'): ?>
                                <span class="badge program-badge">Program Design</span>
                            <?php else: ?>
                                <span class="badge activity-badge">Activity</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php
                              if     ($report['report_type'] === 'community') { echo htmlspecialchars($report['participant_name']); }
                              elseif ($report['report_type'] === 'plan')      { echo htmlspecialchars($report['project_title']); }
                              elseif ($report['report_type'] === 'program')   { echo htmlspecialchars($report['activity_title']); }
                              else                                             { echo htmlspecialchars($report['activity_title']); }
                            ?>
                        </td>

                        <td><?= htmlspecialchars($report['username']) ?></td>

                        <td><?= htmlspecialchars($report['department'] ?? '—') ?></td>

                        <td>
                            <?php
                              if     ($report['report_type'] === 'community') { echo date('M d, Y', strtotime($report['assessment_date'])); }
                              elseif ($report['report_type'] === 'plan')      { echo !empty($report['created_at']) ? date('M d, Y', strtotime($report['created_at'])) : '—'; }
                              elseif ($report['report_type'] === 'program')   { echo !empty($report['created_at']) ? date('M d, Y', strtotime($report['created_at'])) : '—'; }
                              else                                            { echo date('M d, Y', strtotime($report['date'])); }
                            ?>
                        </td>

                        <?php if ($report_type === 'community' || $report_type === 'program'): ?>
                            <td><?= htmlspecialchars($report['location'] ?? '—') ?></td>
                        <?php endif; ?>

                        <td>
                            <?php if (($report['status'] ?? '') === 'approved'): ?>
                                <span class="status-badge approved">✅ Approved</span>
                            <?php elseif (($report['status'] ?? '') === 'rejected'): ?>
                                <span class="status-badge rejected">❌ Rejected</span>
                            <?php else: ?>
                                <span class="status-badge pending">⏳ Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <a href="review-community-report.php?id=<?= $report['id'] ?>" class="btn-small">Review</a>
                                <a href="delete-community-report.php?id=<?= $report['id'] ?>"
                                   class="btn-small danger"
                                   onclick="return confirm('Are you sure you want to delete this community needs assessment?');">
                                   Delete
                                </a>

                            <?php elseif ($report['report_type'] === 'plan'): ?>
                                <a href="review-three-year-plan.php?id=<?= $report['id'] ?>" class="btn-small">Review</a>
                                <a href="delete-three-year-plan.php?id=<?= $report['id'] ?>"
                                   class="btn-small danger"
                                   onclick="return confirm('Are you sure you want to delete this 3-Year Development Plan?');">
                                   Delete
                                </a>

                            <?php elseif ($report['report_type'] === 'program'): ?>
                                <a href="review-program-design.php?id=<?= $report['id'] ?>" class="btn-small">Review</a>
                                <a href="delete-program-design.php?id=<?= $report['id'] ?>"
                                   class="btn-small danger"
                                   onclick="return confirm('Are you sure you want to delete this Program Design?');">
                                   Delete
                                </a>

                            <?php else: ?>
                                <a href="review-report.php?id=<?= $report['id'] ?>" class="btn-small">Review</a>
                                <a href="delete-report.php?id=<?= $report['id'] ?>"
                                   class="btn-small danger"
                                   onclick="return confirm('Are you sure you want to delete this report?');">
                                   Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= ($report_type === 'community' || $report_type === 'program') ? '9' : '8'; ?>">
                        No
                        <?php
                          echo $report_type === 'community' ? 'community needs assessment'
                               : ($report_type === 'plan' ? '3-Year Development Plan'
                               : ($report_type === 'program' ? 'program design' : 'activity'));
                        ?>
                        reports found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
