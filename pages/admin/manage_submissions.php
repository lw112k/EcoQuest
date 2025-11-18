<?php
// pages/admin/manage_submissions.php
require_once '../../includes/header.php';

// =======================================================
// 1. AUTHORIZATION CHECK
// =======================================================
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';
$conn = $conn ?? null;

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
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

// Construct the query
$query = "
    SELECT
        s.Student_quest_submission_id,
        u.Username,
        q.Title AS quest_title,
        q.Points_award,
        s.Status,
        s.Submission_date
    FROM
        Student_Quest_Submissions s
    JOIN
        Student st ON s.Student_id = st.Student_id
    JOIN
        User u ON st.User_id = u.User_id
    JOIN
        Quest q ON s.Quest_id = q.Quest_id
";

$params = [];
$types = '';

if ($filter_status !== 'all') {
    $query .= " WHERE s.Status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$query .= " ORDER BY s.Submission_date DESC";

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
                                    <td data-label="ID"><?php echo htmlspecialchars($submission['Student_quest_submission_id']); ?></td>
                                    <td data-label="Student"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($submission['Username']); ?></td>
                                    <td data-label="Quest Title"><?php echo htmlspecialchars($submission['quest_title']); ?></td>
                                    <td data-label="Points"><?php echo htmlspecialchars($submission['Points_award']); ?> Pts</td>
                                    <td data-label="Status">
                                        <span class="status-badge status-<?php echo strtolower($submission['Status']); ?>">
                                            <?php echo ($submission['Status'] === 'completed') ? 'Approved' : ucfirst($submission['Status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Submitted On">
                                        <?php echo $submission['Submission_date'] ? date('d M Y, h:i A', strtotime($submission['Submission_date'])) : 'N/A'; ?>
                                    </td>
                                    <td data-label="Action">
                                        <a href="review_submission.php?id=<?php echo $submission['Student_quest_submission_id']; ?>" class="btn btn-sm btn-primary">
                                            <?php echo ($submission['Status'] == 'pending') ? 'Review Now' : 'View Details'; ?>
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

<style>
    /* NOTE: These styles augment the main style.css to ensure specific table and filter components are responsive on this page. */

    /* Global classes used in the PHP above that need basic definition if missing */
    .admin-data-table { width: 100%; border-collapse: collapse; }
    .admin-data-table th, .admin-data-table td { padding: 12px 15px; text-align: left; }
    .admin-data-table thead th { background-color: #E6F3EE; color: #1D4C43; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; }
    .table-responsive { overflow-x: auto; }

    /* Mobile Responsive Table Transformation */
    @media (max-width: 768px) {
        /* Filter Navigation Stacking */
        .submission-filter-nav {
            flex-wrap: wrap;
            justify-content: center;
        }
        .submission-filter-nav a {
            flex: 1 1 auto; /* Allow buttons to wrap and take up space */
            text-align: center;
            min-width: 120px;
        }

        /* Card Transformation */
        .admin-data-table thead {
            display: none;
        }
        .admin-data-table, .admin-data-table tbody, .admin-data-table tr, .admin-data-table td {
            display: block;
            width: 100%;
        }
        .admin-data-table tr {
            margin-bottom: 15px;
            border: 1px solid #DDEEE5;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .admin-data-table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
            border-bottom: 1px dashed #f0f0f0;
        }
        .admin-data-table td:last-child {
            border-bottom: none;
        }
        .admin-data-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 15px;
            width: calc(50% - 30px);
            text-align: left;
            font-weight: 600;
            color: #1D4C43;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>