<?php
// pages/admin/manage_submissions.php
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
$submissions = [];

// Set up the status filter from the URL
$filter_status = $_GET['status'] ?? 'pending';
$valid_statuses = ['pending', 'completed', 'rejected', 'all'];
if (!in_array($filter_status, $valid_statuses)) {
    $filter_status = 'pending';
}

// Construct the query to get data from the new 'submissions' and 'students' tables
$query = "
    SELECT
        s.submission_id,
        st.username,
        q.title AS quest_title,
        q.points_award,
        s.status,
        s.submitted_at
    FROM
        submissions s
    JOIN
        students st ON s.user_id = st.student_id -- THIS LINE IS FIXED
    JOIN
        quests q ON s.quest_id = q.quest_id
";

$params = [];
$types = '';

if ($filter_status !== 'all') {
    $query .= " WHERE s.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$query .= " ORDER BY s.submitted_at DESC";

if (!$conn) {
    $error_message = "Database connection failed.";
} else {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
             throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("SQL execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        $error_message = "A database query error occurred: " . $e->getMessage();
    }
}
?>

<main class="page-content admin-submissions">
    <div class="container">
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-tasks"></i> Manage Submissions</h1>
            <p class="subtitle">Review and process all student quest submissions.</p>
        </header>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <nav class="submission-filter-nav">
            <a href="?status=pending" class="btn btn-filter <?php echo ($filter_status == 'pending') ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i> Pending
            </a>
            <a href="?status=completed" class="btn btn-filter <?php echo ($filter_status == 'completed') ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Approved
            </a>
            <a href="?status=rejected" class="btn btn-filter <?php echo ($filter_status == 'rejected') ? 'active' : ''; ?>">
                <i class="fas fa-times-circle"></i> Rejected
            </a>
            <a href="?status=all" class="btn btn-filter <?php echo ($filter_status == 'all') ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i> View All
            </a>
        </nav>

        <section class="admin-data-section submission-list">
            <header class="section-header">
                <h2><?php echo ucfirst($filter_status); ?> Submissions (<?php echo count($submissions); ?>)</h2>
            </header>

            <?php if (!empty($submissions)): ?>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Quest Title</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($submission['submission_id']); ?></td>
                                    <td data-label="Student"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($submission['username']); ?></td>
                                    <td data-label="Quest Title"><?php echo htmlspecialchars($submission['quest_title']); ?></td>
                                    <td data-label="Points"><?php echo htmlspecialchars($submission['points_award']); ?> Pts</td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?php echo strtolower($submission['status']); ?>">
                                            <?php echo ($submission['status'] === 'completed') ? 'Approved' : ucfirst($submission['status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Submitted On">
                                        <?php echo $submission['submitted_at'] ? date('d M Y, h:i A', strtotime($submission['submitted_at'])) : 'N/A'; ?>
                                    </td>
                                    <td data-label="Action">
                                        <a href="review_submission.php?id=<?php echo $submission['submission_id']; ?>" class="btn btn-sm btn-primary">
                                            <?php echo ($submission['status'] == 'pending') ? 'Review Now' : 'View Details'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-smile large-icon"></i>
                    <h3>No Submissions Found</h3>
                    <p>There are currently no "<?php echo htmlspecialchars($filter_status); ?>" submissions to display.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>