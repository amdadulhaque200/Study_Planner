<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];
$today = date('Y-m-d');
$weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
$weekEnd = (new DateTimeImmutable('sunday this week'))->format('Y-m-d');

$todayTotal = (int) (fetch_one('SELECT COALESCE(SUM(duration_min), 0) AS total_min FROM study_sessions WHERE user_id = ? AND session_date = ?', 'is', [$userId, $today])['total_min'] ?? 0);
$weekTotal = (int) (fetch_one('SELECT COALESCE(SUM(duration_min), 0) AS total_min FROM study_sessions WHERE user_id = ? AND session_date BETWEEN ? AND ?', 'iss', [$userId, $weekStart, $weekEnd])['total_min'] ?? 0);
$totalSessions = (int) (fetch_one('SELECT COUNT(*) AS total_count FROM study_sessions WHERE user_id = ?', 'i', [$userId])['total_count'] ?? 0);
$totalSubjects = (int) (fetch_one('SELECT COUNT(*) AS total_count FROM subjects WHERE user_id = ?', 'i', [$userId])['total_count'] ?? 0);
$totalHours = round($totalSessions > 0 ? array_sum(array_map(static fn (array $row): int => (int) $row['duration_min'], fetch_all('SELECT duration_min FROM study_sessions WHERE user_id = ?', 'i', [$userId]))) / 60 : 0, 1);
$completedTasks = (int) (fetch_one("SELECT COUNT(*) AS total_count FROM tasks WHERE user_id = ? AND status = 'completed'", 'i', [$userId])['total_count'] ?? 0);
$pendingTasks = (int) (fetch_one("SELECT COUNT(*) AS total_count FROM tasks WHERE user_id = ? AND status = 'pending'", 'i', [$userId])['total_count'] ?? 0);

$streakDates = fetch_all('SELECT DISTINCT session_date FROM study_sessions WHERE user_id = ? ORDER BY session_date DESC', 'i', [$userId]);
$streak = 0;
$expectedDate = new DateTimeImmutable('today');

foreach ($streakDates as $row) {
    $sessionDate = new DateTimeImmutable($row['session_date']);

    if ($sessionDate->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
        $streak++;
        $expectedDate = $expectedDate->modify('-1 day');
        continue;
    }

    break;
}

$subjectRows = fetch_all(
    'SELECT s.id, s.name, s.color, COALESCE(SUM(ss.duration_min), 0) AS minutes_this_week, COALESCE(wg.goal_hours, 0) AS goal_hours
     FROM subjects s
     LEFT JOIN study_sessions ss ON ss.subject_id = s.id AND ss.user_id = s.user_id AND ss.session_date BETWEEN ? AND ?
     LEFT JOIN weekly_goals wg ON wg.subject_id = s.id AND wg.user_id = s.user_id AND wg.week_start = ?
     WHERE s.user_id = ?
     GROUP BY s.id, s.name, s.color, wg.goal_hours
     ORDER BY s.name ASC',
    'sssi',
    [$weekStart, $weekEnd, $weekStart, $userId]
);

$totalGoalMinutes = 0;
$progressMinutes = 0;
foreach ($subjectRows as $subjectRow) {
    $totalGoalMinutes += (float) $subjectRow['goal_hours'] * 60;
    $progressMinutes += (int) $subjectRow['minutes_this_week'];
}
$progressPercentage = $totalGoalMinutes > 0 ? min(100, (int) round(($progressMinutes / $totalGoalMinutes) * 100)) : 0;

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid py-4">
    <div class="row align-items-center g-3 mb-4">
        <div class="col-lg-8">
            <h1 class="display-title mb-2">Welcome, <?php echo h($user['username']); ?></h1>
            <p class="text-muted-2 mb-0">Track your subjects, sessions, tasks, notes, and weekly progress from one place.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a class="btn btn-primary me-2" href="<?php echo h(base_url('pages/sessions.php')); ?>">Log Session</a>
            <a class="btn btn-outline-primary" href="<?php echo h(base_url('pages/tasks.php')); ?>">Add Task</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-journal-bookmark"></i></div>
                <div class="stat-value"><?php echo h((string) $totalSubjects); ?></div>
                <div class="stat-label">Total Subjects</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                <div class="stat-value"><?php echo h((string) $totalSessions); ?></div>
                <div class="stat-label">Total Study Sessions</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
                <div class="stat-value"><?php echo h((string) $totalHours); ?></div>
                <div class="stat-label">Total Study Hours</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-fire"></i></div>
                <div class="stat-value"><?php echo h((string) $streak); ?></div>
                <div class="stat-label">Study Streak</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Today's Study Time</h2>
                        <span class="badge badge-soft"><?php echo h((string) date('D, M j')); ?></span>
                    </div>
                    <p class="stat-value mb-2"><?php echo h((string) round($todayTotal / 60, 1)); ?>h</p>
                    <p class="text-muted-2 mb-0"><?php echo h((string) $todayTotal); ?> minutes logged today.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">This Week</h2>
                        <span class="badge badge-soft">Mon - Sun</span>
                    </div>
                    <p class="stat-value mb-2"><?php echo h((string) round($weekTotal / 60, 1)); ?>h</p>
                    <p class="text-muted-2 mb-0">Study time recorded for the current week.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Tasks</h2>
                        <span class="badge badge-soft">Active</span>
                    </div>
                    <p class="stat-value mb-2"><?php echo h((string) $pendingTasks); ?></p>
                    <p class="text-muted-2 mb-0"><?php echo h((string) $completedTasks); ?> completed, <?php echo h((string) $pendingTasks); ?> pending.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="goals">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Weekly Goals Progress</h2>
                    <span class="badge badge-soft"><?php echo h((string) $progressPercentage); ?>%</span>
                </div>
                <div class="card-body">
                    <?php if ($subjectRows): ?>
                        <div class="row g-3">
                            <?php foreach ($subjectRows as $subjectRow): ?>
                                <?php
                                $goalMinutes = (float) $subjectRow['goal_hours'] * 60;
                                $actualMinutes = (int) $subjectRow['minutes_this_week'];
                                $percent = $goalMinutes > 0 ? min(100, (int) round(($actualMinutes / $goalMinutes) * 100)) : 0;
                                ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="border rounded-4 p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge rounded-pill" style="background: <?php echo h($subjectRow['color']); ?>;">&nbsp;</span>
                                                <strong><?php echo h($subjectRow['name']); ?></strong>
                                            </div>
                                            <span class="badge <?php echo $percent >= 100 ? 'badge-success-soft' : 'badge-soft'; ?>"><?php echo h((string) $percent); ?>%</span>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar <?php echo $percent >= 100 ? 'is-complete' : ''; ?>" role="progressbar" style="width: <?php echo h((string) $percent); ?>%;"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted-2">
                                            <span><?php echo h((string) $actualMinutes); ?> min</span>
                                            <span><?php echo h((string) $goalMinutes); ?> min goal</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted-2 mb-0">Add subjects and weekly goals to see progress bars here.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Quick Navigation</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a class="btn btn-outline-primary w-100 py-3" href="<?php echo h(base_url('pages/subjects.php')); ?>"><i class="bi bi-book me-2"></i>Subjects</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-outline-primary w-100 py-3" href="<?php echo h(base_url('pages/sessions.php')); ?>"><i class="bi bi-clock-history me-2"></i>Sessions</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-outline-primary w-100 py-3" href="<?php echo h(base_url('pages/tasks.php')); ?>"><i class="bi bi-check2-square me-2"></i>Tasks</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-outline-primary w-100 py-3" href="<?php echo h(base_url('pages/goals.php')); ?>"><i class="bi bi-bullseye me-2"></i>Goals</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-outline-primary w-100 py-3" href="<?php echo h(base_url('pages/report.php')); ?>"><i class="bi bi-bar-chart me-2"></i>Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Recent Focus</h2></div>
                <div class="card-body">
                    <?php $recentSessions = fetch_all('SELECT ss.session_date, ss.duration_min, s.name FROM study_sessions ss INNER JOIN subjects s ON s.id = ss.subject_id WHERE ss.user_id = ? ORDER BY ss.session_date DESC, ss.id DESC LIMIT 5', 'i', [$userId]); ?>
                    <?php if ($recentSessions): ?>
                        <ul class="timeline-list">
                            <?php foreach ($recentSessions as $session): ?>
                                <li>
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <strong><?php echo h($session['name']); ?></strong>
                                        <span class="badge badge-soft"><?php echo h((string) $session['duration_min']); ?> min</span>
                                    </div>
                                    <div class="text-muted-2 small"><?php echo h($session['session_date']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted-2 mb-0">No sessions yet. Start by logging your first study block.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
