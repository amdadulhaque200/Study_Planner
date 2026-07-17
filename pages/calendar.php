<?php
// Filename: calendar.php
// Destination: /study_planner/pages/calendar.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];

// Get year and month from URL or default to current
$year = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');

// Validate year and month
if ($month < 1 || $month > 12) { $month = (int)date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

$firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
$daysInMonth = (int)date('t', strtotime($firstDayOfMonth));
$lastDayOfMonth = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
$firstDayOfWeek = (int)date('N', strtotime($firstDayOfMonth)); // 1 (Mon) to 7 (Sun)

// Calculate prev/next month links
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Fetch sessions for the month
$sessions = fetch_all(
    'SELECT ss.session_date, SUM(ss.duration_min) as total_min, COUNT(ss.id) as count 
     FROM study_sessions ss 
     WHERE ss.user_id = ? AND ss.session_date BETWEEN ? AND ? 
     GROUP BY ss.session_date', 
    'iss', 
    [$userId, $firstDayOfMonth, $lastDayOfMonth]
);
$sessionsByDate = [];
foreach ($sessions as $s) {
    $sessionsByDate[$s['session_date']] = $s;
}

// Fetch tasks for the month
$tasks = fetch_all(
    'SELECT due_date, COUNT(id) as count 
     FROM tasks 
     WHERE user_id = ? AND due_date BETWEEN ? AND ? 
     GROUP BY due_date', 
    'iss', 
    [$userId, $firstDayOfMonth, $lastDayOfMonth]
);
$tasksByDate = [];
foreach ($tasks as $t) {
    $tasksByDate[$t['due_date']] = $t['count'];
}

$pageTitle = 'Calendar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h2 class="h3 mb-0">Study Calendar</h2>
        <p class="text-muted mb-0">Overview of your logged sessions and task deadlines.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm">
            <a href="?y=<?= $prevYear ?>&m=<?= $prevMonth ?>" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <button class="btn btn-outline-secondary disabled fw-bold text-dark px-4" style="opacity: 1;">
                <?= date('F Y', strtotime($firstDayOfMonth)) ?>
            </button>
            <a href="?y=<?= $nextYear ?>&m=<?= $nextMonth ?>" class="btn btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        </div>
        <a href="?y=<?= date('Y') ?>&m=<?= date('n') ?>" class="btn btn-outline-primary ms-2">Today</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="table-layout: fixed; min-width: 800px;">
                <thead class="table-light text-center">
                    <tr>
                        <th width="14%">Mon</th>
                        <th width="14%">Tue</th>
                        <th width="14%">Wed</th>
                        <th width="14%">Thu</th>
                        <th width="14%">Fri</th>
                        <th width="14%">Sat</th>
                        <th width="14%">Sun</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    echo '<tr>';
                    $dayCount = 1;
                    
                    // Empty cells before the 1st of the month
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        echo '<td class="bg-light bg-opacity-50"></td>';
                        $dayCount++;
                    }
                    
                    // Days of the month
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = $currentDate === date('Y-m-d');
                        
                        $dayClasses = 'position-relative p-2 align-top';
                        if ($isToday) $dayClasses .= ' bg-primary bg-opacity-10';
                        
                        echo '<td class="' . $dayClasses . '" style="height: 120px;">';
                        
                        // Date Number
                        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
                        echo '<span class="fw-bold ' . ($isToday ? 'text-primary' : '') . '">' . $day . '</span>';
                        echo '</div>';
                        
                        // Events (Sessions)
                        if (isset($sessionsByDate[$currentDate])) {
                            $min = $sessionsByDate[$currentDate]['total_min'];
                            echo '<a href="sessions.php" class="text-decoration-none d-block mb-1 p-1 rounded small bg-success bg-opacity-25 text-success-emphasis" style="font-size: 0.75rem;">';
                            echo '<i class="bi bi-clock-fill me-1"></i>' . round($min / 60, 1) . ' hrs';
                            echo '</a>';
                        }
                        
                        // Events (Tasks)
                        if (isset($tasksByDate[$currentDate])) {
                            $taskCount = $tasksByDate[$currentDate];
                            echo '<a href="tasks.php" class="text-decoration-none d-block mb-1 p-1 rounded small bg-warning bg-opacity-25 text-warning-emphasis" style="font-size: 0.75rem;">';
                            echo '<i class="bi bi-list-check me-1"></i>' . $taskCount . ' task(s)';
                            echo '</a>';
                        }
                        
                        echo '</td>';
                        
                        if ($dayCount % 7 == 0) {
                            echo '</tr>';
                            if ($day < $daysInMonth) echo '<tr>';
                        }
                        $dayCount++;
                    }
                    
                    // Empty cells after the last day
                    while ($dayCount % 7 != 1) {
                        echo '<td class="bg-light bg-opacity-50"></td>';
                        $dayCount++;
                    }
                    if ($dayCount % 7 != 1) echo '</tr>';
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
