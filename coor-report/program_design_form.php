<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

$message = '';
$message_type = '';
$editMode = false;

// Base data (prefill department if stored on session)
$form = [
  'department'     => $_SESSION['department'] ?? '',
  'activity_title' => '',
  'participants'   => '',
  'location'       => '',
  'entries'        => [
    [
      'program' => '',
      'duration' => '',
      'objectives' => '',
      'persons_involved' => '',
      'resources_school' => '',
      'resources_community' => '',
      'collaborating_agencies' => '',
      'budget' => '',
      'mov' => ''
    ]
  ]
];


if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM program_designs WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
    $stmt->execute([$id, $_SESSION['user_id']]);
    if ($pd = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $editMode = true;
        $form = array_merge($form, $pd);
        $e = $pdo->prepare("SELECT * FROM program_design_entries WHERE design_id = ? ORDER BY id");
        $e->execute([$id]);
        $form['entries'] = $e->fetchAll(PDO::FETCH_ASSOC) ?: $form['entries'];
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather posted data
    $form['department']     = trim($_POST['department'] ?? '');
    $form['activity_title'] = trim($_POST['activity_title'] ?? '');
    $form['participants']   = trim($_POST['participants'] ?? '');
    $form['location']       = trim($_POST['location'] ?? '');
    $form['entries']        = $_POST['entries'] ?? [];

    // Basic validation
    $errors = [];
    foreach (['department','activity_title','participants','location'] as $f) {
        if ($form[$f] === '') $errors[] = ucfirst(str_replace('_',' ', $f)).' is required';
    }
    if (empty($form['entries']) || !is_array($form['entries'])) {
        $errors[] = "At least one program row is required";
    } else {
        // Validate at least the key required fields per row
        foreach ($form['entries'] as $i => $row) {
            if (empty($row['program']) || empty($row['duration']) || empty($row['objectives'])) {
                $errors[] = "Row ".($i+1).": Program, Duration, and Objectives are required";
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($editMode && !empty($_POST['design_id'])) {
                // OPTIONAL edit flow if you enabled it above
                $designId = (int)$_POST['design_id'];
                $stmt = $pdo->prepare("UPDATE program_designs
                    SET department = ?, activity_title = ?, participants = ?, location = ?,
                        status = 'pending', admin_notes = NULL, updated_at = NOW()
                    WHERE id = ? AND coordinator_id = ?");
                $stmt->execute([
                    $form['department'], $form['activity_title'], $form['participants'], $form['location'],
                    $designId, $_SESSION['user_id']
                ]);
                // Replace entries
                $pdo->prepare("DELETE FROM program_design_entries WHERE design_id = ?")->execute([$designId]);

            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO program_designs
                    (coordinator_id, department, activity_title, participants, location)
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $form['department'],
                    $form['activity_title'],
                    $form['participants'],
                    $form['location']
                ]);
                $designId = (int)$pdo->lastInsertId();
            }

            // Insert line items
            $ins = $pdo->prepare("INSERT INTO program_design_entries
                (design_id, program, duration, objectives, persons_involved, resources_school,
                 resources_community, collaborating_agencies, budget, mov)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($form['entries'] as $row) {
                // Default budget to 0 if empty/not numeric
                $budget = is_numeric($row['budget'] ?? null) ? (float)$row['budget'] : 0.00;
                $ins->execute([
                    $designId,
                    trim($row['program'] ?? ''),
                    trim($row['duration'] ?? ''),
                    trim($row['objectives'] ?? ''),
                    trim($row['persons_involved'] ?? ''),
                    trim($row['resources_school'] ?? ''),
                    trim($row['resources_community'] ?? ''),
                    trim($row['collaborating_agencies'] ?? ''),
                    $budget,
                    trim($row['mov'] ?? '')
                ]);
            }

            $pdo->commit();

            $_SESSION['message'] = $editMode ? "Program Design updated and resubmitted!" : "Program Design submitted successfully!";
            $_SESSION['message_type'] = 'success';
            header("Location: /coordinator/dashboard.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error saving Program Design: ".$e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }
}

require_once '../includes/header.php';
?>
<div class="pd-form">
  <h2><?= $editMode ? 'Edit Program Design' : 'Program Design Form' ?></h2>

  <?php if (!empty($message)): ?>
    <div class="<?= $message_type === 'success' ? 'alert success' : 'alert error' ?>"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" id="program-design-form" action="">
    <?php if ($editMode && !empty($form['id'])): ?>
      <input type="hidden" name="design_id" value="<?= htmlspecialchars($form['id']) ?>">
    <?php endif; ?>

    <div class="grid-2">
      <div class="form-group">
        <label for="department">Department</label>
        <input id="department" name="department" type="text" value="<?= htmlspecialchars($form['department']) ?>" required>
      </div>
      <div class="form-group">
        <label for="activity_title">Title of Activity</label>
        <input id="activity_title" name="activity_title" type="text" value="<?= htmlspecialchars($form['activity_title']) ?>" required>
      </div>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label for="participants">Participants</label>
        <input id="participants" name="participants" type="text" placeholder="e.g., 50 students; 10 faculty" value="<?= htmlspecialchars($form['participants']) ?>" required>
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input id="location" name="location" type="text" value="<?= htmlspecialchars($form['location']) ?>" required>
      </div>
    </div>

    <div class="form-section">
      <div class="section-title"><h3>Program Details</h3></div>
      <div class="table-wrap">
        <table class="entry-table">
          <thead>
            <tr>
              <th>Program</th>
              <th>Duration</th>
              <th>Objectives</th>
              <th>Persons Involved</th>
              <th>Resources (School)</th>
              <th>Resources (Community)</th>
              <th>Collaborating Agencies</th>
              <th>Budget</th>
              <th>MOV</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="entries-container">
            <?php foreach ($form['entries'] as $i => $row): ?>
              <tr>
                <td><input type="text" name="entries[<?= $i ?>][program]" value="<?= htmlspecialchars($row['program']) ?>" required></td>
                <td><input type="text" name="entries[<?= $i ?>][duration]" value="<?= htmlspecialchars($row['duration']) ?>" required></td>
                <td><textarea name="entries[<?= $i ?>][objectives]" required><?= htmlspecialchars($row['objectives']) ?></textarea></td>
                <td><textarea name="entries[<?= $i ?>][persons_involved]" ><?= htmlspecialchars($row['persons_involved']) ?></textarea></td>
                <td><textarea name="entries[<?= $i ?>][resources_school]" ><?= htmlspecialchars($row['resources_school']) ?></textarea></td>
                <td><textarea name="entries[<?= $i ?>][resources_community]" ><?= htmlspecialchars($row['resources_community']) ?></textarea></td>
                <td><textarea name="entries[<?= $i ?>][collaborating_agencies]" ><?= htmlspecialchars($row['collaborating_agencies']) ?></textarea></td>
                <td><input type="number" step="0.01" min="0" name="entries[<?= $i ?>][budget]" value="<?= htmlspecialchars($row['budget']) ?>"></td>
                <td><textarea name="entries[<?= $i ?>][mov]" ><?= htmlspecialchars($row['mov']) ?></textarea></td>
                <td><button type="button" class="btn-small danger" onclick="removeRow(this)">Remove</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn-small" onclick="addRow()">+ Add Row</button>
    </div>

    <button type="submit" class="btn primary"><?= $editMode ? 'Update Program Design' : 'Submit Program Design' ?></button>
  </form>
</div>

<style>
/* Minimal responsive styling; matches your dashboard look */
.pd-form{ max-width:1100px; margin:0 auto; padding:20px; color:#1f2937; }
.pd-form h2{ color:#192f5d; margin:0 0 12px; }
.alert{ border-radius:8px; padding:12px 14px; margin:12px 0; border:1px solid #e5e7eb; }
.alert.success{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
.alert.error{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }

.grid-2{ display:grid; grid-template-columns: 1fr; gap:14px; }
@media (min-width: 720px){ .grid-2{ grid-template-columns: 1fr 1fr; } }

.form-group label{ display:block; font-weight:700; margin:0 0 6px; color:#192f5d; }
.form-group input, .form-group textarea, .entry-table input, .entry-table textarea{
  width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; outline:none;
}
.form-group input:focus, .form-group textarea:focus, .entry-table input:focus, .entry-table textarea:focus{
  box-shadow:0 0 0 4px rgba(147,197,253,.4); border-color:#93c5fd;
}

.form-section{ margin-top:18px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; }
.section-title{ padding:12px 14px; border-bottom:1px solid #e5e7eb; background:#f7f9fc; }
.section-title h3{ margin:0; color:#192f5d; }

.table-wrap{ overflow:auto; }
.entry-table{ width:100%; border-collapse:separate; border-spacing:0; min-width: 980px; }
.entry-table th{ position:sticky; top:0; background:#eef2ff; color:#192f5d; padding:10px; text-align:left; font-weight:700; }
.entry-table td{ padding:10px; border-top:1px solid #e5e7eb; vertical-align:top; }
.entry-table textarea{ min-height:70px; }

.btn{ display:inline-flex; align-items:center; gap:8px; background:#192f5d; color:#fff; padding:10px 16px;
      border-radius:10px; border:1px solid transparent; text-decoration:none; cursor:pointer; }
.btn.primary:hover{ background:#14254a; }
.btn-small{ padding:6px 10px; background:#192f5d; color:#fff; border-radius:8px; border:none; cursor:pointer; }
.btn-small:hover{ opacity:.95; }
.btn-small.danger{ background:#dc2626; }
.btn-small.danger:hover{ background:#b91c1c; }

@media (max-width: 560px){
  .btn, .btn-small{ width:100%; justify-content:center; }
}
</style>

<script>
let entryCount = <?= count($form['entries']) ?>;

function addRow(){
  const tbody = document.getElementById('entries-container');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="entries[${entryCount}][program]" required></td>
    <td><input type="text" name="entries[${entryCount}][duration]" required></td>
    <td><textarea name="entries[${entryCount}][objectives]" required></textarea></td>
    <td><textarea name="entries[${entryCount}][persons_involved]"></textarea></td>
    <td><textarea name="entries[${entryCount}][resources_school]"></textarea></td>
    <td><textarea name="entries[${entryCount}][resources_community]"></textarea></td>
    <td><textarea name="entries[${entryCount}][collaborating_agencies]"></textarea></td>
    <td><input type="number" step="0.01" min="0" name="entries[${entryCount}][budget]"></td>
    <td><textarea name="entries[${entryCount}][mov]"></textarea></td>
    <td><button type="button" class="btn-small danger" onclick="removeRow(this)">Remove</button></td>
  `;
  tbody.appendChild(tr);
  entryCount++;
}
function removeRow(btn){
  const tbody = document.getElementById('entries-container');
  if (tbody.children.length > 1) btn.closest('tr').remove();
  else alert('At least one row is required.');
}
</script>

<?php require_once '../includes/footer.php'; ?>
