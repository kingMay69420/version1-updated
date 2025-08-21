<?php
require_once '../includes/auth.php';
redirectIfNotAdmin();

require_once '../includes/db.php';

// Get all coordinators
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'coordinator' ORDER BY is_active DESC, username ASC");
$stmt->execute();
$coordinators = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="manage-coordinators">
    <h2>Manage Coordinators</h2>
    
    <a href="create-coordinator.php" class="btn">Create New Coordinator</a>
    
    <div class="coordinators-table">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coordinators as $coordinator): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($coordinator['username']); ?></td>
                        <td><?php echo htmlspecialchars($coordinator['email']); ?></td>
                        <td><?php echo htmlspecialchars($coordinator['department']); ?></td>
                        <td>
                            <?php if ($coordinator['is_active']): ?>
                                <span class="status-badge active">Active</span>
                            <?php else: ?>
                                <span class="status-badge inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($coordinator['is_active']): ?>
                                <a href="toggle-coordinator.php?id=<?php echo $coordinator['id']; ?>&action=deactivate" class="btn-small warning">Deactivate</a>
                            <?php else: ?>
                                <a href="toggle-coordinator.php?id=<?php echo $coordinator['id']; ?>&action=activate" class="btn-small success">Activate</a>
                            <?php endif; ?>
                            <a href="delete-coordinator.php?id=<?php echo $coordinator['id']; ?>" class="btn-small danger" onclick="return confirm('Are you sure you want to delete this coordinator?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>