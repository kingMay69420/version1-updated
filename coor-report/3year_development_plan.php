<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

$editMode = false;
$message = '';
$message_type = '';

/** Default blank state */
$planData = [
    'id' => null,
    'project_title' => '',
    'project_description' => '',
    'general_objectives' => '',
    'program_justification' => '',
    'beneficiaries' => '',
    'program_plan' => '',
    'activities' => [
        [
            'program' => '',
            'milestones' => '',
            'objectives' => '',
            'strategies' => '',
            'persons_agencies_involved' => '',
            'resources_needed' => '',
            'budget' => '',
            'time_frame' => '',
            'remarks' => ''
        ]
    ]
];

/** If hitting page with ?edit=ID, load existing plan (must belong to coordinator & be rejected) */
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];

    // Load the plan and ensure ownership + rejected status (like your example)
    $stmt = $pdo->prepare("SELECT * FROM three_year_development_plans 
                           WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
    $stmt->execute([$editId, $_SESSION['user_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $editMode = true;
        $planData = array_merge($planData, $existing);

        // Load activities for this plan
        $a = $pdo->prepare("SELECT program, milestones, objectives, strategies, persons_agencies_involved,
                                   resources_needed, budget, time_frame, remarks
                            FROM development_plan_activities
                            WHERE plan_id = ?
                            ORDER BY id ASC");
        $a->execute([$existing['id']]);
        $acts = $a->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($acts)) {
            $planData['activities'] = $acts;
        }
    }
}

/** Carry any flash messages */
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

/** Handle submit (create or update) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // collect basic fields
        $planId = isset($_POST['plan_id']) && $_POST['plan_id'] !== '' ? (int) $_POST['plan_id'] : null;
        $editMode = $planId !== null;

        $planData = [
            'id' => $planId,
            'project_title' => trim($_POST['project_title'] ?? ''),
            'project_description' => trim($_POST['project_description'] ?? ''),
            'general_objectives' => trim($_POST['general_objectives'] ?? ''),
            'program_justification' => trim($_POST['program_justification'] ?? ''),
            'beneficiaries' => trim($_POST['beneficiaries'] ?? ''),
            'program_plan' => trim($_POST['program_plan'] ?? ''),
            'activities' => is_array($_POST['activities'] ?? null) ? $_POST['activities'] : []
        ];

        // Basic validation
        $errors = [];
        $required = ['project_title','project_description','general_objectives','program_justification','beneficiaries','program_plan'];
        foreach ($required as $f) {
            if ($planData[$f] === '') {
                $errors[] = ucfirst(str_replace('_',' ', $f)).' is required';
            }
        }
        if (empty($planData['activities']) || !is_array($planData['activities'])) {
            $errors[] = 'At least one program activity is required';
        }

        if (!empty($errors)) {
            $message = implode("<br>", $errors);
            $message_type = 'error';
        } else {
            $pdo->beginTransaction();

            if ($editMode) {
                // Ensure this plan belongs to the current coordinator and is editable
                $chk = $pdo->prepare("SELECT id FROM three_year_development_plans 
                                      WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
                $chk->execute([$planId, $_SESSION['user_id']]);
                if (!$chk->fetch()) {
                    throw new Exception("You cannot edit this plan.");
                }

                // Update main plan, reset status to pending and clear admin notes
                $u = $pdo->prepare("UPDATE three_year_development_plans SET
                        project_title = ?,
                        project_description = ?,
                        general_objectives = ?,
                        program_justification = ?,
                        beneficiaries = ?,
                        program_plan = ?,
                        status = 'pending',
                        admin_notes = NULL,
                        updated_at = NOW()
                    WHERE id = ? AND coordinator_id = ?");
                $u->execute([
                    $planData['project_title'],
                    $planData['project_description'],
                    $planData['general_objectives'],
                    $planData['program_justification'],
                    $planData['beneficiaries'],
                    $planData['program_plan'],
                    $planId,
                    $_SESSION['user_id']
                ]);

                // Replace activities: delete then reinsert
                $del = $pdo->prepare("DELETE FROM development_plan_activities WHERE plan_id = ?");
                $del->execute([$planId]);

                $insA = $pdo->prepare("INSERT INTO development_plan_activities
                    (plan_id, program, milestones, objectives, strategies, persons_agencies_involved,
                     resources_needed, budget, time_frame, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($planData['activities'] as $a) {
                    if (!empty($a['program'])) {
                        $insA->execute([
                            $planId,
                            $a['program'] ?? '',
                            $a['milestones'] ?? '',
                            $a['objectives'] ?? '',
                            $a['strategies'] ?? '',
                            $a['persons_agencies_involved'] ?? '',
                            $a['resources_needed'] ?? '',
                            ($a['budget'] ?? '') === '' ? null : $a['budget'],
                            $a['time_frame'] ?? '',
                            $a['remarks'] ?? ''
                        ]);
                    }
                }

                $pdo->commit();
                $_SESSION['message'] = "3-Year Development Plan updated and resubmitted for review.";
                $_SESSION['message_type'] = 'success';
                header("Location: /coordinator/dashboard.php");
                exit();

            } else {
                // Create new plan
                $stmt = $pdo->prepare("INSERT INTO three_year_development_plans
                    (coordinator_id, project_title, project_description, general_objectives,
                     program_justification, beneficiaries, program_plan, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $planData['project_title'],
                    $planData['project_description'],
                    $planData['general_objectives'],
                    $planData['program_justification'],
                    $planData['beneficiaries'],
                    $planData['program_plan']
                ]);

                $newId = (int) $pdo->lastInsertId();

                $insA = $pdo->prepare("INSERT INTO development_plan_activities
                    (plan_id, program, milestones, objectives, strategies, persons_agencies_involved,
                     resources_needed, budget, time_frame, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                foreach ($planData['activities'] as $a) {
                    if (!empty($a['program'])) {
                        $insA->execute([
                            $newId,
                            $a['program'] ?? '',
                            $a['milestones'] ?? '',
                            $a['objectives'] ?? '',
                            $a['strategies'] ?? '',
                            $a['persons_agencies_involved'] ?? '',
                            $a['resources_needed'] ?? '',
                            ($a['budget'] ?? '') === '' ? null : $a['budget'],
                            $a['time_frame'] ?? '',
                            $a['remarks'] ?? ''
                        ]);
                    }
                }

                $pdo->commit();
                $_SESSION['message'] = "3-Year Development Plan submitted successfully!";
                $_SESSION['message_type'] = 'success';
                header("Location: /coordinator/dashboard.php");
                exit();
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $editMode ? 'Edit 3-Year Development Plan' : '3-Year Development Plan'; ?></title>
<style>
  /* ===== (same design you already use) ===== */
  :root{
    --bg: #f6f7fb;
    --card: #ffffff;
    --text: #1f2937;
    --muted: #6b7280;
    --primary: #2563eb;
    --primary-600:#1d4ed8;
    --success:#16a34a;
    --success-600:#15803d;
    --danger:#dc2626;
    --danger-600:#b91c1c;
    --warning:#f59e0b;
    --border:#e5e7eb;
    --ring:#93c5fd;
    --shadow: 0 8px 24px rgba(2,6,23,.06), 0 2px 6px rgba(2,6,23,.08);
    --radius: 12px;
    --radius-sm: 8px;
    --radius-xs: 6px;
    --space: 20px;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0;
    padding: clamp(16px, 2vw, 28px);
    font: 400 16px/1.6 ui-sans-serif, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    background: #ffffff;
    color: var(--text);
  }
  .container{
    max-width: 1100px;
    margin: 0 auto;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: clamp(20px, 2.4vw, 32px);
  }
  h1{ margin:0 0 6px; font-size: clamp(22px, 2.2vw, 30px); }
  h1 + p.subtitle{ margin:0 0 22px; color:var(--muted); font-size:14px; }
  h2{ margin:0; font-size: clamp(18px, 1.6vw, 22px); }
  .success-message,.error-message{
    border-radius: var(--radius-xs);
    padding: 14px 16px; margin:14px 0 22px; border:1px solid var(--border);
    display:grid; grid-template-columns:22px 1fr; gap:10px; align-items:start;
    box-shadow: 0 2px 8px rgba(2,6,23,.04);
  }
  .success-message{ background: color-mix(in srgb, var(--success) 10%, transparent); }
  .error-message{ background: color-mix(in srgb, var(--danger) 10%, transparent); }
  .form-section{ margin:22px 0; padding:18px; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); }
  .section-title{ padding:12px 14px; border-radius:var(--radius-sm); margin-bottom:18px; background: color-mix(in srgb, var(--primary) 8%, transparent); border:1px solid color-mix(in srgb, var(--primary) 18%, var(--border)); }
  .section-title h2{ display:flex; align-items:center; gap:10px; margin:0; }
  .form-group{ margin-bottom: 16px; }
  label{ display:block; margin:0 0 6px; font-weight:600; }
  input[type="text"], input[type="number"], textarea, select{
    width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:var(--radius-xs);
    background:var(--card); color:var(--text); outline:none; transition: box-shadow .15s ease, border-color .15s ease;
  }
  textarea{ min-height: 120px; resize: vertical; }
  input:focus, textarea:focus, select:focus{ border-color: var(--primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ring) 35%, transparent); }
  input:invalid, textarea:invalid{ border-color: color-mix(in srgb, var(--danger) 40%, var(--border)); }
  .submit-btn, .add-activity, .remove-activity{
    appearance:none; font-weight:600; border-radius:10px; border:1px solid transparent; padding:10px 16px;
    line-height:1.25; cursor:pointer; transition: transform .02s ease, box-shadow .15s ease, background .2s ease, border-color .2s ease;
    box-shadow: 0 1px 0 rgba(2,6,23,.04); display:inline-flex; align-items:center; gap:8px;
  }
  .submit-btn{ background:var(--primary); color:#fff; padding:12px 20px; font-size:15px; margin:10px auto 0; display:block; }
  .submit-btn:hover{ background: var(--primary-600); }
  .submit-btn:active{ transform: translateY(1px); }
  .add-activity{ background:#111827; color:#fff; border-color:#111827; }
  .add-activity:hover{ filter: brightness(1.05); }
  .remove-activity{ background:var(--danger); color:#fff; border-color: color-mix(in srgb, var(--danger) 65%, var(--border)); padding:6px 10px; font-size:13px; }
  .remove-activity:hover{ background: var(--danger-600); }
  .activity-table{ width:100%; border-collapse:separate; border-spacing:0; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
  .activity-table thead th{
    position:sticky; top:0; z-index:1; background: linear-gradient(0deg, color-mix(in srgb, var(--primary) 8%, var(--card)), color-mix(in srgb, var(--primary) 10%, var(--card)));
    color:var(--text); text-align:left; font-weight:700; padding:12px 10px; border-bottom:1px solid var(--border); font-size:13px; white-space:nowrap;
  }
  .activity-table th,.activity-table td{ border-right:1px solid var(--border); }
  .activity-table th:last-child,.activity-table td:last-child{ border-right:0; }
  .activity-table tbody tr{ border-top:1px solid var(--border); transition: background .12s ease; }
  .activity-table tbody tr:nth-child(even){ background: color-mix(in srgb, var(--card) 80%, #f6f7fb); }
  .activity-table tbody tr:hover{ background: color-mix(in srgb, var(--primary) 6%, transparent); }
  .activity-table td{ padding:10px; vertical-align:top; }
  .activity-table input,.activity-table textarea{ background:var(--card); border-color:var(--border); padding:8px 10px; font-size:14px; border-radius:var(--radius-xs); min-width:140px; }
  .activity-table textarea{ min-height:90px; }
  .form-section:has(.activity-table){ overflow:auto; padding-bottom:10px; }
  @media (max-width: 420px){ .submit-btn{ width:100%; } }
</style>
</head>
<body>
<div class="container">
    <h1><?php echo $editMode ? 'EDIT 3-YEAR DEVELOPMENT PLAN' : '3-YEAR DEVELOPMENT PLAN'; ?></h1>
    <p class="subtitle"><?php echo $editMode ? 'Update your plan and resubmit for admin review.' : 'Fill out the details and submit for admin review.'; ?></p>

    <?php if (!empty($message)): ?>
        <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
            <div>ℹ️</div>
            <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php if ($editMode): ?>
            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($planData['id']); ?>">
        <?php endif; ?>

        <!-- Section I: Basic Information -->
        <div class="form-section">
            <div class="section-title">
                <h2>I. Project/Program Information</h2>
            </div>

            <div class="form-group">
                <label for="project_title">Title of the Project/Program:</label>
                <input type="text" id="project_title" name="project_title"
                       value="<?php echo htmlspecialchars($planData['project_title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="project_description">Description of the Project/Program:</label>
                <textarea id="project_description" name="project_description" required><?php echo htmlspecialchars($planData['project_description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="general_objectives">General Objectives:</label>
                <textarea id="general_objectives" name="general_objectives" required><?php echo htmlspecialchars($planData['general_objectives']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="program_justification">Program Justification:</label>
                <textarea id="program_justification" name="program_justification" required><?php echo htmlspecialchars($planData['program_justification']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="beneficiaries">Beneficiaries:</label>
                <textarea id="beneficiaries" name="beneficiaries" required><?php echo htmlspecialchars($planData['beneficiaries']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="program_plan">Program Plan:</label>
                <textarea id="program_plan" name="program_plan" required><?php echo htmlspecialchars($planData['program_plan']); ?></textarea>
            </div>
        </div>

        <!-- Section II: Program Activities -->
        <div class="form-section">
            <div class="section-title">
                <h2>II. Program Activities</h2>
            </div>

            <table class="activity-table">
                <thead>
                <tr>
                    <th>Program</th>
                    <th>Milestones</th>
                    <th>Objectives</th>
                    <th>Strategies</th>
                    <th>Persons/Agencies Involved</th>
                    <th>Resources Needed</th>
                    <th>Budget</th>
                    <th>Time Frame</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody id="activities-container">
                <?php foreach ($planData['activities'] as $index => $activity): ?>
                    <tr>
                        <td><input type="text" name="activities[<?php echo $index; ?>][program]"
                                   value="<?php echo htmlspecialchars($activity['program']); ?>" required></td>
                        <td><textarea name="activities[<?php echo $index; ?>][milestones]"><?php echo htmlspecialchars($activity['milestones']); ?></textarea></td>
                        <td><textarea name="activities[<?php echo $index; ?>][objectives]"><?php echo htmlspecialchars($activity['objectives']); ?></textarea></td>
                        <td><textarea name="activities[<?php echo $index; ?>][strategies]"><?php echo htmlspecialchars($activity['strategies']); ?></textarea></td>
                        <td><textarea name="activities[<?php echo $index; ?>][persons_agencies_involved]"><?php echo htmlspecialchars($activity['persons_agencies_involved']); ?></textarea></td>
                        <td><textarea name="activities[<?php echo $index; ?>][resources_needed]"><?php echo htmlspecialchars($activity['resources_needed']); ?></textarea></td>
                        <td><input type="number" step="0.01" name="activities[<?php echo $index; ?>][budget]"
                                   value="<?php echo htmlspecialchars((string)($activity['budget'] ?? '')); ?>"></td>
                        <td><input type="text" name="activities[<?php echo $index; ?>][time_frame]"
                                   value="<?php echo htmlspecialchars($activity['time_frame']); ?>"></td>
                        <td><textarea name="activities[<?php echo $index; ?>][remarks]"><?php echo htmlspecialchars($activity['remarks']); ?></textarea></td>
                        <td><button type="button" class="remove-activity" onclick="removeActivity(this)">Remove</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" class="add-activity" onclick="addActivity()">+ Add Activity</button>
        </div>

        <button type="submit" class="submit-btn"><?php echo $editMode ? 'Update & Resubmit Plan' : 'Submit Development Plan'; ?></button>
    </form>
</div>

<script>
    let activityCount = <?php echo count($planData['activities']); ?>;

    function addActivity() {
        const container = document.getElementById('activities-container');
        const idx = activityCount++;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="activities[${idx}][program]" required></td>
            <td><textarea name="activities[${idx}][milestones]"></textarea></td>
            <td><textarea name="activities[${idx}][objectives]"></textarea></td>
            <td><textarea name="activities[${idx}][strategies]"></textarea></td>
            <td><textarea name="activities[${idx}][persons_agencies_involved]"></textarea></td>
            <td><textarea name="activities[${idx}][resources_needed]"></textarea></td>
            <td><input type="number" step="0.01" name="activities[${idx}][budget]"></td>
            <td><input type="text" name="activities[${idx}][time_frame]"></td>
            <td><textarea name="activities[${idx}][remarks]"></textarea></td>
            <td><button type="button" class="remove-activity" onclick="removeActivity(this)">Remove</button></td>
        `;
        container.appendChild(row);
    }

    function removeActivity(btn) {
        const tbody = document.getElementById('activities-container');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('At least one activity is required.');
        }
    }
</script>
</body>
</html>
