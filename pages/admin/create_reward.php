<?php
// pages/admin/create_reward.php
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
$is_logged_in = $is_logged_in ?? false;
$user_role = $user_role ?? 'guest';

if (!$is_logged_in || $user_role !== 'admin' || !isset($_SESSION['admin_id'])) {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

$categories = ['Voucher', 'Merchandise', 'Digital Badge', 'Experience', 'Other'];
$errors = [];
$form_data = [
    'Reward_name' => '',
    'Description' => '',
    'Points_cost' => '',
    'Stock' => '',
    'Category' => 'Voucher', // ERD does not show this, but old file had it. Keeping it.
    'Image_url' => '',
    'Is_active' => 1
];

// 2. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['Reward_name'] = trim(filter_input(INPUT_POST, 'Reward_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Description'] = trim(filter_input(INPUT_POST, 'Description', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Image_url'] = filter_input(INPUT_POST, 'Image_url', FILTER_VALIDATE_URL) ?: null; 
    $form_data['Is_active'] = isset($_POST['Is_active']) ? 1 : 0;
    
    // Numeric Validation
    $points_cost = filter_input(INPUT_POST, 'Points_cost', FILTER_VALIDATE_INT);
    if ($points_cost === false || $points_cost < 1) {
        $errors['Points_cost'] = "Cost must be a positive whole number.";
    } else {
        $form_data['Points_cost'] = $points_cost;
    }

    $stock = filter_input(INPUT_POST, 'Stock', FILTER_VALIDATE_INT);
    if ($stock === false || $stock < -1) {
        $errors['Stock'] = "Stock must be a whole number, or -1 for unlimited.";
    } else {
        $form_data['Stock'] = $stock;
    }

    if (empty($form_data['Reward_name'])) {
        $errors['Reward_name'] = "Reward name is required.";
    }

    // 2.2 Execute Database INSERT
    if (empty($errors) && isset($conn) && $conn) {
        try {
            $sql = "INSERT INTO Reward (Reward_name, Description, Points_cost, Stock, Image_url, Is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssiisi",
                $form_data['Reward_name'],
                $form_data['Description'],
                $form_data['Points_cost'],
                $form_data['Stock'],
                $form_data['Image_url'],
                $form_data['Is_active']
            );

            if ($stmt->execute()) {
                header('Location: manage_rewards.php?success=' . urlencode("Reward '{$form_data['Reward_name']}' created!"));
                exit;
            } else {
                $errors['db'] = "Failed to create reward: " . $stmt->error;
            }
            $stmt->close();

        } catch (Exception $e) {
            $errors['db'] = "A critical database error occurred: " . $e->getMessage();
        }
    }
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Create New Reward</h1>
        <p class="admin-subtitle">Fill in the details for a new item in your rewards catalogue.</p>

        <?php if (isset($errors['db'])): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($errors['db']); ?></div>
        <?php endif; ?>

        <form method="POST" action="create_reward.php" class="reward-form">

            <div class="form-group">
                <label for="Reward_name">Reward Name (e.g., $10 Coffee Voucher)</label>
                <input type="text" id="Reward_name" name="Reward_name" 
                       value="<?php echo htmlspecialchars($form_data['Reward_name']); ?>" required>
                <?php if (isset($errors['Reward_name'])): ?><p class="error-text"><?php echo $errors['Reward_name']; ?></p><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="Description">Detailed Description</label>
                <textarea id="Description" name="Description" rows="4"><?php echo htmlspecialchars($form_data['Description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="Points_cost">Points Cost</label>
                    <input type="number" id="Points_cost" name="Points_cost" 
                           value="<?php echo htmlspecialchars($form_data['Points_cost']); ?>" required min="1">
                    <?php if (isset($errors['Points_cost'])): ?><p class="error-text"><?php echo $errors['Points_cost']; ?></p><?php endif; ?>
                </div>
                <div class="form-group half-width">
                    <label for="Stock">Stock Available (Enter -1 for Unlimited)</label>
                    <input type="number" id="Stock" name="Stock" 
                           value="<?php echo htmlspecialchars($form_data['Stock']); ?>" required>
                    <?php if (isset($errors['Stock'])): ?><p class="error-text"><?php echo $errors['Stock']; ?></p><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="Image_url">Image URL (Optional)</label>
                <input type="url" id="Image_url" name="Image_url" value="<?php echo htmlspecialchars($form_data['Image_url']); ?>" placeholder="https://example.com/image.png">
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="Is_active" name="Is_active" 
                       value="1" <?php echo $form_data['Is_active'] ? 'checked' : ''; ?>>
                <label for="Is_active" class="inline-label">Set as Active (Available in Shop)</label>
            </div>

            <div class="button-bar">
                <a href="manage_rewards.php" class="btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Save Reward
                </button>
            </div>
        </form>
    </div>
</main>

<style>
    .admin-page { padding: 30px 20px; background-color: #f4f7f6; min-height: 90vh; }
    .admin-content-card { max-width: 800px; margin: 0 auto; background: #FFFFFF; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .admin-title { font-size: 2rem; font-weight: 700; color: #1D4C43; margin-bottom: 5px; border-bottom: 2px solid #DDEEE5; padding-bottom: 10px; }
    .admin-subtitle { font-size: 1rem; color: #5A7F7C; margin-bottom: 25px; }
    .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; font-weight: 500; }
    .error-message { background-color: #FADBD8; border: 1px solid #E74C3C; color: #C0392B; }
    .reward-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { width: 100%; }
    .form-row { display: flex; gap: 20px; }
    .half-width { flex: 1; }
    label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95rem; }
    input[type="text"], input[type="number"], input[type="url"], textarea, select { width: 100%; padding: 12px; border: 1px solid #DDEEE5; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
    input:focus, textarea:focus, select:focus { border-color: #3498DB; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); outline: none; }
    .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; margin: 0; }
    .checkbox-group .inline-label { margin-bottom: 0; font-weight: 500; cursor: pointer; }
    .error-text { color: #E74C3C; font-size: 0.85rem; margin-top: 5px; }
    .button-bar { display: flex; justify-content: flex-end; gap: 15px; padding-top: 10px; }
    .btn-primary, .btn-secondary { padding: 12px 25px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #1D4C43; color: #FFFFFF; }
    .btn-secondary { background: #E0E0E0; color: #555; }
    .mr-2 { margin-right: 0.5rem; }
    @media (max-width: 600px) { .form-row { flex-direction: column; gap: 15px; } }
</style>

<?php require_once '../../includes/footer.php'; ?>