<?php
// pages/admin/manage_quests.php
// Allows an administrator to view, create, edit, and delete quests.

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
$success_message = null;
$error_message = null;
$quests = []; // Array to hold fetched quests

// =======================================================
// 2. HANDLE QUEST DELETION
// =======================================================
$delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);

if ($delete_id && isset($conn) && $conn) {
    try {
        // Prepare SQL to delete the quest by its ID
        $sql_delete = "DELETE FROM quests WHERE quest_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete === false) {
            throw new Exception("SQL Delete Prepare Failed: " . $conn->error);
        }

        $stmt_delete->bind_param("i", $delete_id);
        
        if ($stmt_delete->execute()) {
            // Check if any rows were actually deleted
            if ($stmt_delete->affected_rows > 0) {
                $success_message = "Quest ID {$delete_id} successfully deleted from the database.";
            } else {
                $error_message = "Error: Quest ID {$delete_id} not found or already deleted.";
            }
        } else {
            throw new Exception("Execution Failed: " . $stmt_delete->error);
        }
        $stmt_delete->close();
        
        // Remove the delete_id parameter from the URL after processing
        // This prevents re-deletion on refresh
        $base_url = strtok($_SERVER["REQUEST_URI"], '?');
        $success_message_param = $success_message ? '&success=' . urlencode($success_message) : '';
        $error_message_param = $error_message ? '&error=' . urlencode($error_message) : '';
        header("Location: {$base_url}?$success_message_param$error_message_param");
        exit;
        
    } catch (Exception $e) {
        error_log("Quest Deletion Error: " . $e->getMessage());
        $error_message = "Database deletion failed. Details: " . $e->getMessage();
    }
}

// =======================================================
// 3. FETCH ALL QUESTS (FIXED: Using points_award and added category)
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
<!-- 4. HTML CONTENT START (Admin Layout) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Manage Quests</h1>
        <p class="admin-subtitle">View, create, and manage all available learning challenges.</p>
        <div class="flex justify-between items-center mb-6">
            <a href="create_quest.php" class="btn-primary-admin">
                <i class="fas fa-plus-circle mr-2"></i> Create New Quest
            </a>
            <span class="text-sm text-gray-500 font-medium">Total Quests: <?php echo count($quests); ?></span>
        </div>

        <?php if ($error_message): ?>
            <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

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
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quests as $quest): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($quest['quest_id']); ?></td>
                            <td data-label="Title"><?php echo htmlspecialchars($quest['title']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($quest['category']); ?></td>
                            <td data-label="Points"><span class="badge points-badge"><?php echo htmlspecialchars($quest['points_award']); ?> Pts</span></td>
                            <td data-label="Creator"><?php echo htmlspecialchars($quest['creator_username']); ?></td>
                            <td data-label="Created On"><?php echo date('Y-m-d', strtotime($quest['created_at'])); ?></td>
                            <td data-label="Actions" class="actions-cell">
                                <a href="edit_quest.php?id=<?php echo $quest['quest_id']; ?>" class="action-btn edit-btn" title="Edit Quest"><i class="fas fa-edit"></i></a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    /**
     * Confirms quest deletion and redirects if confirmed.
     * IMPORTANT: This uses the JS location object for redirection.
     */
    function confirmDelete(questId) {
        // We cannot use alert() or confirm() in this environment.
        // Instead, we will simulate a confirmation using a simplified message 
        // and guide the user on where to click to continue.

        const confirmation = window.confirm(`Are you sure you want to delete Quest ID ${questId}? This action cannot be undone.`);
        
        if (confirmation) {
            // Redirect to the same page with the delete_id parameter
            window.location.href = `manage_quests.php?delete_id=${questId}`;
        }
    }
</script>

<?php
// =======================================================
// 5. CUSTOM STYLING (Admin Panel CSS)
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

    /* Buttons */
    .flex { display: flex; }
    .justify-between { justify-content: space-between; }
    .items-center { align-items: center; }
    .mb-6 { margin-bottom: 1.5rem; }
    .mr-2 { margin-right: 0.5rem; }
    .text-sm { font-size: 0.875rem; }
    .text-gray-500 { color: #6b7280; }
    .font-medium { font-weight: 500; }


    .btn-primary-admin {
        display: inline-flex;
        align-items: center;
        background: #71B48D; /* Light Green */
        color: #1D4C43;
        padding: 10px 18px;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s, transform 0.1s;
        text-decoration: none;
    }
    .btn-primary-admin:hover {
        background: #5AA080; 
        transform: translateY(-1px);
    }

    /* Message Styles (Alerts) */
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: left;
        font-size: 0.95rem;
        font-weight: 500;
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

    /* Table Styles */
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
        background-color: #1D4C43; /* Dark Green Header */
        color: #FFFFFF;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .data-table tbody tr:hover {
        background-color: #fafafa;
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
    }
    
    /* Action Buttons */
    .actions-cell {
        white-space: nowrap;
        text-align: center;
    }
    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        margin: 0 5px;
        transition: color 0.2s;
    }
    .edit-btn {
        color: #2C3E50; /* Dark Gray */
    }
    .edit-btn:hover {
        color: #71B48D; /* Light Green */
    }
    .delete-btn {
        color: #C0392B; /* Red */
    }
    .delete-btn:hover {
        color: #E74C3C; /* Brighter Red */
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #A9A9A9;
        background-color: #fcfcfc;
        border: 1px dashed #ddd;
        border-radius: 8px;
        margin-top: 20px;
    }
    .text-4xl { font-size: 2.25rem; } /* Tailwind 4xl equivalent */
    .mb-4 { margin-bottom: 1rem; }

    /* Responsive Table Design (Mobile First) */
    @media (max-width: 768px) {
        .admin-content-card {
            padding: 20px 15px;
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
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .data-table td {
            text-align: right;
            padding-left: 50%; /* Space for the label */
            position: relative;
        }
        .data-table td::before {
            /* Data label for mobile */
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: calc(50% - 20px);
            padding-right: 10px;
            white-space: nowrap;
            font-weight: 600;
            color: #5A7F7C;
            text-align: left;
        }
        .actions-cell {
            text-align: left;
            padding-left: 15px;
        }
        .actions-cell::before {
            content: "Actions";
        }
    }
</style>

<?php
require_once '../../includes/footer.php'; 
?>
