<?php
// pages/quest_detail.php
// REBUILT to use the single 'submissions' table.
session_start();

// --- DB Connection and Dependencies ---
include("../config/db.php");
include("../includes/header.php");

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db_error = '';
$quest = null;
$user_quest_status = 'New'; // Default status if no record exists

// 1. Check for a valid quest ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $db_error = 'Error: No valid quest ID provided.';
    goto display_page; // Jumps to the HTML part to show the error
}

$quest_id = (int)$_GET['id'];
$is_db_connected = isset($conn) && !$conn->connect_error;

if (!$is_db_connected) {
    $db_error = 'Warning: Database connection failed.';
    goto display_page;
}

// --- POST HANDLER: "START QUEST" ACTION (REBUILT) ---
// This now inserts the initial 'active' record into the new submissions table.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'start_quest') {
    // A simple INSERT. The UNIQUE key on (user_id, quest_id) will prevent duplicates.
    $sql_start = "INSERT INTO submissions (user_id, quest_id, status) VALUES (?, ?, 'active')";

    if ($stmt_start = $conn->prepare($sql_start)) {
        $stmt_start->bind_param("ii", $user_id, $quest_id);

        if ($stmt_start->execute()) {
            // Success! Redirect to the same page (GET request) to show the new status and the submit form.
            header("Location: quest_detail.php?id=$quest_id&msg=Quest started! You can now submit your proof.");
            exit();
        } else {
            // This error will trigger if the user has already started this quest (due to UNIQUE key)
            if ($conn->errno == 1062) { // 1062 is the error code for a duplicate entry
                 $db_error = 'You have already started this quest.';
            } else {
                 $db_error = 'Database error starting quest: ' . $stmt_start->error;
            }
        }
        $stmt_start->close();
    } else {
        $db_error = 'Database query preparation failed for starting quest.';
    }
}

// --- FETCH QUEST DETAILS AND USER STATUS (UPDATED) ---
try {
    // A. Fetch the main quest details from the 'quests' table
    $sql_quest = "SELECT quest_id, title, description, points_award, category, proof_type, instructions FROM quests WHERE quest_id = ?";
    if ($stmt_quest = $conn->prepare($sql_quest)) {
        $stmt_quest->bind_param("i", $quest_id);
        $stmt_quest->execute();
        $result_quest = $stmt_quest->get_result();
        if ($result_quest->num_rows > 0) {
            $quest = $result_quest->fetch_assoc();
        }
        $stmt_quest->close();
    }

    if ($quest) {
        // B. Check user's status for this quest from the NEW 'submissions' table
        $sql_status = "SELECT status FROM submissions WHERE user_id = ? AND quest_id = ?";
        if ($stmt_status = $conn->prepare($sql_status)) {
            $stmt_status->bind_param("ii", $user_id, $quest_id);
            $stmt_status->execute();
            $result_status = $stmt_status->get_result();

            if ($data = $result_status->fetch_assoc()) {
                // An entry exists, so the user has started it. Status can be 'active', 'pending', 'completed', or 'rejected'.
                $user_quest_status = $data['status'];
            }
            // If no data is found, the status remains 'New'.
            $stmt_status->close();
        }
    } else {
        $db_error = 'Error: Quest not found or is inactive.';
    }
} catch (mysqli_sql_exception $e) {
    $db_error = 'Error fetching data: ' . $e->getMessage();
}

display_page: // Goto marker for showing the page content
?>

<main class="quest-detail-page">
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="message success-message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="message error-message"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <?php if ($quest): ?>
            <div class="quest-summary-card">
                <a href="quests.php" class="back-link">&laquo; Back to All Quests</a>
                <span class="quest-category-tag"><?php echo htmlspecialchars($quest['category']); ?></span>
                <h1 class="quest-title"><?php echo htmlspecialchars($quest['title']); ?></h1>
                <div class="quest-metrics">
                    <span class="points-badge">💰 +<?php echo number_format($quest['points_award']); ?> PTS</span>
                    <span class="proof-badge">📋 Proof Type: <?php echo htmlspecialchars(ucfirst($quest['proof_type'])); ?></span>
                </div>
                <p class="quest-description"><?php echo htmlspecialchars($quest['description']); ?></p>
            </div>

            <div class="quest-content-grid">
                <div class="quest-instructions-guide">
                    <h2>Mission Instructions</h2>
                    <div class="instructions-box">
                        <?php echo nl2br(htmlspecialchars($quest['instructions'])); ?>
                    </div>
                </div>

                <div class="quest-submission-area">
                    <?php if ($user_quest_status === 'completed'): ?>
                        <div class="status-box completed-box">
                            <h2>Status: Completed! 🎉</h2>
                            <p>You have successfully completed this quest. Keep up the great work!</p>
                            <a href="quests.php" class="btn-secondary">Find New Quests</a>
                        </div>

                    <?php elseif ($user_quest_status === 'pending'): ?>
                        <div class="status-box pending-box">
                            <h2>Status: Pending Review 🧐</h2>
                            <p>Your submission is waiting for a Moderator to approve. Check back later!</p>
                            <a href="quests.php" class="btn-secondary">Back to Quests</a>
                        </div>

                    <?php elseif ($user_quest_status === 'New'): ?>
                        <div class="status-box new-box">
                            <h2>Ready to Commit?</h2>
                            <p>Press 'Start Quest' to accept this mission. You can submit your proof after starting.</p>
                            <form method="POST" action="quest_detail.php?id=<?php echo $quest_id; ?>">
                                <input type="hidden" name="action" value="start_quest">
                                <button type="submit" class="btn-primary">Start Quest! 🚀</button>
                            </form>
                        </div>

                    <?php elseif ($user_quest_status === 'active'): ?>
                        <div class="status-box active-box">
                            <h2>Submit Proof Now</h2>
                            <p>You have started this quest! Once you finish the mission, head to the submission page.</p>
                            <a href="validate.php" class="btn-primary">Submit Quest Proof</a>
                        </div>

                    <?php elseif ($user_quest_status === 'rejected'): ?>
                        <div class="status-box" style="border-color: var(--color-error);">
                            <h2 style="color: var(--color-error);">Status: Rejected 🔴</h2>
                            <p>Your previous submission was rejected. You can try submitting again.</p>
                             <a href="validate.php" class="btn-primary">Re-Submit Proof</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("../includes/footer.php"); ?>