<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

$editMode = false;
$reportData = [
    'department' => $_SESSION['department'] ?? '',
    'activity_title' => '',
    'location' => '',
    'beneficiaries' => '',
    'date' => date('Y-m-d'),
    'activities' => '',
    'issues_concerns' => '',
    'recommendations' => ''
];

// Check if editing an existing report
if (isset($_GET['edit'])) {
    // This block is for initial page load in edit mode
    $reportId = $_GET['edit'];
    
    // Verify this report belongs to the current coordinator
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
    $stmt->execute([$reportId, $_SESSION['user_id']]);
    $existingReport = $stmt->fetch();
    
    if ($existingReport) {
        $editMode = true;
        $reportData = $existingReport;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = $_POST['report_id'] ?? null;
    $editMode = !empty($reportId);

    $reportData = [
        'department' => $_POST['department'],
        'activity_title' => $_POST['activity_title'],
        'location' => $_POST['location'],
        'beneficiaries' => $_POST['beneficiaries'],
        'date' => $_POST['date'],
        'activities' => $_POST['activities'],
        'issues_concerns' => $_POST['issues_concerns'],
        'recommendations' => $_POST['recommendations']
    ];
    
    // Validate required fields
    $errors = [];
    
    if (empty($reportData['department'])) $errors[] = "Department is required";
    if (empty($reportData['activity_title'])) $errors[] = "Activity Title is required";
    if (empty($reportData['location'])) $errors[] = "Location is required";
    if (empty($reportData['beneficiaries'])) $errors[] = "Beneficiaries are required";
    if (empty($reportData['date'])) $errors[] = "Date is required";
    if (empty($reportData['activities'])) $errors[] = "A description of activities is required";
    
    if (empty($errors)) {
        if ($editMode) {
            // Update existing report
            $stmt = $pdo->prepare("UPDATE reports SET 
                department = ?, 
                activity_title = ?, 
                location = ?, 
                beneficiaries = ?, 
                date = ?, 
                activities = ?, 
                issues_concerns = ?, 
                recommendations = ?, 
                status = 'pending', 
                admin_notes = NULL, 
                updated_at = NOW() 
                WHERE id = ?");
            
            $stmt->execute([
                $reportData['department'],
                $reportData['activity_title'],
                $reportData['location'],
                $reportData['beneficiaries'],
                $reportData['date'],
                $reportData['activities'],
                $reportData['issues_concerns'],
                $reportData['recommendations'],
                $reportId
            ]);
        } else {
            // Create new report
            $stmt = $pdo->prepare("INSERT INTO reports (
                coordinator_id, 
                department, 
                activity_title, 
                location, 
                beneficiaries, 
                date, 
                activities,
                issues_concerns, 
                recommendations,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $reportData['department'],
                $reportData['activity_title'],
                $reportData['location'],
                $reportData['beneficiaries'],
                $reportData['date'],
                $reportData['activities'],
                $reportData['issues_concerns'],
                $reportData['recommendations']
            ]);
        }
        
        header("Location: dashboard.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="new-report">
    <h2><?php echo $editMode ? 'Edit Monthly Accomplishment Report' : 'File New Monthly Accomplishment Report'; ?></h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" id="report-form">
        <?php if ($editMode): ?>
            <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($reportData['id']); ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($reportData['department']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="activity_title">Activity Title</label>
            <input type="text" id="activity_title" name="activity_title" value="<?php echo htmlspecialchars($reportData['activity_title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($reportData['location']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="beneficiaries">Beneficiaries</label>
            <input type="text" id="beneficiaries" name="beneficiaries" value="<?php echo htmlspecialchars($reportData['beneficiaries']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($reportData['date']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="activities">Activities</label>
            <textarea id="activities" name="activities" rows="4" required><?php echo htmlspecialchars($reportData['activities']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="issues_concerns">Issues and Concerns</label>
            <textarea id="issues_concerns" name="issues_concerns" rows="4"><?php echo htmlspecialchars($reportData['issues_concerns']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="recommendations">Recommendations</label>
            <textarea id="recommendations" name="recommendations" rows="4"><?php echo htmlspecialchars($reportData['recommendations']); ?></textarea>
        </div>
        
        <button type="submit" class="btn" id="submit-btn"><?php echo $editMode ? 'Update Report' : 'Submit Report'; ?></button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('submit-btn');
    
    function validateForm() {
        let isValid = true;
        
        // Check all required fields
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
            }
        });
        
        // Enable/disable submit button
        submitBtn.disabled = !isValid;
        if (isValid) {
            submitBtn.classList.add('active');
        } else {
            submitBtn.classList.remove('active');
        }
    }
    
    // Validate on form changes
    form.addEventListener('input', validateForm);
    
    // Initial validation
    validateForm();
});
</script>

<style>
.new-report {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-group textarea {
    min-height: 60px;
}

.btn {
    padding: 8px 15px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn:hover {
    background: #45a049;
}

.btn[disabled] {
    background: #cccccc;
    cursor: not-allowed;
}

.alert-error {
    background: #ffdddd;
    border-left: 4px solid #f44336;
    padding: 10px;
    margin-bottom: 15px;
}

.alert-error ul {
    margin: 0;
    padding-left: 20px;
}
</style>

<?php require_once '../includes/footer.php'; ?>