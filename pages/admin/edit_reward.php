<?php
// pages/admin/edit_reward.php
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
$reward_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$form_data = null;

if (!$reward_id) {
    header('Location: manage_rewards.php?error=' . urlencode('No reward ID provided.'));
    exit;
}

// 2. HANDLE FORM SUBMISSION (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['Reward_id'] = filter_input(INPUT_POST, 'Reward_id', FILTER_VALIDATE_INT);
    $form_data['Reward_name'] = trim(filter_input(INPUT_POST, 'Reward_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Description'] = trim(filter_input(INPUT_POST, 'Description', FILTER_SANITIZE_SPECIAL_CHARS));
    $form_data['Image_url'] = filter_input(INPUT_POST, 'Image_url', FILTER_VALIDATE_URL) ?: null; 
    $form_data['Is_active'] = isset($_POST['Is_active']) ? 1 : 0;
    
    $points_cost = filter_input(INPUT_POST, 'Points_cost', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'Stock', FILTER_VALIDATE_INT);

    if (empty($form_data['Reward_name']) || $points_cost === false || $stock === false) {
        $error_message = "Validation Error: Name, Cost, and Stock must be filled correctly.";
    } elseif ($points_cost <= 0) {
        $error_message = "Reward Cost must be positive.";
    } elseif ($stock < -1) {
        $error_message = "Stock must be -1 (unlimited) or more.";
    } else {
        if (isset($conn) && $conn) {
            try {
                $sql_update = "
                    UPDATE Reward SET 
                        Reward_name = ?, 
                        Description = ?, 
                        Points_cost = ?, 
                        Stock = ?, 
                        Image_url = ?, 
                        Is_active = ? 
                    WHERE Reward_id = ?
                ";
                
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param(
                    "ssiisii", 
                    $form_data['Reward_name'], 
                    $form_data['Description'], 
                    $points_cost, 
                    $stock,
                    $form_data['Image_url'], 
                    $form_data['Is_active'],
                    $form_data['Reward_id']
                );

                if ($stmt->execute()) {
                    $success_message = "Reward '{$form_data['Reward_name']}' updated successfully!";
                } else {
                    throw new Exception("Execution Failed: " . $stmt->error);
                }
                $stmt->close();

            } catch (Exception $e) {
                $error_message = "Failed to update reward. Error: " . $e->getMessage();
            }
        }
    }
}

// 4. LOAD DATA (On initial load or after update)
if (isset($conn)) {
    try {
        $sql = "SELECT * FROM Reward WHERE Reward_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reward_id);
        $stmt->execute();
        $form_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$form_data) {
             header('Location: manage_rewards.php?error=' . urlencode("Reward ID {$reward_id} not found."));
             exit;
        }
    } catch (Exception $e) {
         $error_message = "Failed to load reward data: " . $e->getMessage();
    }
}
?>

<main class="admin-page">
    <div class="admin-content-card">
        <h1 class="admin-title">Edit Reward #<?php echo htmlspecialchars($form_data['Reward_id']); ?></h1>
        <p class="admin-subtitle">Update the details for: **<?php echo htmlspecialchars($form_data['Reward_name']); ?>**.</p>

        <?php if ($success_message): ?>
            <div class="message success-message"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error-message"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($form_data): ?>
        <form method="POST" action="edit_reward.php?id=<?php echo $form_data['Reward_id']; ?>" class="reward-form">
            
            <input type="hidden" name="Reward_id" value="<?php echo htmlspecialchars($form_data['Reward_id']); ?>">
            
            <div class="form-group">
                <label for="Reward_name">Reward Name <span class="required">*</span></label>
                <input type="text" id="Reward_name" name="Reward_name" value="<?php echo htmlspecialchars($form_data['Reward_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="Description">Detailed Description (Optional)</label>
                <textarea id="Description" name="Description" rows="3"><?php echo htmlspecialchars($form_data['Description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label for="Points_cost">Reward Cost (Points) <span class="required">*</span></label>
                    <input type="number" id="Points_cost" name="Points_cost" value="<?php echo htmlspecialchars($form_data['Points_cost']); ?>" required min="1">
                </div>
                <div class="form-group half-width">
                    <label for="Stock">Stock Quantity <span class="required">*</span></label>
                    <input type="number" id="Stock" name="Stock" value="<?php echo htmlspecialchars($form_data['Stock']); ?>" required min="-1">
                    <p class="hint">Use **-1** for unlimited stock.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="Image_url">Image URL (Optional)</label>
                <input type="url" id="Image_url" name="Image_url" value="<?php echo htmlspecialchars($form_data['Image_url'] ?? ''); ?>" placeholder="https://example.com/reward-photo.jpg">
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="Is_active" name="Is_active" value="1" <?php echo $form_data['Is_active'] ? 'checked' : ''; ?>>
                <label for="Is_active" class="inline-label">Is Active (Available in Shop)</label>
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
        <?php endif; ?>
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
    .reward-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-weight: 600; color: #1D4C43; margin-bottom: 8px; font-size: 0.95rem; }
    .required { color: #E74C3C; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="url"], .form-group select, .form-group textarea { padding: 10px 15px; border: 1px solid #DDEEE5; border-radius: 6px; font-size: 1rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #71B48D; outline: none; box-shadow: 0 0 0 2px rgba(113, 180, 141, 0.2); }
    .hint { font-size: 0.8rem; color: #777; margin-top: 4px; }
    .checkbox-group { flex-direction: row; align-items: center; margin-top: 10px; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; margin-right: 10px; }
    .checkbox-group .inline-label { margin-bottom: 0; cursor: pointer; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
    .half-width { flex: 1; }
    .form-actions { display: flex; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
    .btn-primary-submit, .btn-secondary-cancel { padding: 12px 20px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
    .btn-primary-submit { background: #2980B9; color: #FFFFFF; }
    .btn-secondary-cancel { background: #F4F7F6; color: #5A7F7C; border: 1px solid #DDEEE5; }
    @media (max-width: 600px) { .form-row { flex-direction: column; gap: 15px; } }
</style>

<?php require_once '../../includes/footer.php'; ?>