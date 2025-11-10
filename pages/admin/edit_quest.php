<?php
// pages/admin/edit_quest.php
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$success_message = null;
$error_message = null;
$quest_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quest = null; 

// --- Load categories from database ---
$categories_from_db = [];
if (isset($conn) && $conn) {
    try {
        $result = $conn->query("SELECT CategoryID, Category_Name FROM Quest_Categories ORDER BY Category_Name ASC");
        while ($row = $result->fetch_assoc()) {
            $categories_from_db[] = $row;
        }
    } catch (Exception $e) {
        $error_message = "Failed to load categories: " . $e->getMessage();
    }
}
// $proof_types = ['Image', 'Text/Log', 'Both']; // <-- REMOVED

$form_data = [
    'Quest_id' => $quest_id,
    'Title' => '',
    'Description' => '',
    'Points_award' => '',
    'CategoryID' => '', // Use CategoryID
    'Proof_type' => 'Image', // <-- LOCKED
    'Instructions' => '',
    'Is_active' => '1',
];

if (!$quest_id) {
    header('Location: manage_quests.php?error=' . urlencode('No quest ID specified.'));
    exit;
}

// 2. FETCH EXISTING QUEST DATA
if (isset($conn) && $conn) {
    try {
        $sql_fetch = "SELECT Quest_id, Title, Description, Points_award, CategoryID, Proof_type, Instructions, Is_active FROM Quest WHERE Quest_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param("i", $quest_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $quest = $result->fetch_assoc();
        $stmt_fetch->close();

        if (!$quest) {
            header('Location: manage_quests.php?error=' . urlencode("Quest ID {$quest_id} not found."));
            exit;
        }
        // Populate form_data with fetched quest data
        $form_data = $quest;

    } catch (Exception $e) {
        $error_message = "Failed to load quest data. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Database connection unavailable.";
}


// 3. HANDLE FORM SUBMISSION (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['Title'] = filter_input(INPUT_POST, 'Title', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['Description'] = filter_input(INPUT_POST, 'Description', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['Points_award'] = filter_input(INPUT_POST, 'Points_award', FILTER_VALIDATE_INT);
    $form_data['CategoryID'] = filter_input(INPUT_POST, 'CategoryID', FILTER_VALIDATE_INT);
    $form_data['Proof_type'] = 'Image'; // <-- LOCKED VALUE
    $form_data['Instructions'] = filter_input(INPUT_POST, 'Instructions', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['Is_active'] = isset($_POST['Is_active']) ? 1 : 0; 
    $edit_id = filter_input(INPUT_POST, 'Quest_id', FILTER_VALIDATE_INT);

    // --- Validation ---
    if (empty($form_data['Title']) || $form_data['Points_award'] === false || empty($form_data['Instructions']) || $edit_id !== $quest_id || empty($form_data['CategoryID'])) {
        $error_message = "Validation Error: Please fill in all required fields.";
    } elseif ($form_data['Points_award'] <= 0) {
        $error_message = "Points Award must be a positive number.";
    } else {
        // --- Database Update ---
        if (isset($conn) && $conn) {
            try {
                $sql_update = "
                    UPDATE Quest SET
                        Title = ?, 
                        Description = ?, 
                        Points_award = ?, 
                        CategoryID = ?, 
                        Proof_type = ?, 
                        Instructions = ?, 
                        Is_active = ?
                    WHERE Quest_id = ?
                ";
                
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param(
                    "ssiisssi", 
                    $form_data['Title'], 
                    $form_data['Description'], 
                    $form_data['Points_award'], 
                    $form_data['CategoryID'], 
                    $form_data['Proof_type'], // This will always be 'Image'
                    $form_data['Instructions'], 
                    $form_data['Is_active'],
                    $edit_id
                );

                if ($stmt->execute()) {
                    $success_message = "Quest '{$form_data['Title']}' (ID: {$edit_id}) updated successfully!";
                } else {
                    throw new Exception("Execution Failed: " . $stmt->error);
                }
                $stmt->close();

            } catch (Exception $e) {
                $error_message = "Failed to update quest. Error: " . $e->getMessage();
            }
        }
    }
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Edit Quest: #<?php echo htmlspecialchars($form_data['Quest_id'] ?? 'N/A'); ?></h1>
        <p class="admin-subtitle">Modify the details of the existing environmental challenge.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_quest.php?id=<?php echo $quest_id; ?>" class="quest-form">
            
            <input type="hidden" name="Quest_id" value="<?php echo htmlspecialchars($form_data['Quest_id']); ?>">

            <div class="form-group">
                <label for="Title">Quest Title <span class="required">*</span></label>
                <input type="text" id="Title" name="Title" value="<?php echo htmlspecialchars($form_data['Title']); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="Description">Short Description (Optional)</label>
                <textarea id="Description" name="Description" rows="2"><?php echo htmlspecialchars($form_data['Description']); ?></textarea>
            </div>

            <input type="hidden" name="Proof_type" value="Image">

            <div class="form-row">
                <div class="form-group w-full md:w-1/2">
                    <label for="Points_award">Points Award <span class="required">*</span></label>
                    <input type="number" id="Points_award" name="Points_award" value="<?php echo htmlspecialchars($form_data['Points_award']); ?>" required min="1">
                </div>

                <div class="form-group w-full md:w-1/2">
                    <label for="CategoryID">Category <span class="required">*</span></label>
                    <select id="CategoryID" name="CategoryID" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($categories_from_db as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>" <?php echo (isset($form_data['CategoryID']) && $form_data['CategoryID'] == $cat['CategoryID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['Category_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                </div>

            <div class="form-group">
                <label for="Instructions">Detailed Instructions <span class="required">*</span></label>
                <textarea id="Instructions" name="Instructions" rows="4" required><?php echo htmlspecialchars($form_data['Instructions']); ?></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="Is_active" name="Is_active" value="1" <?php echo $form_data['Is_active'] ? 'checked' : ''; ?>>
                <label for="Is_active" class="inline-label">Is Active (Make quest available to users)</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-submit">
                    <i class="fas fa-save mr-2"></i> Update Quest
                </button>
                <a href="manage_quests.php" class="btn-secondary-cancel">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>

        </form>
    </div>
</main>

<style>
    .admin-page { padding: 30px 20px; background-color: #FAFAF0; min-height: 90vh; }
    .admin-content-card { max-width: 900px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; font-weight: 500; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .success-message { background-color: #DDEEE5; border: 1px solid #71B48D; color: #1D4C43; }
    .mr-2 { margin-right: 0.5rem; }
    .quest-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-weight: 600; color: #1D4C43; margin-bottom: 8px; font-size: 0.95rem; }
    .required { color: #E74C3C; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { padding: 10px 15px; border: 1px solid #DDEEE5; border-radius: 6px; font-size: 1rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #71B48D; outline: none; box-shadow: 0 0 0 2px rgba(113, 180, 141, 0.2); }
    .checkbox-group { flex-direction: row; align-items: center; margin-top: 10px; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; margin-right: 10px; }
    .checkbox-group .inline-label { margin-bottom: 0; cursor: pointer; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
    .w-full { width: 100%; }
    .md\:w-1\/3 { flex-basis: calc(33.333% - 13.333px); }
    /* === STYLE ADDED === */
    .md\:w-1\/2 { flex-basis: calc(50% - 10px); }
    .form-actions { display: flex; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
    .btn-primary-submit, .btn-secondary-cancel { padding: 12px 20px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: background 0.3s; text-decoration: none; }
    .btn-primary-submit { background: #71B48D; color: #1D4C43; }
    .btn-secondary-cancel { background: #F4F7F6; color: #5A7F7C; border: 1px solid #DDEEE5; }
    @media (max-width: 768px) {
        .form-row { flex-direction: column; gap: 15px; }
        .md\:w-1\/3 { flex-basis: 100%; }
        /* === STYLE ADDED === */
        .md\:w-1\/2 { flex-basis: 100%; }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>