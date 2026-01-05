<?php
// pages/view_moderation_history.php
require_once '../includes/header.php';

$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || !in_array($user_role, ['moderator', 'admin'])) {
    header('Location: ../index.php?error=unauthorized');
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$error_message = null;
$student = null;
$moderation_records = [];

if (!$conn) {
    $error_message = 'Database connection failed.';
} else {
    try {
        // Fetch student info
        $sql = "SELECT u.User_id, u.Username, s.Student_id FROM User u 
                JOIN Student s ON u.User_id = s.User_id 
                WHERE s.Student_id = ? LIMIT 1";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $student = $res->fetch_assoc();
            } else {
                $error_message = 'Student not found.';
            }
            $stmt->close();
        }

        // Fetch moderation records
        if ($student) {
            $history_sql = "SELECT smr.*, u.Username as moderator_name
                            FROM student_moderation_records smr
                            JOIN User u ON smr.User_id = u.User_id
                            WHERE smr.student_id = ?
                            ORDER BY smr.date_time DESC";
            
            if ($stmt = $conn->prepare($history_sql)) {
                $stmt->bind_param('i', $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $moderation_records[] = $row;
                }
                $stmt->close();
            }
        }

    } catch (Exception $e) {
        $error_message = 'Query error: ' . $e->getMessage();
    }
}
?>

<main class="page-content moderation-history">
    <div class="container">
        <a href="view_student.php?student_id=<?php echo $student_id; ?>" class="btn-link">← Back to Student</a>
        <h1 class="page-title">Moderation History</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
            <div class="history-card">
                <div class="student-info-header">
                    <h2><?php echo htmlspecialchars($student['Username']); ?>'s Moderation Records</h2>
                </div>

                <?php if (empty($moderation_records)): ?>
                    <div class="no-records">
                        <i class="fas fa-info-circle"></i>
                        <p>No moderation records found for this student.</p>
                    </div>
                <?php else: ?>
                    <div class="records-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                    <th>Duration (Days)</th>
                                    <th>Moderator/Admin</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moderation_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($record['date_time'])); ?></td>
                                        <td>
                                            <span class="action-badge action-<?php echo strtolower(str_replace(' ', '-', $record['reason'])); ?>">
                                                <?php echo htmlspecialchars($record['reason']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['duration'] > 0 ? $record['duration'] . ' days' : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($record['moderator_name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($record['description'], 0, 50)) . (strlen($record['description']) > 50 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .moderation-history {
        padding: 40px 0;
    }

    .btn-link {
        display: inline-block;
        color: #1D4C43;
        text-decoration: none;
        font-weight: 500;
        margin-bottom: 20px;
        transition: color 0.3s;
    }

    .btn-link:hover {
        color: #71B48D;
    }

    .page-title {
        color: #1D4C43;
        font-size: 2.5rem;
        margin: 20px 0 30px;
    }

    .history-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .student-info-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }

    .student-info-header h2 {
        color: #1D4C43;
        margin: 0;
    }

    .no-records {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .no-records i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
        display: block;
    }

    .records-table {
        overflow-x: auto;
    }

    .records-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .records-table thead {
        background-color: #f5f5f5;
    }

    .records-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #1D4C43;
        border-bottom: 2px solid #ddd;
    }

    .records-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }

    .records-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    .action-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .action-temporary-ban {
        background-color: #ffe0e0;
        color: #E53E3E;
    }

    .action-mute-post {
        background-color: #e3f2fd;
        color: #1976d2;
    }

    .action-mute-comment {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }

    .action-unban {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .action-unmute-post {
        background-color: #e0f2f1;
        color: #00796b;
    }

    .action-unmute-comment {
        background-color: #fce4ec;
        color: #c2185b;
    }

    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-error {
        background-color: #ffe0e0;
        color: #c92a2a;
        border-left: 4px solid #c92a2a;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
