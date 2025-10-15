<?php
// pages/moderator/manage_users.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// =======================================================
// 2. DATA FETCHING (UPDATED TO ONLY FETCH STUDENTS)
// =======================================================
$error_message = null;
$students = [];

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        // This query now only fetches users from the 'students' table.
        $query = "
            SELECT student_id, username, email, total_points, created_at
            FROM students
            ORDER BY created_at DESC
        ";
        
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
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
            <h1 class="page-title"><i class="fas fa-users"></i> View Students</h1>
            <p class="subtitle">A list of all registered students on the EcoQuest platform.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <section class="admin-data-section user-list">
            <header class="section-header">
                <h2>All Students (<?php echo count($students); ?>)</h2>
            </header>

            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Points</th>
                                <th>Joined On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td data-label="Username">
                                        <i class="fas fa-user-graduate user-icon"></i> 
                                        <?php echo htmlspecialchars($student['username']); ?>
                                    </td>
                                    <td data-label="Email"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td data-label="Points"><?php echo number_format($student['total_points']); ?> Pts</td>
                                    <td data-label="Joined On"><?php echo date('d M Y', strtotime($student['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>No Students Found</h3></div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>