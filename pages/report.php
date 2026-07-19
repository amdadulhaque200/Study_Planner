<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];

// Get time periods
$thisMonthStart = date('Y-m-01');
$thisMonthEnd = date('Y-m-t');
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

// Get overall stats
$totalMinutes = (int) (fetch_one('SELECT COALESCE(SUM(duration_min), 0) as total FROM study_sessions WHERE user_id = ?', 'i', [$userId])['total'] ?? 0);
$thisMonthMinutes = (int) (fetch_one('SELECT COALESCE(SUM(duration_min), 0) as total FROM study_sessions WHERE user_id = ? AND session_date BETWEEN ? AND ?', 'iss', [$userId, $thisMonthStart, $thisMonthEnd])['total'] ?? 0);
$lastMonthMinutes = (int) (fetch_one('SELECT COALESCE(SUM(duration_min), 0) as total FROM study_sessions WHERE user_id = ? AND session_date BETWEEN ? AND ?', 'iss', [$userId, $lastMonthStart, $lastMonthEnd])['total'] ?? 0);
$totalTasks = (int) (fetch_one('SELECT COUNT(*) as total FROM tasks WHERE user_id = ?', 'i', [$userId])['total'] ?? 0);
$completedTasks = (int) (fetch_one('SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = "completed"', 'i', [$userId])['total'] ?? 0);

$taskCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$monthGrowth = $lastMonthMinutes > 0 ? round((($thisMonthMinutes - $lastMonthMinutes) / $lastMonthMinutes) * 100) : ($thisMonthMinutes > 0 ? 100 : 0);

// Subject breakdown
$subjectStats = fetch_all(
    'SELECT s.name, s.color, COUNT(ss.id) as session_count, COALESCE(SUM(ss.duration_min), 0) as total_minutes 
     FROM subjects s 
     LEFT JOIN study_sessions ss ON s.id = ss.subject_id 
     WHERE s.user_id = ? 
     GROUP BY s.id 
     ORDER BY total_minutes DESC', 
    'i', 
    [$userId]
);

$pageTitle = 'Reports & Statistics';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="h3 mb-1">Study Reports</h2>
        <p class="text-muted mb-0">Analyze your study habits and progress over time.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">All-Time Hours</h6>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= round($totalMinutes / 60, 1) ?> <span class="fs-6 text-muted fw-normal">hrs</span></h3>
                <p class="text-muted small mb-0"><?= $totalMinutes ?> total minutes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">This Month</h6>
                    <div class="bg-success bg-opacity-10 text-success rounded p-2">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= round($thisMonthMinutes / 60, 1) ?> <span class="fs-6 text-muted fw-normal">hrs</span></h3>
                <p class="mb-0 small <?= $monthGrowth >= 0 ? 'text-success' : 'text-danger' ?>">
                    <i class="bi <?= $monthGrowth >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>"></i> 
                    <?= abs($monthGrowth) ?>% vs last month
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">Task Completion</h6>
                    <div class="bg-info bg-opacity-10 text-info rounded p-2">
                        <i class="bi bi-check2-square"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= $taskCompletionRate ?>%</h3>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $taskCompletionRate ?>%"></div>
                </div>
                <p class="text-muted small mt-2 mb-0"><?= $completedTasks ?> of <?= $totalTasks ?> tasks</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">Subjects Tracked</h6>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2">
                        <i class="bi bi-book"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= count($subjectStats) ?></h3>
                <p class="text-muted small mb-0">Active study areas</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold">Subject Breakdown</h5>
                <p class="text-muted small">Total time invested per subject</p>
            </div>
            <div class="card-body">
                <?php if (empty($subjectStats)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-pie-chart display-4 mb-3 d-block"></i>
                        <p>No data available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <?php foreach ($subjectStats as $stat): ?>
                                <?php 
                                $percent = $totalMinutes > 0 ? round(($stat['total_minutes'] / $totalMinutes) * 100) : 0; 
                                ?>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-end mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill" style="background-color: <?= h($stat['color']) ?>;">&nbsp;</span>
                                            <span class="fw-medium"><?= h($stat['name']) ?></span>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold d-block"><?= round($stat['total_minutes'] / 60, 1) ?> hrs</span>
                                            <span class="text-muted small"><?= $stat['session_count'] ?> sessions (<?= $percent ?>%)</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%; background-color: <?= h($stat['color']) ?>;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
