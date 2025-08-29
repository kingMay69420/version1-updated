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
    // This block is for initial page load in edit mode
    $reportId = $_GET['edit'];
    
    // Verify this report belongs to the current coordinator
    $stmt = $pdo->prepare("SELECT * FROM community_needs_reports WHERE id = ? AND coordinator_id = ? AND status = 'rejected'");
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
        'assessment_date' => $_POST['assessment_date'],
        'participant_name' => $_POST['participant_name'],
        'location' => $_POST['location'],
        'family_profile' => $_POST['family_profile'],
        'community_concerns' => $_POST['community_concerns'],
        'other_needs' => $_POST['other_needs'],
        'kabayani_buhay' => $_POST['kabayani_buhay'],
        'kabayani_panginoon' => $_POST['kabayani_panginoon'],
        'kabayani_kalikasan' => $_POST['kabayani_kalikasan'],
        'kabayani_kultura' => $_POST['kabayani_kultura'],
        'kabayani_turismo' => $_POST['kabayani_turismo'],
        'program_buhay' => $_POST['program_buhay'],
        'program_panginoon' => $_POST['program_panginoon'],
        'program_kalikasan' => $_POST['program_kalikasan'],
        'program_kultura' => $_POST['program_kultura'],
        'program_turismo' => $_POST['program_turismo'],
        'resources_buhay' => $_POST['resources_buhay'],
        'resources_panginoon' => $_POST['resources_panginoon'],
        'resources_kalikasan' => $_POST['resources_kalikasan'],
        'resources_kultura' => $_POST['resources_kultura'],
        'resources_turismo' => $_POST['resources_turismo']
    ];
    
    // Validate required fields
    $errors = [];
    
    if (empty($reportData['department'])) $errors[] = "Department is required";
    if (empty($reportData['assessment_date'])) $errors[] = "Assessment Date is required";
    if (empty($reportData['participant_name'])) $errors[] = "Participant Name is required";
    if (empty($reportData['location'])) $errors[] = "Location is required";
    if (empty($reportData['family_profile'])) $errors[] = "Family Profile is required";
    
    if (empty($errors)) {
        if ($editMode) {
            // Update existing report
            $stmt = $pdo->prepare("UPDATE community_needs_reports SET 
                department = ?, 
                assessment_date = ?, 
                participant_name = ?, 
                location = ?, 
                family_profile = ?, 
                community_concerns = ?, 
                other_needs = ?, 
                kabayani_buhay = ?, 
                kabayani_panginoon = ?, 
                kabayani_kalikasan = ?, 
                kabayani_kultura = ?, 
                kabayani_turismo = ?, 
                program_buhay = ?, 
                program_panginoon = ?, 
                program_kalikasan = ?, 
                program_kultura = ?, 
                program_turismo = ?, 
                resources_buhay = ?, 
                resources_panginoon = ?, 
                resources_kalikasan = ?, 
                resources_kultura = ?, 
                resources_turismo = ?, 
                status = 'pending', 
                admin_notes = NULL, 
                updated_at = NOW() 
                WHERE id = ?");
            
            $stmt->execute([
                $reportData['department'],
                $reportData['assessment_date'],
                $reportData['participant_name'],
                $reportData['location'],
                $reportData['family_profile'],
                $reportData['community_concerns'],
                $reportData['other_needs'],
                $reportData['kabayani_buhay'],
                $reportData['kabayani_panginoon'],
                $reportData['kabayani_kalikasan'],
                $reportData['kabayani_kultura'],
                $reportData['kabayani_turismo'],
                $reportData['program_buhay'],
                $reportData['program_panginoon'],
                $reportData['program_kalikasan'],
                $reportData['program_kultura'],
                $reportData['program_turismo'],
                $reportData['resources_buhay'],
                $reportData['resources_panginoon'],
                $reportData['resources_kalikasan'],
                $reportData['resources_kultura'],
                $reportData['resources_turismo'],
                $reportId
            ]);
        } else {
            // Create new report
            $stmt = $pdo->prepare("INSERT INTO community_needs_reports (
                coordinator_id, 
                department, 
                assessment_date, 
                participant_name, 
                location, 
                family_profile,
                community_concerns, 
                other_needs,
                kabayani_buhay,
                kabayani_panginoon,
                kabayani_kalikasan,
                kabayani_kultura,
                kabayani_turismo,
                program_buhay,
                program_panginoon,
                program_kalikasan,
                program_kultura,
                program_turismo,
                resources_buhay,
                resources_panginoon,
                resources_kalikasan,
                resources_kultura,
                resources_turismo,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $reportData['department'],
                $reportData['assessment_date'],
                $reportData['participant_name'],
                $reportData['location'],
                $reportData['family_profile'],
                $reportData['community_concerns'],
                $reportData['other_needs'],
                $reportData['kabayani_buhay'],
                $reportData['kabayani_panginoon'],
                $reportData['kabayani_kalikasan'],
                $reportData['kabayani_kultura'],
                $reportData['kabayani_turismo'],
                $reportData['program_buhay'],
                $reportData['program_panginoon'],
                $reportData['program_kalikasan'],
                $reportData['program_kultura'],
                $reportData['program_turismo'],
                $reportData['resources_buhay'],
                $reportData['resources_panginoon'],
                $reportData['resources_kalikasan'],
                $reportData['resources_kultura'],
                $reportData['resources_turismo']
            ]);
        }
        
        header("Location: /coordinator/dashboard.php");
        exit();
    }
}