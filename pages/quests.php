<?php
// pages/quests.php
session_start();

include("../includes/header.php");
include("../includes/navigation.php");

// --- DATABASE SIMULATION: Placeholder Quest Data ---
$quests = [
        [
                'id' => 1,
                'title' => 'Say No To Plastic Bottle 🙅',
                'points' => 150,
                'theme' => 'Plastic Reduction',
                'difficulty' => 'Easy',
                'status' => 'Active',
                'desc' => 'Walao eh, use a reusable bottle for every drink for one week. Snap a pic daily!'
        ],
        [
                'id' => 2,
                'title' => 'The Carpool Crew 🚗',
                'points' => 300,
                'theme' => 'Sustainable Transport',
                'difficulty' => 'Medium',
                'status' => 'Completed',
                'desc' => 'Carpool with 3+ friends to campus for 5 days straight. Less emissions, more points, confirm!'
        ],
        [
                'id' => 3,
                'title' => 'Power Down King/Queen 💡',
                'points' => 200,
                'theme' => 'Energy Saving',
                'difficulty' => 'Medium',
                'status' => 'Pending Review',
                'desc' => 'Track and log your room/house electricity usage reduction by 10% this month.'
        ],
        [
                'id' => 4,
                'title' => 'Recycle Champion ♻️',
                'points' => 100,
                'theme' => 'Waste Management',
                'difficulty' => 'Easy',
                'status' => 'Active',
                'desc' => 'Find and correctly sort 5 items from different recycling categories. Get it right, can?'
        ]
];
?>

    <main class="quests-page">
        <div class="container">
            <h1 class="page-title">Ready for the Next Challenge? 🚀</h1>
            <p class="page-subtitle">Pick a quest, submit your proof, and start earning points for real impact. Cepat, don't miss out!</p>

            <div class="quest-controls">
                <div class="search-bar form-group">
                    <input type="text" placeholder="Search by title or theme...">
                </div>
                <div class="filter-dropdown form-group">
                    <select id="quest-filter">
                        <option value="all">Filter by Status: All</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending Review</option>
                    </select>
                </div>
            </div>

            <div class="quest-grid">
                <?php foreach ($quests as $quest): ?>
                    <?php
                    // Simple formatting for the status class name (e.g., 'Pending Review' becomes 'pending-review')
                    $status_class = strtolower(str_replace(' ', '-', $quest['status']));
                    ?>

                    <div class="quest-card status-<?php echo $status_class; ?>">

                        <div class="quest-header">
                            <span class="quest-theme"><?php echo $quest['theme']; ?></span>
                            <span class="quest-points">+<?php echo $quest['points']; ?> PTS</span>
                        </div>

                        <h3 class="quest-title"><?php echo $quest['title']; ?></h3>
                        <p class="quest-desc"><?php echo $quest['desc']; ?></p>

                        <div class="quest-footer">
                            <span class="quest-difficulty"><?php echo $quest['difficulty']; ?></span>

                            <?php if ($quest['status'] == 'Active'): ?>
                                <span class="quest-status"><?php echo $quest['status']; ?></span>
                                <a href="quest_detail.php?id=<?php echo $quest['id']; ?>" class="btn-submit">Start Quest</a>
                            <?php elseif ($quest['status'] == 'Pending Review'): ?>
                                <span class="quest-status"><?php echo $quest['status']; ?></span>
                                <span class="btn-pending">Waiting...</span>
                            <?php else: /* Completed */ ?>
                                <span class="quest-status"><?php echo $quest['status']; ?></span>
                                <span class="btn-completed">Done! 🎉</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($quests)): ?>
                    <p class="no-quests">Aiyo, looks like no active quests right now! Check back soon or contact your admin.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>

<?php include("../includes/footer.php"); ?>