<?php
// pages/admin/edit_reward.php
// Allows an administrator to edit an existing reward in the catalogue.

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
$reward_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Defined categories
$categories = ['Voucher', 'Merchandise', 'Digital Badge', 'Experience'];

// Default form data initialized to null/empty
$form_data = [
    'reward_id' => $reward_id,
    'name' => '',
    'description' => '',
    'points_cost' => '',
    'stock' => '', 
    'image_url' => '',
    'category' => 'Voucher',
    'is_active' => '1',
];

// If ID is missing, redirect back
if (!$reward_id) {
    header('Location: manage_rewards.php?error=' . urlencode('No reward ID provided for editing.'));
    exit;
}


// =======================================================
// 2. FETCH EXISTING DATA (Initial Load or on Error)
// =======================================================
function fetchRewardData($conn, $id) {
    try {
        $sql = "SELECT * FROM rewards WHERE reward_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        error_log("Edit Reward Fetch Error: " . $e->getMessage());
        return false;
    }
}


// =======================================================
// 3. HANDLE FORM SUBMISSION (UPDATE)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Collect and sanitize POST data (ID comes from a hidden field)
    $reward_id = filter_input(INPUT_POST, 'reward_id', FILTER_VALIDATE_INT);
    $form_data['name'] = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['description'] = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['points_cost'] = filter_input(INPUT_POST, 'points_cost', FILTER_VALIDATE_INT);
    $form_data['stock'] = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $form_data['image_url'] = filter_input(INPUT_POST, 'image_url', FILTER_VALIDATE_URL) ?: null; 
    $form_data['category'] = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0; 

    
    // --- Validation ---
    if (!$reward_id) {
        $error_message = "Critical Error: Reward ID is missing during update.";
    } elseif (empty($form_data['name']) || $form_data['points_cost'] === false || $form_data['stock'] === false) {
        $error_message = "Validation Error: Please ensure Reward Name, Cost, and Stock are filled correctly.";
    } elseif (!in_array($form_data['category'], $categories)) {
         $error_message = "Invalid selection for Category.";
    } elseif ($form_data['points_cost'] <= 0) {
        $error_message = "Reward Cost must be a positive number.";
    } elseif ($form_data['stock'] < -1) {
        $error_message = "Stock cannot be less than -1 (use -1 for Unlimited Stock).";
    } else {
        // --- Database Update ---
        if (isset($conn) && $conn) {
            try {
                // SQL for update
                $sql_update = "
                    UPDATE rewards SET 
                        name = ?, 
                        description = ?, 
                        points_cost = ?, 
                        stock = ?, 
                        image_url = ?, 
                        category = ?, 
                        is_active = ? 
                    WHERE reward_id = ?
                ";
                
                $stmt = $conn->prepare($sql_update);
                
                if ($stmt === false) {
                    throw new Exception("SQL Update Prepare Failed: " . $conn->error);
                }

                $stmt->bind_param(
                    "ssiisssi", 
                    $form_data['name'], 
                    $form_data['description'], 
                    $form_data['points_cost'], 
                    $form_data['stock'],
                    $form_data['image_url'], 
                    $form_data['category'], 
                    $form_data['is_active'],
                    $reward_id // WHERE clause parameter
                );

                if ($stmt->execute()) {
                    $success_message = "Reward '{$form_data['name']}' (ID: {$reward_id}) updated successfully!";
                    // Since the update was successful, we fetch the latest data just in case of any implicit DB changes
                    $form_data = fetchRewardData($conn, $reward_id); 
                } else {
                    throw new Exception("Execution Failed: " . $stmt->error);
                }
                $stmt->close();

            } catch (Exception $e) {
                error_log("Reward Update Error: " . $e->getMessage());
                $error_message = "Failed to update reward in database. Error: " . $e->getMessage();
                // If update fails, re-fetch the original data to keep the form data consistent for the next try
                $original_data = fetchRewardData($conn, $reward_id);
                if ($original_data) {
                    $form_data = array_merge($form_data, $original_data); // Merge to restore original values, but keep POST errors
                }
            }
        }
    }
}


// =======================================================
// 4. LOAD DATA (If not a POST or if POST failed and needs fresh data)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $error_message) {
    if (empty($form_data['name']) && $reward_id) {
        $db_data = fetchRewardData($conn, $reward_id);

        if (!$db_data) {
            header('Location: manage_rewards.php?error=' . urlencode("Reward ID {$reward_id} not found or database error."));
            exit;
        }
        $form_data = $db_data;
    }
}


?>

<!-- ======================================================= -->
<!-- 5. HTML CONTENT START (Admin Layout) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Edit Reward #<?php echo htmlspecialchars($form_data['reward_id']); ?></h1>
        <p class="admin-subtitle">Update the details for the reward: **<?php echo htmlspecialchars($form_data['name']); ?>**.</p>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Reward Editing Form -->
        <form method="POST" action="edit_reward.php?id=<?php echo $form_data['reward_id']; ?>" class="reward-form">
            
            <!-- Hidden field for reward ID (critical for update query) -->
            <input type="hidden" name="reward_id" value="<?php echo htmlspecialchars($form_data['reward_id']); ?>">
            
            <div class="form-group">
                <label for="name">Reward Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name']); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">Detailed Description (Optional)</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="hint">Full details, terms and conditions for the reward.</p>
            </div>

            <div class="form-row">
                <!-- Points Cost -->
                <div class="form-group w-full md:w-1/3">
                    <label for="points_cost">Reward Cost (Points) <span class="required">*</span></label>
                    <input type="number" id="points_cost" name="points_cost" value="<?php echo htmlspecialchars($form_data['points_cost']); ?>" required min="1">
                </div>

                <!-- Stock -->
                <div class="form-group w-full md:w-1/3">
                    <label for="stock">Stock Quantity <span class="required">*</span></label>
                    <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($form_data['stock']); ?>" required min="-1">
                    <p class="hint">Use **-1** for unlimited stock.</p>
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
            </div>

            <div class="form-group">
                <label for="image_url">Image URL (Optional)</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($form_data['image_url'] ?? ''); ?>" placeholder="https://example.com/reward-photo.jpg">
                <p class="hint">Provide a direct link to an image of the reward.</p>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="inline-label">Is Active (Make reward available for redemption)</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-submit">
                    <i class="fas fa-save mr-2"></i> Save Changes
                </button>
                <a href="manage_rewards.php" class="btn-secondary-cancel">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>

        </form>
    </div> <!-- /.admin-content-card -->
</main>

<?php
// =======================================================
// 6. CUSTOM STYLING (Reusing Admin Panel Form CSS)
// =======================================================
?>
<style>
    /* General Admin Layout */
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
    .reward-form {
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
    .form-group input[type="url"],
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
        background: #2980B9; /* Blue for editing/saving */
        color: #FFFFFF;
        box-shadow: 0 4px 10px rgba(41, 128, 185, 0.3);
    }
    .btn-primary-submit:hover {
        background: #2471A3;
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
