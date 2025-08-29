<?php
require_once '../includes/auth.php';
redirectIfNotCoordinator();

require_once '../includes/db.php';

$editMode = false;
$reportData = [
    'department' => $_SESSION['department'] ?? '',
    'assessment_date' => '',
    'participant_name' => '',
    'location' => '',
    'family_profile' => '',
    'community_concerns' => '',
    'other_needs' => '',
    'kabayani_buhay' => '',
    'kabayani_panginoon' => '',
    'kabayani_kalikasan' => '',
    'kabayani_kultura' => '',
    'kabayani_turismo' => '',
    'program_buhay' => '',
    'program_panginoon' => '',
    'program_kalikasan' => '',
    'program_kultura' => '',
    'program_turismo' => '',
    'resources_buhay' => '',
    'resources_panginoon' => '',
    'resources_kalikasan' => '',
    'resources_kultura' => '',
    'resources_turismo' => ''
];

// Check if editing an existing report
if (isset($_GET['edit'])) {
    $reportId = $_GET['edit'];
    
    // Verify this report belongs to the current coordinator and is rejected
    $stmt = $pdo->prepare("SELECT * FROM community_needs_reports WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
    $stmt->execute([$reportId, $_SESSION['user_id']]);
    $existingReport = $stmt->fetch();
    
    if ($existingReport) {
        $editMode = true;
        $reportData = $existingReport;
    }
}

// Start session to display messages


// Check for messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    
    // Clear the message after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Edit' : 'New'; ?> Community Needs Assessment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .header-info div {
            flex: 1;
            padding: 0 10px;
        }
        .edit-notice {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $editMode ? 'EDIT' : 'NEW'; ?> COMMUNITY NEEDS ASSESSMENT CONSOLIDATED REPORT</h1>
        
        <?php if ($editMode): ?>
            <div class="edit-notice">
                ⚠️ You are editing a rejected report. Please make the necessary changes and resubmit.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/includes/process_form.php">
            <?php if ($editMode): ?>
                <input type="hidden" name="report_id" value="<?php echo $reportData['id']; ?>">
            <?php endif; ?>
            
            <div class="header-info">
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($reportData['department']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="assessment_date">A. Community Needs Assessment Date of Conduct:</label>
                <input type="date" id="assessment_date" name="assessment_date" value="<?php echo htmlspecialchars($reportData['assessment_date']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="participant_name">B. Name of Participant:</label>
                <input type="text" id="participant_name" name="participant_name" value="<?php echo htmlspecialchars($reportData['participant_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="location">C. Location/Purok/District:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($reportData['location']); ?>" required>
            </div>
            
            <div class="form-section">
                <h2>I. Community Needs Assessment Results</h2>
                
                <div class="form-group">
                    <label for="family_profile">A. Family Profile:</label>
                    <textarea id="family_profile" name="family_profile" required><?php echo htmlspecialchars($reportData['family_profile']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="community_concerns">B. Community Concerns:</label>
                    <textarea id="community_concerns" name="community_concerns" required><?php echo htmlspecialchars($reportData['community_concerns']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="other_needs">C. Other Identified Needs:</label>
                    <textarea id="other_needs" name="other_needs"><?php echo htmlspecialchars($reportData['other_needs']); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h2>II. Identified Prevailing Needs of the Community</h2>
                
                <div class="form-group">
                    <label for="kabayani_buhay">A. Kabayani ng Buhay:</label>
                    <textarea id="kabayani_buhay" name="kabayani_buhay" required><?php echo htmlspecialchars($reportData['kabayani_buhay']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kabayani_panginoon">B. Kabayani ng Panginoon:</label>
                    <textarea id="kabayani_panginoon" name="kabayani_panginoon" required><?php echo htmlspecialchars($reportData['kabayani_panginoon']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kabayani_kalikasan">C. Kabayani ng Kalikasan:</label>
                    <textarea id="kabayani_kalikasan" name="kabayani_kalikasan" required><?php echo htmlspecialchars($reportData['kabayani_kalikasan']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kabayani_kultura">D. Kabayani ng Kultura:</label>
                    <textarea id="kabayani_kultura" name="kabayani_kultura" required><?php echo htmlspecialchars($reportData['kabayani_kultura']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="kabayani_turismo">E. Kabayani ng Turismo:</label>
                    <textarea id="kabayani_turismo" name="kabayani_turismo" required><?php echo htmlspecialchars($reportData['kabayani_turismo']); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h2>III. Recommended Outreach Program</h2>
                
                <div class="form-group">
                    <label for="program_buhay">A. Kabayani ng Buhay:</label>
                    <textarea id="program_buhay" name="program_buhay" required><?php echo htmlspecialchars($reportData['program_buhay']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="program_panginoon">B. Kabayani ng Panginoon:</label>
                    <textarea id="program_panginoon" name="program_panginoon" required><?php echo htmlspecialchars($reportData['program_panginoon']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="program_kalikasan">C. Kabayani ng Kalikasan:</label>
                    <textarea id="program_kalikasan" name="program_kalikasan" required><?php echo htmlspecialchars($reportData['program_kalikasan']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="program_kultura">D. Kabayani ng Kultura:</label>
                    <textarea id="program_kultura" name="program_kultura" required><?php echo htmlspecialchars($reportData['program_kultura']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="program_turismo">E. Kabayani ng Turismo:</label>
                    <textarea id="program_turismo" name="program_turismo" required><?php echo htmlspecialchars($reportData['program_turismo']); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h2>IV. Allocation of Resources (Funds and Equipment Needed)</h2>
                
                <div class="form-group">
                    <label for="resources_buhay">A. Kabayani ng Buhay:</label>
                    <textarea id="resources_buhay" name="resources_buhay" required><?php echo htmlspecialchars($reportData['resources_buhay']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="resources_panginoon">B. Kabayani ng Panginoon:</label>
                    <textarea id="resources_panginoon" name="resources_panginoon" required><?php echo htmlspecialchars($reportData['resources_panginoon']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="resources_kalikasan">C. Kabayani ng Kalikasan:</label>
                    <textarea id="resources_kalikasan" name="resources_kalikasan" required><?php echo htmlspecialchars($reportData['resources_kalikasan']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="resources_kultura">D. Kabayani ng Kultura:</label>
                    <textarea id="resources_kultura" name="resources_kultura" required><?php echo htmlspecialchars($reportData['resources_kultura']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="resources_turismo">E. Kabayani ng Turismo:</label>
                    <textarea id="resources_turismo" name="resources_turismo" required><?php echo htmlspecialchars($reportData['resources_turismo']); ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="submit-btn"><?php echo $editMode ? 'Update' : 'Submit'; ?> Assessment</button>
        </form>
    </div>
</body>
</html>