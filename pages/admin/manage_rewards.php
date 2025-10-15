<?php
// pages/admin/manage_rewards.php
// Displays a list of all rewards and provides options to Edit, Delete, or Create new rewards.

// --- Include header/config ---
require_once '../../includes/header.php'; 

// =======================================================
// 1. AUTHORIZATION CHECK & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

// Only allow access if the user is logged in AND is an admin
if (!$is_logged_in || $user_role !== 'admin') {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

// Initialize messages
$success_message = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS);
$error_message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);
$rewards = [];

// =======================================================
// 2. HANDLE DELETE ACTION
// =======================================================
$delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
if ($delete_id) {
    if (isset($conn) && $conn) {
        try {
            // Check if the reward is currently in redemption queues (optional, but good practice)
            // For now, we'll just delete it. Real-world apps might mark as inactive instead.
            $sql_delete = "DELETE FROM rewards WHERE reward_id = ?";
            $stmt = $conn->prepare($sql_delete);
            
            if ($stmt === false) {
                throw new Exception("SQL Delete Prepare Failed: " . $conn->error);
            }

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

            // Redirect to clear the GET parameter and display message
            header('Location: manage_rewards.php?success=' . urlencode($success_message ?? 'Reward deleted.'));
            exit;

        } catch (Exception $e) {
            error_log("Reward Deletion Error: " . $e->getMessage());
            $error_message = "Failed to delete reward. Error: " . $e->getMessage();
            // Fall-through to display the error
        }
    } else {
        $error_message = "Critical: Database connection object (\$conn) is unavailable.";
    }
}


// =======================================================
// 3. FETCH ALL REWARDS
// =======================================================
if (isset($conn) && $conn) {
    try {
        // Fix: Changed 'cost_points' to the correct column name 'points_cost' based on user's schema.
        $sql_fetch_all = "SELECT * FROM rewards ORDER BY points_cost ASC";
        $result = $conn->query($sql_fetch_all);
        
        if ($result) {
            $rewards = $result->fetch_all(MYSQLI_ASSOC);
        }

    } catch (Exception $e) {
        error_log("Reward Fetch Error: " . $e->getMessage());
        $error_message = "Failed to load rewards list. DB Error: " . $e->getMessage();
    }
}


?>

<!-- ======================================================= -->
<!-- 4. HTML CONTENT START (Admin Layout) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Manage Rewards Catalogue</h1>
        <p class="admin-subtitle">View, edit, and manage all available rewards users can redeem with points.</p>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Toolbar / Action Button -->
        <div class="toolbar">
            <a href="create_reward.php" class="btn-primary-create">
                <i class="fas fa-plus mr-2"></i> Create New Reward
            </a>
        </div>

        <?php if (empty($rewards)): ?>
            <div class="no-data-message">
                <i class="fas fa-box-open"></i>
                <p>No rewards found. Time to create some awesome rewards!</p>
            </div>
        <?php else: ?>
            
            <!-- Rewards Table -->
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
                                <td data-label="ID"><?php echo htmlspecialchars($reward['reward_id']); ?></td>
                                <td data-label="Name" class="font-bold"><?php echo htmlspecialchars($reward['name']); ?></td>
                                <!-- Fix: Changed array key from 'cost_points' to 'points_cost' -->
                                <td data-label="Cost" class="text-center points-cost"><?php echo number_format($reward['points_cost']); ?></td>
                                <td data-label="Stock" class="text-center">
                                    <?php 
                                        if ($reward['stock'] == -1) {
                                            echo '<span class="status-unlimited"><i class="fas fa-infinity mr-1"></i> Unlimited</span>';
                                        } elseif ($reward['stock'] > 0) {
                                            echo '<span class="status-in-stock">' . number_format($reward['stock']) . ' left</span>';
                                        } else {
                                            echo '<span class="status-out-stock">Out of Stock</span>';
                                        }
                                    ?>
                                </td>
                                <td data-label="Status" class="text-center">
                                    <?php if ($reward['is_active']): ?>
                                        <span class="badge active-badge">Active</span>
                                    <?php else: ?>
                                        <span class="badge inactive-badge">Draft/Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions" class="actions-cell">
                                    <a href="edit_reward.php?id=<?php echo $reward['reward_id']; ?>" class="action-btn edit-btn" title="Edit Reward">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $reward['reward_id']; ?>, '<?php echo htmlspecialchars(addslashes($reward['name'])); ?>')" class="action-btn delete-btn" title="Delete Reward">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <!-- /.rewards-table-container -->

        <?php endif; ?>

    </div> <!-- /.admin-content-card -->
</main>

<script>
    /**
     * Confirms reward deletion before redirecting.
     * IMPORTANT: Using window.confirm() here only because a custom modal is not implemented
     * within this single-file scope. In a full app, a custom modal is MANDATORY.
     */
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to permanently delete the reward "${name}" (ID: ${id})? This action cannot be undone.`)) {
            window.location.href = `manage_rewards.php?delete_id=${id}`;
        }
    }
</script>

<?php
// =======================================================
// 5. CUSTOM STYLING
// =======================================================
?>
<style>
    /* Admin Base Layout */
    .admin-page {
        padding: 30px 20px;
        background-color: #FAFAF0; /* Light gray-green background */
        min-height: 90vh;
        font-family: 'Inter', sans-serif;
    }
    .admin-content-card {
        max-width: 1100px;
        margin: 0 auto;
        background: #FFFFFF;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }
    .admin-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1D4C43; /* Dark Green */
        margin-bottom: 5px;
        border-bottom: 2px solid #DDEEE5;
        padding-bottom: 10px;
    }
    .admin-subtitle {
        font-size: 1rem;
        color: #5A7F7C;
        margin-bottom: 25px;
    }

    /* Messages */
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: left;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    .error-message {
        background-color: #FADBD8; /* Light Red */
        border: 1px solid #E74C3C;
        color: #C0392B; /* Darker Red */
    }
    .success-message {
        background-color: #DDEEE5; /* Light Green */
        border: 1px solid #71B48D;
        color: #1D4C43; /* Dark Green */
    }
    .mr-2 { margin-right: 0.5rem; }

    /* Toolbar & Buttons */
    .toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 20px;
    }
    .btn-primary-create {
        background: #10b981; /* Fresh Green */
        color: #FFFFFF;
        padding: 10px 18px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.3s, transform 0.1s;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
    }
    .btn-primary-create:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    .mr-1 { margin-right: 0.25rem; }

    /* No Data Message */
    .no-data-message {
        text-align: center;
        padding: 50px 20px;
        background-color: #F8F8F8;
        border: 1px dashed #DDEEE5;
        border-radius: 8px;
        color: #5A7F7C;
        margin-top: 20px;
    }
    .no-data-message i {
        font-size: 3rem;
        margin-bottom: 10px;
        color: #71B48D;
    }

    /* Table Styling */
    .rewards-table-container {
        overflow-x: auto;
        border: 1px solid #DDEEE5;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table thead {
        background-color: #E6F3EE; /* Lighter Green Header */
        color: #1D4C43;
    }
    .data-table th, .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #F4F7F6;
    }
    .data-table th {
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
    }
    .data-table tbody tr:hover {
        background-color: #f7fffb;
    }
    .font-bold { font-weight: 600; }
    .text-center { text-align: center; }
    .points-cost { 
        font-weight: 700; 
        color: #C0392B; /* A bit red for cost */
    }

    /* Status Badges */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .active-badge {
        background-color: #71B48D;
        color: #1D4C43;
    }
    .inactive-badge {
        background-color: #F4F7F6;
        color: #777;
    }
    .status-unlimited {
        color: #2980B9; /* Blue for unlimited */
        font-weight: 500;
        font-size: 0.85rem;
    }
    .status-in-stock {
        color: #1D4C43; 
        font-weight: 500;
    }
    .status-out-stock {
        color: #C0392B; 
        font-weight: 500;
    }


    /* Actions Column */
    .actions-cell {
        white-space: nowrap; /* Keep actions buttons on one line */
        text-align: center;
    }
    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        margin: 0 3px;
        border-radius: 6px;
        font-size: 1rem;
        transition: background 0.2s;
        display: inline-block;
    }
    .action-btn i {
        pointer-events: none; /* Allows click on the button itself */
    }
    .edit-btn {
        color: #2980B9; /* Blue */
    }
    .edit-btn:hover {
        background-color: #EAF2F8;
    }
    .delete-btn {
        color: #E74C3C; /* Red */
    }
    .delete-btn:hover {
        background-color: #FADBD8;
    }

    /* Mobile Responsiveness for Table */
    @media (max-width: 768px) {
        .admin-content-card {
            padding: 20px;
        }
        .data-table thead {
            display: none; /* Hide header row on small screens */
        }
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
        }
        .data-table tr {
            margin-bottom: 15px;
            border: 1px solid #DDEEE5;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        .data-table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
            border-bottom: 1px dashed #F4F7F6;
        }
        .data-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: calc(50% - 30px);
            padding-right: 10px;
            white-space: nowrap;
            text-align: left;
            font-weight: 600;
            color: #5A7F7C;
            font-size: 0.9rem;
        }
        .actions-cell {
            border-bottom: none;
            text-align: left;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .actions-cell::before {
            content: "Actions";
        }
    }
</style>

<?php
require_once '../../includes/footer.php'; 
?>
