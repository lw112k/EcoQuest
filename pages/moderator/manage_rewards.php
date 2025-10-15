<?php
// pages/moderator/manage_rewards.php
// Allows moderators to view the rewards catalogue in read-only mode.

// --- Include header/config ---
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

// Allow access only if logged in as moderator
if (!$is_logged_in || $user_role !== 'moderator') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

$error_message = null;
$rewards = [];

// =======================================================
// 2. FETCH ALL REWARDS (READ-ONLY)
// =======================================================
if (isset($conn) && $conn) {
    try {
        $sql_fetch = "SELECT * FROM rewards ORDER BY points_cost ASC";
        $result = $conn->query($sql_fetch);

        if ($result) {
            $rewards = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            if ($conn->errno == 1146) {
                $error_message = "Table 'rewards' not found in the database.";
            } else {
                throw new Exception("SQL Error: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        error_log("Moderator Reward Fetch Error: " . $e->getMessage());
        $error_message = "Failed to load rewards. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Critical: Database connection unavailable.";
}
?>

<!-- ======================================================= -->
<!-- 3. HTML CONTENT START (Moderator Layout - Read-only) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title"><i class="fas fa-gift"></i> View Rewards Catalogue</h1>
        <p class="admin-subtitle">Browse and review all available rewards. (Read-only access)</p>

        <!-- Error Message -->
        <?php if ($error_message): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($rewards) && !$error_message): ?>
            <div class="no-data-message">
                <i class="fas fa-box-open"></i>
                <p>No rewards found in the catalogue.</p>
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
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($reward['reward_id']); ?></td>
                                <td data-label="Name" class="font-bold text-green-700"><?php echo htmlspecialchars($reward['name']); ?></td>
                                <td data-label="Cost" class="text-center points-cost">
                                    <?php echo number_format($reward['points_cost']); ?>
                                </td>
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
                                        <span class="badge inactive-badge">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Description" class="small-text">
                                    <?php echo htmlspecialchars($reward['description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- ======================================================= -->
<!-- 4. MODERATOR STYLING (Same as Admin for consistency) -->
<!-- ======================================================= -->
<style>
    .admin-page {
        padding: 30px 20px;
        background-color: #FAFAF0;
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
        color: #1D4C43;
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
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    .error-message {
        background-color: #FADBD8;
        border: 1px solid #E74C3C;
        color: #C0392B;
    }

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

    /* Table */
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
        background-color: #E6F3EE;
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

    /* Badges and Stock Status */
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
        color: #2980B9;
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

    /* Description Text */
    .small-text {
        font-size: 0.85rem;
        color: #374151;
        line-height: 1.4;
    }

    /* Responsive Table (Mobile) */
    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
        }
        .data-table tr {
            margin-bottom: 15px;
            border: 1px solid #DDEEE5;
            border-radius: 8px;
        }
        .data-table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
        }
        .data-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: calc(50% - 30px);
            text-align: left;
            font-weight: 600;
            color: #5A7F7C;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>
