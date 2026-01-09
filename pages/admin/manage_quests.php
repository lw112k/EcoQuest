<?php
// Start output buffering to prevent header errors if white space exists
ob_start();
require_once '../../includes/header.php'; 

// 1. AUTHORIZATION CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php?error=unauthorized'); 
    exit;
}

// --- LOGIC A: UPDATE SINGLE QUEST (Handles Quest Editing) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_quest') {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');

    $q_id = filter_input(INPUT_POST, 'Quest_id', FILTER_VALIDATE_INT);
    $title = $_POST['Title'] ?? ''; 
    $points = filter_input(INPUT_POST, 'Points_award', FILTER_VALIDATE_INT);
    $cat_id = filter_input(INPUT_POST, 'CategoryID', FILTER_VALIDATE_INT);
    $desc = $_POST['Description'] ?? '';
    $active = isset($_POST['Is_active']) ? (int)$_POST['Is_active'] : 0;

    if ($q_id) {
        $stmt = $conn->prepare("UPDATE Quest SET Title = ?, Points_award = ?, CategoryID = ?, Description = ?, Is_active = ? WHERE Quest_id = ?");
        $stmt->bind_param("siisii", $title, $points, $cat_id, $desc, $active, $q_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Quest updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Quest ID.']);
    }
    exit;
}

// --- LOGIC B: SAVE/UPDATE WEEKLY CALENDAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_calendar') {
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json');
    
    $selected_ids = $_POST['Quest_id'] ?? [];
    $start_date = $_POST['Start_date'] ?? '';
    $end_date = $_POST['End_date'] ?? '';

    if (count($selected_ids) === 5 && !empty($start_date)) {
        try {
            $conn->begin_transaction();

            $stmt_check = $conn->prepare("SELECT Calendar_id FROM quest_calendar WHERE Start_date = ? ORDER BY Calendar_id ASC");
            $stmt_check->bind_param("s", $start_date);
            $stmt_check->execute();
            $existing_rows = $stmt_check->get_result()->fetch_all(MYSQLI_ASSOC);

            if (count($existing_rows) === 5) {
                $stmt_upd = $conn->prepare("UPDATE quest_calendar SET Quest_id = ?, End_date = ? WHERE Calendar_id = ?");
                foreach ($selected_ids as $index => $q_id) {
                    $calendar_primary_id = $existing_rows[$index]['Calendar_id'];
                    $stmt_upd->bind_param("isi", $q_id, $end_date, $calendar_primary_id);
                    $stmt_upd->execute();
                }
            } else {
                $stmt_del = $conn->prepare("DELETE FROM quest_calendar WHERE Start_date = ?");
                $stmt_del->bind_param("s", $start_date);
                $stmt_del->execute();

                $stmt_insert = $conn->prepare("INSERT INTO quest_calendar (Quest_id, Start_date, End_date) VALUES (?, ?, ?)");
                foreach ($selected_ids as $q_id) {
                    $q_id_int = (int)$q_id;
                    $stmt_insert->bind_param("iss", $q_id_int, $start_date, $end_date);
                    $stmt_insert->execute();
                }
            }
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Weekly quest updated successfully!']);
        } catch (Exception $e) {
            if ($conn->in_transaction) $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please select exactly 5 quests and a date.']);
    }
    exit; 
}

// --- LOGIC C: DELETE/INACTIVATE QUEST (修正后的部分) ---
$quest_id_param = filter_input(INPUT_GET, 'quest_id', FILTER_VALIDATE_INT); 
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $quest_id_param) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM quest_calendar WHERE Quest_id = ?");
    $stmt_check->bind_param("i", $quest_id_param);
    $stmt_check->execute();
    $in_calendar = $stmt_check->get_result()->fetch_assoc()['count'] > 0;

    if ($in_calendar) {
        $stmt_upd = $conn->prepare("UPDATE Quest SET Is_active = 0 WHERE Quest_id = ?");
        $stmt_upd->bind_param("i", $quest_id_param);
        if ($stmt_upd->execute()) {
            echo json_encode(['status' => 'status_updated', 'message' => 'Quest is in calendar. Set to Inactive.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    } else {
        $stmt_del = $conn->prepare("DELETE FROM Quest WHERE Quest_id = ?");
        $stmt_del->bind_param("i", $quest_id_param);
        if ($stmt_del->execute()) {
            echo json_encode(['status' => 'deleted', 'message' => 'Quest deleted successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
    exit;
}

// 2. FETCH DATA
$existing_events = [];
$res_cal = $conn->query("SELECT qc.*, q.Title, q.Points_award, cat.Category_Name FROM quest_calendar qc JOIN Quest q ON qc.Quest_id = q.Quest_id LEFT JOIN Quest_Categories cat ON q.CategoryID = cat.CategoryID");
if ($res_cal) { while($row = $res_cal->fetch_assoc()) { $existing_events[] = $row; } }

$quests = $conn->query("SELECT q.*, qc.Category_Name, u.Username FROM Quest q LEFT JOIN Quest_Categories qc ON q.CategoryID = qc.CategoryID LEFT JOIN Admin a ON q.Created_by = a.Admin_id LEFT JOIN User u ON a.User_id = u.User_id ORDER BY q.Quest_id ASC")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM Quest_Categories")->fetch_all(MYSQLI_ASSOC);
?>

<main class="page-content admin-page">
    <div class="container"> 
        <header class="dashboard-header">
            <h1 class="page-title"><i class="fas fa-tasks"></i> Quest Management</h1>
            <p class="subtitle">View, set and manage all weekly quests.</p>
        </header>

        <section class="admin-data-section">
            <div class="calendar-layout">
                <div class="calendar-container">
                    <div class="calendar-header-ui">
                        <div class="calendar-title-static">
                             <span id="display-month">December</span>, <span id="display-year">2025</span>
                        </div>
                        <div class="calendar-arrows">
                            <button class="nav-arrow-btn" onclick="changeWeek(-1)"><i class="fas fa-caret-up"></i></button>
                            <button class="nav-arrow-btn" onclick="changeWeek(1)"><i class="fas fa-caret-down"></i></button>
                        </div>
                    </div>
                    <div class="calendar-grid" id="calendar-grid"></div>
                </div>
                
                <div class="event-sidebar">
                    <div id='sidebar-header'><strong>Weekly Events</strong></div>
                    <div id="sidebar-content">Select a week to see events...</div>
                </div>
            </div>
        </section>

        <section class="admin-data-section">
            <div class="edit-panel">
                <div class="week-display-bar">
                    <button class="nav-arrow-circle" onclick="changeWeek(-1)"><i class="fas fa-caret-left"></i></button>
                    <div class="selected-range" id="selected-week-range">Select a week on the calendar</div>
                    <button class="nav-arrow-circle" onclick="changeWeek(1)"><i class="fas fa-caret-right"></i></button>
                </div>

                <div class="table-container shadow-inner table-responsive">
                    <table class="admin-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Points</th>
                                <th>Creator</th>
                                <th>Created On</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quests as $q): ?>
                            <tr class="selectable-row <?php echo ($q['Is_active'] == 0) ? 'row-inactive' : ''; ?>" 
                                id="quest-row-<?php echo $q['Quest_id']; ?>" 
                                data-id="<?php echo $q['Quest_id']; ?>" 
                                data-active="<?php echo $q['Is_active']; ?>"
                                onclick="toggleQuestSelection(this)"> 
                                <td><?php echo htmlspecialchars($q['Quest_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($q['Title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($q['Category_Name']); ?></td>
                                <td><span class="points-badge"><?php echo htmlspecialchars($q['Points_award']); ?> Pts</span></td>
                                <td><?php echo htmlspecialchars($q['Username']); ?></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($q['Created_at']))); ?></td>
                                <td class="actions-cell text-right">
                                    <div class="icon-group">
                                        <button class="action-btn-img pencil-btn" title="Edit" onclick="event.stopPropagation(); openEditModal(<?php echo htmlspecialchars(json_encode($q)); ?>)"> 
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="action-btn-img trash-btn" title="Delete" onclick="event.stopPropagation(); deleteQuest(<?php echo $q['Quest_id']; ?>, '<?php echo htmlspecialchars(addslashes($q['Title'])); ?>')" >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 15px 10px;"> 
                                    <a href="create_quest.php" class="btn-add-inline"><i class="fas fa-plus-circle"></i> Create New Quest</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="selection-status">0/5 Selected</span>
                    <div class="footer-actions">
                        <button class="btn-ghost" onclick="autoSelectQuests()">Auto select</button>
                        <button class="btn-primary-admin" id="confirm-btn" disabled onclick="confirmSelection()">Confirm</button>
                    </div>
                </div>
            </div>  
        </section>
    </div>
</main>

<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-content redesigned-modal">
        <form id="editForm">
            <input type="hidden" name="Quest_id" id="modal-id">
            <div class="modal-row header-row">
                <input type="text" name="Title" id="modal-title" class="input-flat-title-only" placeholder="Name">
                <button type="button" class="status-toggle-btn" id="modal-status-btn" onclick="toggleStatus()">Active</button>
                <input type="hidden" name="Is_active" id="modal-status-val">
            </div>
            <div class="modal-row stats-row">
                <div class="input-block">
                    <label>Point :</label>
                    <div class="counter-control">
                        <button type="button" class="btn-step" onclick="adjustPoints(-10)">-</button>
                        <input type="number" name="Points_award" id="modal-points" value="0">
                        <button type="button" class="btn-step" onclick="adjustPoints(10)">+</button>
                    </div>
                </div>
                <div class="input-block">
                    <label>Category:</label>
                    <select name="CategoryID" id="modal-category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>"><?php echo $cat['Category_Name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-row description-row">
                <textarea name="Description" id="modal-desc" class="description-box" placeholder="Quest details..."></textarea>
            </div>
            <div class="modal-footer-btns">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-modal-confirm">Confirm</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* 保持原有设计 */
    .admin-page { padding: 30px 0; background-color: #FAFAF0; min-height: 90vh; }
    .container { max-width: 1100px; margin: 0 auto; padding: 0 15px; }
    .dashboard-header { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #1D4C43; }
    .page-title { color: #1D4C43; font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .calendar-layout { display: flex; gap: 20px; margin-bottom: 30px; align-items: flex-start; }
    .calendar-container { flex: 1.5; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #ddd; overflow: hidden; }
    .event-sidebar { flex: 1; background: #fff; border: 3px solid #eee; border-radius: 15px; min-height: 350px; }
    #sidebar-header {background-color: #FAFAF0; border-bottom: 3px solid #f0f0f0; padding: 15px; font-weight: bold; color: #1D4C43; }
    #sidebar-content {padding: 15px 10px; }
    .calendar-header-ui { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background-color: #FAFAF0; }
    .calendar-title-static { font-weight: 800; color: #1D4C43; }
    .nav-arrow-btn { background: none; border: none; cursor: pointer; color: #1D4C43; }
    .calendar-grid { display: grid; padding: 15px; gap: 8px; text-align: center; grid-template-columns: repeat(7, 1fr); }
    .calendar-day { padding: 10px 5px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .calendar-day.selected-week { background: #3498db !important; color: white !important; }
    .week-display-bar { display: flex; justify-content: center; align-items: center; gap: 20px; padding: 15px; background: #fff; border-radius: 8px; margin-bottom: 20px; border: 1px solid #eee; }
    .nav-arrow-circle { background: #71B48D; color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .selected-range { font-weight: bold; color: #1D4C43; min-width: 320px; text-align: center; }
    .sidebar-quest-card { background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 8px; margin-bottom: 10px; border-left: 5px solid #1D4C43; text-align: left; }
    .sidebar-quest-card h4 {margin: 0; font-size: 0.9rem; color:#1D4C43; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sidebar-quest-card p { margin: 4px 0 0 0; font-size: 0.8rem; white-space: nowrap  ; overflow: hidden; text-overflow: ellipsis; }
    .icon-group { display: flex; gap: 12px; justify-content: flex-end; }
    .action-btn-img { background: none; border: none; cursor: pointer; font-size: 1.2rem; }
    .pencil-btn { color: #3498db; }
    .trash-btn { color: #e74c3c; }
    .btn-add-inline { display: flex; align-items: center; justify-content: center; width: 100%; padding: 15px; border: 2px dashed #71B48D; border-radius: 12px; text-decoration: none; color: #1D4C43; font-weight: bold; }
    .selectable-row.selected { background-color: #d1ecf1 !important; }
    .points-badge { color: #C0392B; font-weight: bold; }
    .table-footer { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-top: 1px solid #eee; }
    .btn-ghost { background: none; border: 1px solid #71B48D; color: #1D4C43; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .btn-primary-admin { background-color: #ccc; color: #666; padding: 10px 25px; border-radius: 6px; font-weight: 600; border: none; cursor: not-allowed; }
    .btn-primary-admin.active-green { background-color: #71B48D; color: #1D4C43; cursor: pointer; }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .redesigned-modal { background: white; width: 600px; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .input-flat-title-only { flex-grow: 1; background: transparent; border: none; padding: 0; font-size: 24px; font-weight: bold; color:#333; outline: none; }
    .modal-row{ display: flex; gap: 20px; margin-bottom: 20px; align-items: center; }
    .status-toggle-btn { border: none; padding: 8px 25px; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; color: white; background-color: #2ecc71; min-width: 100px;}
    .status-toggle-btn.inactive { background-color: #e74c3c !important; }
    .counter-control { display: flex; align-items: center; background: #E8E8E8; border-radius: 25px; padding: 5px 15px; }
    .input-block label{ display: block; font-size: 18px; margin-bottom: 8px; font-weight: bold; }
    .btn-step { background: none; border: none; font-size: 24px; cursor: pointer; color: #333; }
    #modal-points { width: 60px; text-align: center; border: none; background: none; font-size: 20px; font-weight: bold; outline: none; }
    #modal-category { background: #E8E8E8; border: none; padding: 10px 20px; border-radius: 10px; font-size: 18px; width: 220px; }
    .modal-footer-btns {display:flex; justify-content: flex-end; gap:15px; }
    .btn-modal-cancel, .btn-modal-confirm { background: #D1D1D1; border: none; padding: 10px 20px; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.2s}
    .description-box { width: 100%; height: 180px; background: #E8E8E8; border: 2px solid #3498db; border-radius: 10px; padding: 15px; resize: none; }
    .row-inactive { background-color: #f2f2f2 !important; opacity: 0.6; cursor: not-allowed !important; }
</style>

<script>
/* 保持原有 JavaScript 逻辑 */
const existingEvents = <?php echo json_encode($existing_events); ?>;
const mLong = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
let selectedQuests = [];
let weekStartStr = ""; 
let weekEndStr = "";
let viewYear = new Date().getFullYear();
let viewMonth = new Date().getMonth();

function changeWeek(delta) {
    if (!weekStartStr) {
        viewMonth += delta;
        if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        else if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        renderCalendar();
        return;
    }
    let current = new Date(weekStartStr);
    current.setDate(current.getDate() + (delta * 7));
    viewYear = current.getFullYear();
    viewMonth = current.getMonth();
    selectWeekUI(current.getFullYear(), current.getMonth(), current.getDate());
    renderCalendar();
}

function renderCalendar() {
    const grid = document.getElementById('calendar-grid');
    document.getElementById('display-month').innerText = mLong[viewMonth];
    document.getElementById('display-year').innerText = viewYear;
    grid.innerHTML = "";
    ["S", "M", "T", "W", "T", "F", "S"].forEach(d => grid.innerHTML += `<div style="font-weight:bold; color:#999; font-size:0.8rem;">${d}</div>`);
    const firstDay = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    for (let i = 0; i < firstDay; i++) grid.innerHTML += '<div></div>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        grid.innerHTML += `<div class="calendar-day" data-date="${dateStr}" onclick="selectWeekUI(${viewYear}, ${viewMonth}, ${d})">${d}</div>`;
    }
    highlightWeekInGrid();
}

function selectWeekUI(y, m, d) {
    const clickedDate = new Date(y, m, d);
    const startOfWeek = new Date(clickedDate);
    startOfWeek.setDate(clickedDate.getDate() - clickedDate.getDay());
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    weekStartStr = `${startOfWeek.getFullYear()}-${String(startOfWeek.getMonth() + 1).padStart(2, '0')}-${String(startOfWeek.getDate()).padStart(2, '0')}`;
    weekEndStr = `${endOfWeek.getFullYear()}-${String(endOfWeek.getMonth() + 1).padStart(2, '0')}-${String(endOfWeek.getDate()).padStart(2, '0')}`;

    highlightWeekInGrid();
    document.getElementById('selected-week-range').innerText = startOfWeek.toLocaleDateString() + " - " + endOfWeek.toLocaleDateString();
    
    const sidebar = document.getElementById('sidebar-content');
    const found = existingEvents.filter(e => e.Start_date === weekStartStr);
    
    resetSelectionState(); 
    if (found.length > 0) {
        found.forEach(f => {
            const row = document.getElementById(`quest-row-${f.Quest_id}`);
            if (row) { selectedQuests.push(f.Quest_id.toString()); row.classList.add('selected'); }
        });
        updateUI();
        sidebar.innerHTML = found.map(f => `<div class="sidebar-quest-card"><h4>${f.Title}</h4><p><strong>Category:</strong> ${f.Category_Name} | <strong> Points:</strong> ${f.Points_award}</p></div>`).join('');
    } else {
        sidebar.innerHTML = "<p style='color:#999; font-size:0.8rem;'>No quests scheduled.</p>";
    }
}

function highlightWeekInGrid() {
    if (!weekStartStr) return;
    document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected-week'));
    const start = new Date(weekStartStr);
    for (let i = 0; i < 7; i++) {
        let temp = new Date(start);
        temp.setDate(start.getDate() + i);
        let key = `${temp.getFullYear()}-${String(temp.getMonth() + 1).padStart(2, '0')}-${String(temp.getDate()).padStart(2, '0')}`;
        let dayEl = document.querySelector(`.calendar-day[data-date="${key}"]`);
        if (dayEl) dayEl.classList.add('selected-week');
    }
}

function toggleQuestSelection(row) {
    if (row.getAttribute('data-active') === "0" || !weekStartStr) return;
    const id = row.getAttribute('data-id');
    const idx = selectedQuests.indexOf(id);
    if (idx > -1) {
        selectedQuests.splice(idx, 1);
        row.classList.remove('selected');
    } else {
        if (selectedQuests.length >= 5) return alert("Max 5!");
        selectedQuests.push(id);
        row.classList.add('selected');
    }
    updateUI();
}

function autoSelectQuests() {
    if (!weekStartStr) return alert("Select a date!");
    resetSelectionState();
    const rows = Array.from(document.querySelectorAll('.selectable-row[data-active="1"]')).sort(() => 0.5 - Math.random());
    for(let i = 0; i < Math.min(5, rows.length); i++) {
        selectedQuests.push(rows[i].getAttribute('data-id'));
        rows[i].classList.add('selected');
    }
    updateUI();
}

function deleteQuest(id, title) {
    if (!confirm(`Delete "${title}"?`)) return;
    fetch(`manage_quests.php?action=delete&quest_id=${id}`).then(r => r.json()).then(data => { alert(data.message); window.location.reload(); });
}

function resetSelectionState() {
    selectedQuests = [];
    document.querySelectorAll('.selectable-row').forEach(r => r.classList.remove('selected'));
    updateUI();
}

function updateUI() {
    const btn = document.getElementById('confirm-btn');
    document.getElementById('selection-status').innerText = `${selectedQuests.length}/5 Selected`;
    btn.disabled = selectedQuests.length !== 5;
    selectedQuests.length === 5 ? btn.classList.add('active-green') : btn.classList.remove('active-green');
}

function confirmSelection() {
    if (selectedQuests.length !== 5 || !confirm("Update calendar?")) return;
    const formData = new FormData();
    formData.append('action', 'save_calendar');
    formData.append('Start_date', weekStartStr);
    formData.append('End_date', weekEndStr);
    selectedQuests.forEach(id => formData.append('Quest_id[]', id));
    fetch('manage_quests.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => { alert(data.message); window.location.reload(); });
}

function openEditModal(quest) {
    document.getElementById('modal-id').value = quest.Quest_id;
    document.getElementById('modal-title').value = quest.Title;
    document.getElementById('modal-points').value = quest.Points_award;
    document.getElementById('modal-desc').value = quest.Description;
    document.getElementById('modal-category').value = quest.CategoryID;
    document.getElementById('modal-status-val').value = quest.Is_active;
    updateStatusBtnUI(quest.Is_active);
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function toggleStatus() {
    const valInput = document.getElementById('modal-status-val');
    valInput.value = valInput.value == "1" ? "0" : "1";
    updateStatusBtnUI(valInput.value);
}

function updateStatusBtnUI(val) {
    const btn = document.getElementById('modal-status-btn');
    btn.innerText = val == 1 ? "Active" : "Inactive";
    val == 1 ? btn.classList.remove('inactive') : btn.classList.add('inactive');
}

function adjustPoints(amount) {
    const input = document.getElementById('modal-points');
    input.value = Math.max(0, (parseInt(input.value) || 0) + amount);
}

document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_quest');
    fetch('manage_quests.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => { alert(data.message); window.location.reload(); });
};

renderCalendar();
</script>

<?php 
require_once '../../includes/footer.php'; 
ob_end_flush();
?>