<?php
// pages/admin/edit_quest.php
// Allows an administrator to edit an existing quest.

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

// Initialize messages and variables
$success_message = null;
$error_message = null;
$quest_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quest = null; // Holds the original quest data

// Defined categories and proof types based on your schema
$categories = ['Plastic Use', 'Energy Saving', 'Sustainable Transport', 'General'];
$proof_types = ['Image', 'Text/Log', 'Both'];

// Default form data, will be overwritten by existing quest data or POST data
$form_data = [
    'quest_id' => $quest_id,
    'title' => '',
    'description' => '',
    'points_award' => '',
    'category' => 'Plastic Use',
    'proof_type' => 'Image',
    'instructions' => '',
    'is_active' => '1',
];

// Check if quest ID is provided
if (!$quest_id) {
    header('Location: manage_quests.php?error=' . urlencode('No quest ID specified for editing.'));
    exit;
}

// =======================================================
// 2. FETCH EXISTING QUEST DATA
// =======================================================
if (isset($conn) && $conn) {
    try {
        $sql_fetch = "SELECT * FROM quests WHERE quest_id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        
        if ($stmt_fetch === false) {
            throw new Exception("SQL Fetch Prepare Failed: " . $conn->error);
        }

        $stmt_fetch->bind_param("i", $quest_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $quest = $result->fetch_assoc();
        $stmt_fetch->close();

        if (!$quest) {
            // Quest not found
            header('Location: manage_quests.php?error=' . urlencode("Quest ID {$quest_id} not found."));
            exit;
        }

        // Populate form_data with fetched quest data
        $form_data = array_merge($form_data, $quest);

    } catch (Exception $e) {
        error_log("Quest Fetch Error: " . $e->getMessage());
        $error_message = "Failed to load quest data. DB Error: " . $e->getMessage();
    }
} else {
    $error_message = "Critical: Database connection object (\$conn) is unavailable.";
}


// =======================================================
// 3. HANDLE FORM SUBMISSION (UPDATE)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize POST data (overwriting fetched data for sticky form)
    $form_data['title'] = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['description'] = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['points_award'] = filter_input(INPUT_POST, 'points_award', FILTER_VALIDATE_INT);
    $form_data['category'] = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['proof_type'] = filter_input(INPUT_POST, 'proof_type', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['instructions'] = filter_input(INPUT_POST, 'instructions', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0; 
    // Ensure quest_id remains the one we are editing
    $edit_id = filter_input(INPUT_POST, 'quest_id', FILTER_VALIDATE_INT);


    // --- Validation ---
    if (empty($form_data['title']) || empty($form_data['points_award']) || $form_data['points_award'] === false || empty($form_data['instructions']) || $edit_id !== $quest_id) {
        $error_message = "Validation Error: Please fill in all required fields and ensure the Quest ID is correct.";
    } elseif (!in_array($form_data['category'], $categories) || !in_array($form_data['proof_type'], $proof_types)) {
         $error_message = "Invalid selection for Category or Proof Type.";
    } elseif ($form_data['points_award'] <= 0) {
        $error_message = "Points Award must be a positive number.";
    } else {
        // --- Database Update ---
        if (isset($conn) && $conn) {
            try {
                // SQL for update (matches your schema)
                $sql_update = "
                    UPDATE quests SET
                        title = ?, 
                        description = ?, 
                        points_award = ?, 
                        category = ?, 
                        proof_type = ?, 
                        instructions = ?, 
                        is_active = ?
                    WHERE quest_id = ?
                ";
                
                $stmt = $conn->prepare($sql_update);
                
                if ($stmt === false) {
                    throw new Exception("SQL Update Prepare Failed: " . $conn->error);
                }

                $stmt->bind_param(
                    "ssisssii", 
                    $form_data['title'], 
                    $form_data['description'], 
                    $form_data['points_award'], 
                    $form_data['category'], 
                    $form_data['proof_type'], 
                    $form_data['instructions'], 
                    $form_data['is_active'],
                    $edit_id // The ID of the row to update
                );

                if ($stmt->execute()) {
                    $success_message = "Quest '{$form_data['title']}' (ID: {$edit_id}) updated successfully!";
                    // Since we updated successfully, we should re-fetch the data to reflect any server-side defaults 
                    // or just leave the current form data as is, as it's the latest.
                } else {
                    throw new Exception("Execution Failed: " . $stmt->error);
                }
                $stmt->close();

            } catch (Exception $e) {
                error_log("Quest Update Error: " . $e->getMessage());
                $error_message = "Failed to update quest in database. Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- ======================================================= -->
<!-- 4. HTML CONTENT START (Admin Layout) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Edit Quest: #<?php echo htmlspecialchars($form_data['quest_id'] ?? 'N/A'); ?></h1>
        <p class="admin-subtitle">Modify the details of the existing environmental challenge.</p>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Quest Creation Form -->
        <form method="POST" action="edit_quest.php?id=<?php echo $quest_id; ?>" class="quest-form">
            
            <!-- Hidden field to ensure we know which ID we are editing -->
            <input type="hidden" name="quest_id" value="<?php echo htmlspecialchars($form_data['quest_id']); ?>">

            <div class="form-group">
                <label for="title">Quest Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">Short Description (Optional)</label>
                <textarea id="description" name="description" rows="2"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="hint">A brief summary visible on the main quest list.</p>
            </div>

            <div class="form-row">
                <!-- Points Award -->
                <div class="form-group w-full md:w-1/3">
                    <label for="points_award">Points Award <span class="required">*</span></label>
                    <input type="number" id="points_award" name="points_award" value="<?php echo htmlspecialchars($form_data['points_award']); ?>" required min="1">
                    <p class="hint">The reward points for completing this quest.</p>
                </div>

                <!-- Category -->
                <div class="form-group w-full md:w-1/3">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($form_data['category']) && $form_data['category'] === $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Proof Type -->
                <div class="form-group w-full md:w-1/3">
                    <label for="proof_type">Required Proof Type <span class="required">*</span></label>
                    <select id="proof_type" name="proof_type" required>
                        <?php foreach ($proof_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo (isset($form_data['proof_type']) && $form_data['proof_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo $type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint">How users must submit proof (Image, Text/Log, or Both).</p>
                </div>
            </div>

            <div class="form-group">
                <label for="instructions">Detailed Instructions <span class="required">*</span></label>
                <textarea id="instructions" name="instructions" rows="4" required><?php echo htmlspecialchars($form_data['instructions']); ?></textarea>
                <p class="hint">Clear, step-by-step instructions for the user to complete and verify the quest.</p>
            </div>
            
            <div class="form-group checkbox-group">
                <!-- is_active might be fetched as 0 or 1, so we check for truthiness -->
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="inline-label">Is Active (Make quest available to users)</label>
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
    </div> <!-- /.admin-content-card -->
</main>

<?php
// =======================================================
// 5. CUSTOM STYLING (Reusing Admin Panel Form CSS)
// =======================================================
?>
<style>
    /* General Admin Layout (from manage_quests.php) */
    .admin-page {
        padding: 30px 20px;
        background-color: #FAFAF0; /* Light gray-green background */
        min-height: 90vh;
        font-family: 'Inter', sans-serif;
    }
    .admin-content-card {
        max-width: 900px;
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
    
    /* Message Styles (Alerts) */
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: left;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
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
    .mr-2 { margin-right: 0.5rem; }

    /* Form Styles */
    .quest-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-group label {
        font-weight: 600;
        color: #1D4C43;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    .required {
        color: #E74C3C;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        padding: 10px 15px;
        border: 1px solid #DDEEE5;
        border-radius: 6px;
        font-size: 1rem;
        color: #333;
        transition: border-color 0.3s, box-shadow 0.3s;
        background-color: #FAFAFA;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #71B48D;
        outline: none;
        box-shadow: 0 0 0 2px rgba(113, 180, 141, 0.2);
        background-color: #FFFFFF;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .hint {
        font-size: 0.8rem;
        color: #777;
        margin-top: 4px;
    }

    /* Checkbox Group */
    .checkbox-group {
        flex-direction: row;
        align-items: center;
        margin-top: 10px;
    }
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-right: 10px;
        flex-shrink: 0;
        cursor: pointer;
    }
    .checkbox-group .inline-label {
        font-weight: 500;
        margin-bottom: 0;
        cursor: pointer;
    }

    /* Form Row (for 3-column layout) */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .w-full { width: 100%; }
    .md\:w-1\/3 { flex-basis: calc(33.333% - 13.333px); } /* Tailwind w-1/3 equivalent with gap */

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .btn-primary-submit, .btn-secondary-cancel {
        padding: 12px 20px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s, transform 0.1s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-primary-submit {
        background: #71B48D; /* Light Green */
        color: #1D4C43;
    }
    .btn-primary-submit:hover {
        background: #5AA080;
        transform: translateY(-1px);
    }
    
    .btn-secondary-cancel {
        background: #F4F7F6; /* Background Color */
        color: #5A7F7C;
        border: 1px solid #DDEEE5;
    }
    .btn-secondary-cancel:hover {
        background: #E8EDE9;
        color: #1D4C43;
    }

    /* Mobile adjustments */
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 15px;
        }
        .md\:w-1\/3 {
            flex-basis: 100%;
        }
        .form-actions {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<?php
require_once '../../includes/footer.php'; 
?>
