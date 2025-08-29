<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

// --- Get filter from querystring ---
$filterType  = $_GET['report_type'] ?? 'all';
$validTypes  = ['all','activity','community','plan','design']; // added 'design'
if (!in_array($filterType, $validTypes, true)) {
    $filterType = 'all';
}

// Activity reports
$stmt = $pdo->prepare("
    SELECT r.*, 'activity' AS report_type
    FROM reports r
    WHERE r.coordinator_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$activityReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Community needs assessments
$stmt = $pdo->prepare("
    SELECT r.*, 'community' AS report_type
    FROM community_needs_reports r
    WHERE r.coordinator_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$communityReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3-year development plans
$stmt = $pdo->prepare("
    SELECT p.*, 'plan' AS report_type
    FROM three_year_development_plans p
    WHERE p.coordinator_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$planReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Program designs (+ entries count)
$stmt = $pdo->prepare("
    SELECT d.*,
           'design' AS report_type,
           (
             SELECT COUNT(*) 
             FROM program_design_entries e 
             WHERE e.design_id = d.id
           ) AS entries_count
    FROM program_designs d
    WHERE d.coordinator_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$designReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine all reports
$reports = array_merge($activityReports, $communityReports, $planReports, $designReports);

// Apply type filter (server-side)
if ($filterType !== 'all') {
    $reports = array_values(array_filter($reports, function($r) use ($filterType) {
        return ($r['report_type'] ?? '') === $filterType;
    }));
}

// Sort by date (newest first), robust against missing fields
usort($reports, function($a, $b) {
    $getDate = function($r) {
        switch ($r['report_type'] ?? '') {
            case 'community': return $r['assessment_date'] ?? $r['created_at'] ?? '1970-01-01';
            case 'activity' : return $r['date']            ?? $r['created_at'] ?? '1970-01-01';
            case 'plan'     : return $r['created_at']      ?? '1970-01-01';
            case 'design'   : return $r['created_at']      ?? '1970-01-01';
            default         : return $r['created_at']      ?? '1970-01-01';
        }
    };
    return strtotime($getDate($b)) <=> strtotime($getDate($a));
});

require_once '../includes/header.php';
?>

<div class="report-history">
    <h2>Report History</h2>

    <!-- Filter bar -->
    <form method="GET" class="filter-bar">
        <label for="report_type">Type:</label>
        <select id="report_type" name="report_type" onchange="this.form.submit()">
            <option value="all"      <?= $filterType==='all'?'selected':''; ?>>All Types</option>
            <option value="activity" <?= $filterType==='activity'?'selected':''; ?>>Activity</option>
            <option value="community"<?= $filterType==='community'?'selected':''; ?>>Community</option>
            <option value="plan"     <?= $filterType==='plan'?'selected':''; ?>>3-Year Plan</option>
            <option value="design"   <?= $filterType==='design'?'selected':''; ?>>Program Design</option>
        </select>
        <?php if ($filterType !== 'all'): ?>
            <a href="?report_type=all" class="btn-small outline" style="margin-left:8px;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($reports)): ?>
        <p>No reports found for this filter.</p>
    <?php else: ?>
        <div class="reports-table">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title/Participant</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <span class="badge community-badge">Community</span>
                            <?php elseif ($report['report_type'] === 'activity'): ?>
                                <span class="badge activity-badge">Activity</span>
                            <?php elseif ($report['report_type'] === 'plan'): ?>
                                <span class="badge plan-badge">3-Year Plan</span>
                            <?php else: ?>
                                <span class="badge design-badge">Program Design</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <?= htmlspecialchars($report['participant_name']) ?>
                            <?php elseif ($report['report_type'] === 'activity'): ?>
                                <?= htmlspecialchars($report['activity_title']) ?>
                            <?php elseif ($report['report_type'] === 'plan'): ?>
                                <?= htmlspecialchars($report['project_title']) ?>
                            <?php else: /* design */ ?>
                                <?= htmlspecialchars($report['activity_title']) ?>
                                <?php if (isset($report['entries_count'])): ?>
                                    <small style="color:#6b7280;">(<?= (int)$report['entries_count'] ?> entries)</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <td><?= isset($report['department']) && $report['department'] !== '' ? htmlspecialchars($report['department']) : '—' ?></td>

                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <?= !empty($report['assessment_date']) ? date('M d, Y', strtotime($report['assessment_date'])) : '—' ?>
                            <?php elseif ($report['report_type'] === 'activity'): ?>
                                <?= !empty($report['date']) ? date('M d, Y', strtotime($report['date'])) : '—' ?>
                            <?php else: /* plan & design */ ?>
                                <?= !empty($report['created_at']) ? date('M d, Y', strtotime($report['created_at'])) : '—' ?>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php $status = $report['status'] ?? 'pending'; ?>
                            <?php if ($status === 'approved'): ?>
                                <span class="status-badge approved">✅ Approved</span>
                            <?php elseif ($status === 'rejected'): ?>
                                <span class="status-badge rejected">❌ Rejected</span>
                            <?php else: ?>
                                <span class="status-badge pending">⏳ Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($report['report_type'] === 'community'): ?>
                                <a href="view_cnacr.php?id=<?= $report['id'] ?>" class="btn-small">View</a>
                                <?php if (($report['status'] ?? '') === 'rejected'): ?>
                                    <a href="/coor-report/cnacr.php?edit=<?= $report['id'] ?>" class="btn-small">Resubmit</a>
                                <?php endif; ?>

                            <?php elseif ($report['report_type'] === 'activity'): ?>
                                <a href="view-report.php?id=<?= $report['id'] ?>" class="btn-small">View</a>
                                <?php if (($report['status'] ?? '') === 'rejected'): ?>
                                    <a href="/coor-report/new-report.php?edit=<?= $report['id'] ?>" class="btn-small">Resubmit</a>
                                <?php endif; ?>

                            <?php elseif ($report['report_type'] === 'plan'): ?>
                                <a href="view_3_year_plan.php?id=<?= $report['id'] ?>" class="btn-small">View</a>
                                <?php if (($report['status'] ?? '') === 'rejected'): ?>
                                    <a href="/coor-report/3year_development_plan.php?edit=<?= $report['id'] ?>" class="btn-small">Resubmit</a>
                                <?php endif; ?>

                            <?php else: /* design */ ?>
                                <a href="view_program_design.php?id=<?= $report['id'] ?>" class="btn-small">View</a>
                                <?php if (($report['status'] ?? '') === 'rejected'): ?>
                                    <a href="/coor-report/program_design_form.php?edit=<?= $report['id'] ?>" class="btn-small">Resubmit</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
/* ===== Report History – Polished UI + Filter (with Program Design) ===== */
:root{
  --bg:#fff; --text:#1f2937; --muted:#6b7280; --brand:#192f5d;
  --border:#e5e7eb; --row:#f8f9fa; --row-hover:#fbfdff;
  --shadow:0 6px 18px rgba(17,24,39,.08); --radius:12px; --radius-sm:10px;
}

.report-history{ max-width:1200px; margin:0 auto; padding:20px; color:var(--text); }
.report-history h2{ color:var(--brand); margin:0 0 10px; font-size:clamp(22px,2.2vw,28px); letter-spacing:.2px; }

/* Filter bar */
.filter-bar{ display:flex; align-items:center; gap:10px; margin:6px 0 12px; }
.filter-bar label{ font-weight:700; color:var(--brand); }
.filter-bar select{ padding:8px 10px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--text); }
.btn-small.outline{ background:#fff; color:var(--brand); border:1px solid var(--brand); }

/* Table wrapper */
.reports-table{ margin-top:6px; overflow:auto; border:1px solid var(--border); border-radius:var(--radius); background:#fff; box-shadow:var(--shadow); }
.reports-table table{ width:100%; border-collapse:separate; border-spacing:0; min-width:820px; }
.reports-table thead th{
  position:sticky; top:0; z-index:1; background:#f3f6fb; color:var(--brand);
  text-align:left; font-weight:700; padding:12px; border-bottom:1px solid var(--border); white-space:nowrap;
}
.reports-table th:first-child, .reports-table td:first-child{ padding-left:16px; }
.reports-table th:last-child,  .reports-table td:last-child{ padding-right:16px; }
.reports-table tbody td{ padding:12px; border-bottom:1px solid var(--border); vertical-align:middle; }
.reports-table tbody tr:nth-child(even) td{ background:var(--row); }
.reports-table tbody tr:hover td{ background:var(--row-hover); }

/* Type badges */
.badge{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; line-height:1; border:1px solid transparent; white-space:nowrap; }
.community-badge{ background:#e3f2fd; color:#1976d2; border-color:#bbdefb; }
.activity-badge{  background:#f3e5f5; color:#7b1fa2; border-color:#e1bee7; }
.plan-badge{      background:#e8f5e9; color:#2e7d32; border-color:#c8e6c9; }
.design-badge{    background:#e0f2fe; color:#075985; border-color:#bae6fd; } /* NEW */

/* Status chips */
.status-badge{ display:inline-block; padding:4px 10px; border-radius:8px; font-size:13px; font-weight:700; line-height:1.1; white-space:nowrap; }
.status-badge.approved{ background:#d4edda; color:#155724; }
.status-badge.rejected{ background:#f8d7da; color:#721c24; }
.status-badge.pending { background:#fff3cd; color:#8a6d3b; }

/* Action buttons */
.btn-small{
  display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:6px 12px;
  border:1px solid transparent; border-radius:8px; background:var(--brand); color:#fff; text-decoration:none;
  font-size:12px; font-weight:700; box-shadow:0 1px 0 rgba(17,24,39,.04);
  transition:background .2s ease, transform .02s ease, box-shadow .2s ease, opacity .2s ease; margin-right:6px;
}
.btn-small:hover{ background:#14254a; }
.btn-small:active{ transform:translateY(1px); }
.btn-small.danger{ background:#dc2626; }
.btn-small.danger:hover{ background:#b91c1c; }

/* Prevent awkward wrapping in “Type” and “Actions” */
.reports-table td:nth-child(1),
.reports-table td:nth-child(6){ white-space:nowrap; }

/* Empty state */
.report-history > p{ padding:14px 16px; border:1px dashed var(--border); border-radius:var(--radius-sm); background:#fff; color:var(--muted); }

/* Small screens */
@media (max-width:600px){ .btn-small{ padding:6px 10px; font-size:11px; } }
</style>

<?php require_once '../includes/footer.php'; ?>
