<?php
// pages/admin/manage_rewards.php
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$error_message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
$rewards = [];

// 2. HANDLE DELETE ACTION
$delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
if ($delete_id) {
    if (isset($conn) && $conn) {
        try {
            // Check if reward is in Redemption_History. If so, we can't delete.
            $stmt_check = $conn->prepare("SELECT Redemption_History_id FROM Redemption_History WHERE Reward_id = ? LIMIT 1");
            $stmt_check->bind_param("i", $delete_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                 throw new Exception("Cannot delete reward: It has already been redeemed by students. Please set it to 'Inactive' instead.");
            }
            $stmt_check->close();
            
            // Proceed with deletion
            $sql_delete = "DELETE FROM Reward WHERE Reward_id = ?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("i", $delete_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Reward ID {$delete_id} deleted successfully.";
                } else {
                    $error_message = "No reward found with ID {$delete_id}.";
                }
            } else {
                throw new Exception("Execution Failed: " . $stmt->error);
            }
            $stmt->close();

            header('Location: manage_rewards.php?success=' . urlencode($success_message ?? 'Reward deleted.'));
            exit;

        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// 3. FETCH ALL REWARDS (Updated Column Names)
if (isset($conn) && $conn) {
    try {
        $sql_fetch_all = "SELECT Reward_id, Reward_name, Description, Points_cost, Stock, Is_active FROM Reward ORDER BY Points_cost ASC";
        $result = $conn->query($sql_fetch_all);
        if ($result) {
            $rewards = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load rewards list. DB Error: " . $e->getMessage();
    }
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Manage Rewards Catalogue</h1>
        <p class="admin-subtitle">View, edit, and manage all available rewards.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="toolbar">
            <a href="create_reward.php" class="btn-primary-create">
                <i class="fas fa-plus mr-2"></i> Create New Reward
            </a>
        </div>

        <?php if (empty($rewards)): ?>
            <div class="no-data-message"><i class="fas fa-box-open"></i><p>No rewards found.</p></div>
        <?php else: ?>
            <div class="rewards-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Cost (Points)</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($reward['Reward_id']); ?></td>
                                <td data-label="Name" class="font-bold"><?php echo htmlspecialchars($reward['Reward_name']); ?></td>
                                <td data-label="Cost" class="text-center points-cost"><?php echo number_format($reward['Points_cost']); ?></td>
                                <td data-label="Stock" class="text-center">
                                    <?php 
                                        if ($reward['Stock'] == -1) {
                                            echo '<span class="status-unlimited"><i class="fas fa-infinity mr-1"></i> Unlimited</span>';
                                        } elseif ($reward['Stock'] > 0) {
                                            echo '<span class="status-in-stock">' . number_format($reward['Stock']) . ' left</span>';
                                        } else {
                                            echo '<span class="status-out-stock">Out of Stock</span>';
                                        }
                                    ?>
                                </td>
                                <td data-label="Status" class="text-center">
                                    <?php if ($reward['Is_active']): ?>
                                        <span class="badge active-badge">Active</span>
                                    <?php else: ?>
                                        <span class="badge inactive-badge">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions" class="actions-cell">
                                    <a href="edit_reward.php?id=<?php echo $reward['Reward_id']; ?>" class="action-btn edit-btn" title="Edit Reward">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $reward['Reward_id']; ?>, '<?php echo htmlspecialchars(addslashes($reward['Reward_name'])); ?>')" class="action-btn delete-btn" title="Delete Reward">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to permanently delete the reward "${name}" (ID: ${id})? This will fail if any student has already redeemed it.`)) {
            window.location.href = `manage_rewards.php?delete_id=${id}`;
        }
    }
</script>

<style>
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 1100px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; font-weight: 500; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .success-message { background-color: #DDEEE5; border: 1px solid #71B48D; color: #1D4C43; }
    .mr-2 { margin-right: 0.5rem; }
    .toolbar { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .btn-primary-create { background: #10b981; color: #FFFFFF; padding: 10px 18px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
    .btn-primary-create:hover { background: #059669; }
    .no-data-message { text-align: center; padding: 50px 20px; background-color: #F8F8F8; border-radius: 8px; color: #5A7F7C; }
    .no-data-message i { font-size: 3rem; margin-bottom: 10px; color: #71B48D; }
    .rewards-table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead { background-color: #E6F3EE; color: #1D4C43; }
    .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #F4F7F6; }
    .data-table th { font-weight: 700; font-size: 0.9rem; text-transform: uppercase; }
    .data-table tbody tr:hover { background-color: #f7fffb; }
    .font-bold { font-weight: 600; }
    .text-center { text-align: center; }
    .points-cost { font-weight: 700; color: #C0392B; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; }
    .active-badge { background-color: #71B48D; color: #1D4C43; }
    .inactive-badge { background-color: #F4F7F6; color: #777; }
    .status-unlimited { color: #2980B9; font-weight: 500; font-size: 0.85rem; }
    .status-in-stock { color: #1D4C43; font-weight: 500; }
    .status-out-stock { color: #C0392B; font-weight: 500; }
    .actions-cell { white-space: nowrap; text-align: center; }
    .action-btn { background: none; border: none; cursor: pointer; padding: 8px; margin: 0 3px; border-radius: 6px; font-size: 1rem; }
    .edit-btn { color: #2980B9; }
    .edit-btn:hover { background-color: #EAF2F8; }
    .delete-btn { color: #E74C3C; }
    .delete-btn:hover { background-color: #FADBD8; }
    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin-bottom: 15px; border: 1px solid #DDEEE5; border-radius: 8px; }
        .data-table td { text-align: right; padding-left: 50%; position: relative; }
        .data-table td::before { content: attr(data-label); position: absolute; left: 15px; width: calc(50% - 30px); text-align: left; font-weight: 600; color: #5A7F7C; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>