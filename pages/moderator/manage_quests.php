<?php
// pages/moderator/manage_quests.php
// Allows a moderator to view all quests in read-only mode.

// --- Include header/config ---
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK & INITIALIZATION
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

// Allow only logged-in moderators
if (!$is_logged_in || $user_role !== 'moderator') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// Initialize variables
$error_message = null;
$quests = [];

// =======================================================
// 2. FETCH QUESTS (Read-only)
// =======================================================
$quests = [];
$error_message = null;
if (isset($conn) && $conn) {
    try {
        // This query now checks both admins and moderators tables for the creator's name
        $sql_fetch = "
            SELECT
                q.quest_id, q.title, q.description, q.points_award, q.category, q.created_at,
                COALESCE(a.username, m.username, 'N/A') AS creator_username
            FROM quests q
            LEFT JOIN admins a ON q.created_by = a.admin_id
            LEFT JOIN moderators m ON q.created_by = m.moderator_id
            ORDER BY q.created_at DESC
        ";
        
        $result = $conn->query($sql_fetch);
        if ($result) {
            $quests = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            throw new Exception("SQL Fetch Error: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "Failed to load quests. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Database connection is unavailable.";
}
?>

<!-- ======================================================= -->
<!-- 3. HTML CONTENT START (Moderator Layout - Read-only) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title"><i class="fas fa-map"></i> View Quests</h1>
        <p class="admin-subtitle">Browse and review all available learning quests. (Read-only access)</p>

        <!-- Message Alerts -->
        <?php if ($error_message): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($quests) && !$error_message): ?>
            <div class="empty-state">
                <i class="fas fa-star text-4xl mb-4"></i>
                <p>No quests found at the moment.</p>
            </div>
        <?php elseif (!empty($quests)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Points</th>
                            <th>Creator</th>
                            <th>Created On</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quests as $quest): ?>
                            <tr>
                                <td data-label="ID" class="font-bold text-gray-700"><?php echo htmlspecialchars($quest['quest_id']); ?></td>
                                <td data-label="Title" class="font-medium text-green-700"><?php echo htmlspecialchars($quest['title']); ?></td>
                                <td data-label="Category" class="text-gray-600"><?php echo htmlspecialchars($quest['category']); ?></td>
                                <td data-label="Points">
                                    <span class="badge points-badge">
                                        <?php echo htmlspecialchars($quest['points_award']) . ' Pts'; ?>
                                    </span>
                                </td>
                                <td data-label="Creator" class="text-gray-600"><?php echo htmlspecialchars($quest['creator_username'] ?? 'N/A'); ?></td>
                                <td data-label="Created On" class="text-sm">
                                    <?php echo date('Y-m-d', strtotime($quest['created_at'])); ?>
                                </td>
                                <td data-label="Description" class="text-gray-700 small-text">
                                    <?php echo htmlspecialchars($quest['description']); ?>
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
<!-- 4. MODERATOR STYLING (Same Design as Admin, but Simplified) -->
<!-- ======================================================= -->
<style>
    .admin-page {
        padding: 30px 20px;
        background-color: #FAFAF0;
        min-height: 90vh;
        font-family: 'Inter', sans-serif;
    }
    .admin-content-card {
        max-width: 1200px;
        margin: 0 auto;
        background: #FFFFFF;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
        text-align: left;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .error-message {
        background-color: #FADBD8;
        border: 1px solid #E74C3C;
        color: #C0392B;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background-color: #FFFFFF;
    }
    .data-table th, .data-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }
    .data-table th {
        background-color: #1D4C43;
        color: #FFFFFF;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .data-table tbody tr:hover {
        background-color: #f9fafb;
    }

    /* Quest Specific Badges */
    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .points-badge {
        background-color: #F8D7DA; 
        color: #721C24; 
        /* THIS IS THE MAGIC LINE TO FORCE IT ON ONE LINE */
        white-space: nowrap; 
    }

    /* Description cell */
    .small-text {
        font-size: 0.85rem;
        line-height: 1.4;
        color: #374151;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #A9A9A9;
        background-color: #fcfcfc;
        border: 1px dashed #ddd;
        border-radius: 8px;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
        }
        .data-table tr {
            margin-bottom: 15px;
            border: 1px solid #f0f0f0;
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
            left: 10px;
            width: calc(50% - 20px);
            font-weight: 600;
            color: #5A7F7C;
            text-align: left;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>
