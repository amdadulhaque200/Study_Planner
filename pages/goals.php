<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];
$weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
$weekEnd = (new DateTimeImmutable('sunday this week'))->format('Y-m-d');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $goalHours = trim((string) ($_POST['goal_hours'] ?? ''));

    if ($subjectId <= 0 || !fetch_one('SELECT id FROM subjects WHERE id = ? AND user_id = ? LIMIT 1', 'ii', [$subjectId, $userId])) {
        $errors[] = 'Choose a valid subject.';
    }

    if (!is_numeric($goalHours) || (float) $goalHours < 0) {
        $errors[] = 'Goal hours must be zero or greater.';
    }

    if (!$errors) {
        $saved = execute_statement(
            'INSERT INTO weekly_goals (user_id, subject_id, goal_hours, week_start) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE goal_hours = VALUES(goal_hours)',
            'iids',
            [$userId, $subjectId, (float) $goalHours, $weekStart]
        );

        if ($saved) {
            set_flash('success', 'Weekly goal saved successfully.');
            redirect('pages/goals.php');
        }

        $errors[] = 'Unable to save the goal.';
    }
}

$subjectRows = fetch_all('SELECT id, name, color FROM subjects WHERE user_id = ? ORDER BY name ASC', 'i', [$userId]);
$goalRows = fetch_all(
    'SELECT s.id AS subject_id, s.name, s.color, COALESCE(wg.goal_hours, 0) AS goal_hours,
            COALESCE(SUM(ss.duration_min), 0) AS minutes_this_week
     FROM subjects s
     LEFT JOIN weekly_goals wg ON wg.subject_id = s.id AND wg.user_id = s.user_id AND wg.week_start = ?
     LEFT JOIN study_sessions ss ON ss.subject_id = s.id AND ss.user_id = s.user_id AND ss.session_date BETWEEN ? AND ?
     WHERE s.user_id = ?
     GROUP BY s.id, s.name, s.color, wg.goal_hours
     ORDER BY s.name ASC',
    'sssi',
    [$weekStart, $weekStart, $weekEnd, $userId]
);

$pageTitle = 'Weekly Goals';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-2">Weekly Goals</h1>
            <p class="text-muted-2 mb-0">Set a target for each subject and track how much of the week you have completed.</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach ($goalRows as $row): ?>
            <?php
            $goalMinutes = (float) $row['goal_hours'] * 60;
            $actualMinutes = (int) $row['minutes_this_week'];
            $percent = $goalMinutes > 0 ? min(100, (int) round(($actualMinutes / $goalMinutes) * 100)) : 0;
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill" style="background: <?php echo h($row['color']); ?>;">&nbsp;</span>
                                <h2 class="h5 mb-0"><?php echo h($row['name']); ?></h2>
                            </div>
                            <span class="badge <?php echo $percent >= 100 ? 'badge-success-soft' : 'badge-soft'; ?>"><?php echo h((string) $percent); ?>%</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar <?php echo $percent >= 100 ? 'is-complete' : ''; ?>" style="width: <?php echo h((string) $percent); ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted-2 mb-3">
                            <span><?php echo h((string) $actualMinutes); ?> min logged</span>
                            <span><?php echo h((string) $goalMinutes); ?> min goal</span>
                        </div>
                        <form method="post" class="row g-2">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="subject_id" value="<?php echo h((string) $row['subject_id']); ?>">
                            <div class="col-8">
                                <label class="form-label">Goal Hours</label>
                                <input type="number" step="0.25" min="0" name="goal_hours" class="form-control" value="<?php echo h((string) $row['goal_hours']); ?>" required>
                            </div>
                            <div class="col-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$goalRows): ?>
            <div class="col-12">
                <div class="card"><div class="card-body text-center py-5 text-muted-2">Add subjects first to set weekly goals.</div></div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
