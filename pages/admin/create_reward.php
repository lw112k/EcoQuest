<?php
// pages/admin/create_reward.php
// Handles the form submission to create a new reward in the catalogue.

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

// Allowed categories for the dropdown
$categories = ['Voucher', 'Merchandise', 'Digital Badge', 'Experience', 'Other'];

// Initialize variables for form feedback
$errors = [];
$form_data = [
    'name' => '',
    'description' => '',
    'points_cost' => '',
    'stock' => '',
    'category' => '',
    'is_active' => 1 // Default to active
];

// =======================================================
// 2. HANDLE FORM SUBMISSION (POST Request)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2.1 Sanitize and validate inputs
    $form_data['name'] = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['description'] = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['category'] = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Convert checkbox 'on' or missing value to 1 or 0
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    
    // Numeric Validation for points_cost
    $points_cost = filter_input(INPUT_POST, 'points_cost', FILTER_VALIDATE_INT);
    if ($points_cost === false || $points_cost < 1) {
        $errors['points_cost'] = "Cost must be a positive whole number (points).";
    } else {
        $form_data['points_cost'] = $points_cost;
    }

    // Numeric Validation for stock (allowing -1 for unlimited)
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    if ($stock === false || $stock < -1) {
        $errors['stock'] = "Stock must be a whole number, or -1 for unlimited.";
    } else {
        $form_data['stock'] = $stock;
    }

    // Basic required field checks
    if (empty($form_data['name'])) {
        $errors['name'] = "Reward name is required.";
    }
    if (!in_array($form_data['category'], $categories)) {
        $errors['category'] = "Invalid category selected.";
    }

    // 2.2 Execute Database INSERT
    if (empty($errors) && isset($conn) && $conn) {
        try {
            $sql = "INSERT INTO rewards (name, description, points_cost, stock, category, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }

            // Bind parameters: (s=string, i=integer)
            $stmt->bind_param(
                "ssiisi",
                $form_data['name'],
                $form_data['description'],
                $form_data['points_cost'],
                $form_data['stock'],
                $form_data['category'],
                $form_data['is_active']
            );

            if ($stmt->execute()) {
                // Success: Redirect to the manage page with a success message
                header('Location: manage_rewards.php?success=' . urlencode("Reward '{$form_data['name']}' created successfully!"));
                exit;
            } else {
                $errors['db'] = "Failed to create reward: " . $stmt->error;
            }
            $stmt->close();

        } catch (Exception $e) {
            error_log("Reward Creation Error: " . $e->getMessage());
            $errors['db'] = "A critical database error occurred.";
        }
    }
}

?>

<!-- ======================================================= -->
<!-- 3. HTML CONTENT START (Admin Form Layout) -->
<!-- ======================================================= -->
<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Create New Reward</h1>
        <p class="admin-subtitle">
            Fill in the details for a new item in your rewards catalogue.
        </p>

        <!-- Display general database error -->
        <?php if (isset($errors['db'])): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($errors['db']); ?></div>
        <?php endif; ?>

        <form method="POST" action="create_reward.php" class="reward-form">

            <!-- Reward Name -->
            <div class="form-group">
                <label for="name">Reward Name (e.g., $10 Coffee Voucher)</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($form_data['name']); ?>" 
                       placeholder="Enter reward name" required>
                <?php if (isset($errors['name'])): ?><p class="error-text"><?php echo $errors['name']; ?></p><?php endif; ?>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Detailed Description</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="Describe the reward, including any terms and conditions (e.g., Valid for 3 months)."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
            </div>

            <div class="form-row">
                <!-- Points Cost -->
                <div class="form-group half-width">
                    <label for="points_cost">Points Cost</label>
                    <input type="number" id="points_cost" name="points_cost" 
                           value="<?php echo htmlspecialchars($form_data['points_cost']); ?>" 
                           placeholder="e.g., 500" required min="1">
                    <?php if (isset($errors['points_cost'])): ?><p class="error-text"><?php echo $errors['points_cost']; ?></p><?php endif; ?>
                </div>

                <!-- Stock -->
                <div class="form-group half-width">
                    <label for="stock">Stock Available (Enter -1 for Unlimited)</label>
                    <input type="number" id="stock" name="stock" 
                           value="<?php echo htmlspecialchars($form_data['stock']); ?>" 
                           placeholder="e.g., 100 or -1" required>
                    <?php if (isset($errors['stock'])): ?><p class="error-text"><?php echo $errors['stock']; ?></p><?php endif; ?>
                </div>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">-- Select a Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo ($form_data['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category'])): ?><p class="error-text"><?php echo $errors['category']; ?></p><?php endif; ?>
            </div>
            
            <!-- Active Status -->
            <div class="form-group checkbox-group">
                <input type="checkbox" id="is_active" name="is_active" 
                       value="1" 
                       <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="inline-label">Set as Active (Available in Shop)</label>
            </div>

            <!-- Action Buttons -->
            <div class="button-bar">
                <a href="manage_rewards.php" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save Reward
                </button>
            </div>
        </form>
    </div> <!-- /.admin-content-card -->
</main>

<?php
// =======================================================
// 4. CUSTOM STYLING
// =======================================================
?>
<style>
    /* General Admin Layout */
    .admin-page {
        padding: 30px 20px;
        background-color: #f4f7f6;
        min-height: 90vh;
        font-family: 'Inter', sans-serif;
    }
    .admin-content-card {
        max-width: 800px; /* Wider card for form */
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
    
    /* Form Styling */
    .reward-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .form-group {
        width: 100%;
    }
    .form-row {
        display: flex;
        gap: 20px;
    }
    .half-width {
        flex: 1;
    }
    
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }
    
    input[type="text"], 
    input[type="number"],
    textarea,
    select {
        width: 100%;
        padding: 12px;
        border: 1px solid #DDEEE5;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    input:focus, textarea:focus, select:focus {
        border-color: #3498DB;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        outline: none;
    }

    textarea {
        resize: vertical;
    }

    /* Checkbox Styling */
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin: 0;
        accent-color: #1D4C43; /* Dark Green accent color */
    }
    .checkbox-group .inline-label {
        margin-bottom: 0;
        font-weight: 500;
        cursor: pointer;
    }

    /* Error Text */
    .error-text {
        color: #E74C3C;
        font-size: 0.85rem;
        margin-top: 5px;
        font-weight: 500;
    }

    /* Buttons */
    .button-bar {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding-top: 10px;
    }
    .btn-primary, .btn-secondary {
        padding: 12px 25px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s, transform 0.1s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 1rem;
    }
    .btn-primary {
        background: #1D4C43; /* Primary Green */
        color: #FFFFFF;
        box-shadow: 0 4px 10px rgba(29, 76, 67, 0.3);
    }
    .btn-primary:hover {
        background: #12362E;
        transform: translateY(-1px);
    }
    .btn-secondary {
        background: #E0E0E0;
        color: #555;
    }
    .btn-secondary:hover {
        background: #CFCFCF;
    }

    .mr-2 { margin-right: 0.5rem; }

    /* Mobile adjustments */
    @media (max-width: 600px) {
        .admin-content-card {
            padding: 20px;
        }
        .form-row {
            flex-direction: column;
            gap: 15px;
        }
        .button-bar {
            flex-direction: column;
            gap: 10px;
        }
        .btn-primary, .btn-secondary {
            justify-content: center;
        }
    }
</style>

<?php
require_once '../../includes/footer.php'; 
?>
