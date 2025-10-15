<?php
// pages/admin/manage_users.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. FILTERING & DATA FETCHING (UPDATED)
// =======================================================
$error_message = null;
$users = [];

$filter_role = $_GET['role'] ?? 'all';
$valid_roles = ['all', 'student', 'moderator', 'admin'];
if (!in_array($filter_role, $valid_roles)) {
    $filter_role = 'all';
}

$query = '';
if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    // Build the query based on the selected filter
    switch ($filter_role) {
        case 'student':
            $query = "SELECT student_id AS id, username, email, 'student' AS role, total_points AS current_points, created_at FROM students ORDER BY created_at DESC";
            break;
        case 'moderator':
            $query = "SELECT moderator_id AS id, username, email, 'moderator' AS role, 0 AS current_points, created_at FROM moderators ORDER BY created_at DESC";
            break;
        case 'admin':
            $query = "SELECT admin_id AS id, username, email, 'admin' AS role, 0 AS current_points, created_at FROM admins ORDER BY created_at DESC";
            break;
        case 'all':
        default:
            $query = "
                (SELECT student_id AS id, username, email, 'student' AS role, total_points AS current_points, created_at FROM students)
                UNION ALL
                (SELECT moderator_id AS id, username, email, 'moderator' AS role, 0 AS current_points, created_at FROM moderators)
                UNION ALL
                (SELECT admin_id AS id, username, email, 'admin' AS role, 0 AS current_points, created_at FROM admins)
                ORDER BY created_at DESC
            ";
            break;
    }

    try {
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            throw new Exception("Query failed: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = "A database query error occurred: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-users">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-users-cog"></i> Manage Users</h1>
            <p class="subtitle">View, filter, and manage all student and staff accounts.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <nav class="user-filter-nav">
            <?php foreach ($valid_roles as $role): ?>
                <a href="?role=<?php echo htmlspecialchars($role); ?>" 
                   class="btn btn-filter <?php echo ($filter_role === $role) ? 'active btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-<?php echo ($role === 'admin') ? 'shield-alt' : (($role === 'moderator') ? 'user-shield' : (($role === 'student') ? 'user-graduate' : 'list-ul')); ?>"></i> 
                    <?php echo htmlspecialchars(ucfirst($role)); ?>
                </a>
            <?php endforeach; ?>
            
            <a href="create_user.php" class="btn-create-user">
                <i class="fas fa-user-plus mr-2"></i> Register New User
            </a>
        </nav>
        
        <section class="admin-data-section user-list">
            <header class="section-header">
                <h2><?php echo htmlspecialchars(ucfirst($filter_role)); ?> Accounts (<?php echo count($users); ?>)</h2>
            </header>

            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Points</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td data-label="Username">
                                        <i class="fas fa-user-circle user-icon"></i> 
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="Role">
                                        <span class="status-badge status-<?php echo strtolower($user['role']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="Points"><?php echo number_format($user['current_points']); ?> Pts</td>
                                    <td data-label="Joined"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td data-label="Action">
                                        <div class="action-group">
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>&role=<?php echo $user['role']; ?>" class="btn-action-icon btn-action-edit" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn-action-icon btn-action-delete" title="Delete User">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>No Users Found</h3></div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>