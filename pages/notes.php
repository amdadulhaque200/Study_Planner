<?php
// Filename: tasks.php
// Destination: /study_planner/pages/tasks.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];
$errors = [];

// Handle Task Deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    $taskId = (int) ($_POST['task_id'] ?? 0);

    // Ensure task belongs to user
    $task = fetch_one('SELECT id FROM tasks WHERE id = ? AND user_id = ?', 'ii', [$taskId, $userId]);

    if ($task) {
        execute_statement('DELETE FROM tasks WHERE id = ?', 'i', [$taskId]);
        set_flash('success', 'Task deleted successfully.');
    } else {
        set_flash('error', 'Task not found.');
    }
    redirect('pages/tasks.php');
}

// Handle Task Status Toggle
if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
    verify_csrf();
    $taskId = (int) ($_POST['task_id'] ?? 0);
    $newStatus = $_POST['status'] === 'completed' ? 'completed' : 'pending';

    $task = fetch_one('SELECT id FROM tasks WHERE id = ? AND user_id = ?', 'ii', [$taskId, $userId]);

    if ($task) {
        execute_statement('UPDATE tasks SET status = ? WHERE id = ?', 'si', [$newStatus, $taskId]);
        set_flash('success', 'Task status updated.');
    } else {
        set_flash('error', 'Task not found.');
    }
    redirect('pages/tasks.php');
}

// Handle Task Addition
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $subjectId = !empty($_POST['subject_id']) ? (int) $_POST['subject_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dueDate = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';

    if (empty($title)) {
        $errors['title'] = 'Task title is required.';
    }

    // Verify subject belongs to user if provided
    if ($subjectId !== null && $subjectId > 0) {
        $subject = fetch_one('SELECT id FROM subjects WHERE id = ? AND user_id = ?', 'ii', [$subjectId, $userId]);
        if (!$subject) {
            $errors['subject_id'] = 'Invalid subject selected.';
        }
    } else {
        $subjectId = null;
    }

    if (empty($errors)) {
        $sql = 'INSERT INTO tasks (user_id, subject_id, title, description, due_date, priority, status) VALUES (?, ?, ?, ?, ?, ?, "pending")';
        $params = [$userId, $subjectId, $title, $description, $dueDate, $priority];
        $types = 'iissss';

        $success = execute_statement($sql, $types, $params);

        if ($success) {
            set_flash('success', 'Task added successfully.');
            redirect('pages/tasks.php');
        } else {
            $errors['general'] = 'Failed to add task.';
        }
    }
}

// Fetch all subjects for the dropdown
$subjects = fetch_all('SELECT id, name, color FROM subjects WHERE user_id = ? ORDER BY name ASC', 'i', [$userId]);

// Fetch tasks
$tasks = fetch_all(
    'SELECT t.*, s.name as subject_name, s.color as subject_color 
     FROM tasks t 
     LEFT JOIN subjects s ON t.subject_id = s.id 
     WHERE t.user_id = ? 
     ORDER BY CASE WHEN t.status = "pending" THEN 0 ELSE 1 END, 
              CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END, 
              t.due_date ASC, 
              t.id DESC',
    'i',
    [$userId]
);

$pageTitle = 'Tasks & Goals';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h3 mb-0">Tasks & Goals</h2>
        <p class="text-muted mb-0">Manage your assignments, readings, and study goals.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-lg me-1"></i> Add Task
        </button>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php if (empty($tasks)): ?>
                    <li class="list-group-item text-center py-5 text-muted">
                        <i class="bi bi-check2-circle display-4 d-block mb-3"></i>
                        No tasks yet.<br>
                        <button type="button" class="btn btn-link mt-2" data-bs-toggle="modal" data-bs-target="#addTaskModal">Create your first task</button>
                    </li>
            <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                            <?php
                            $isCompleted = $task['status'] === 'completed';
                            $priorityColor = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger'][$task['priority']];
                            ?>
                            <li class="list-group-item py-3 <?= $isCompleted ? 'bg-light opacity-75' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                                        <form method="post" action="" class="mt-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $isCompleted ? 'pending' : 'completed' ?>">
                                            <button type="submit" class="btn btn-link p-0 text-<?= $isCompleted ? 'success' : 'secondary' ?>" title="<?= $isCompleted ? 'Mark as pending' : 'Mark as completed' ?>">
                                                <i class="bi <?= $isCompleted ? 'bi-check-circle-fill' : 'bi-circle' ?> fs-4"></i>
                                            </button>
                                        </form>
                                        <div class="w-100">
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                <h5 class="mb-0 fw-bold <?= $isCompleted ? 'text-decoration-line-through text-muted' : '' ?>"><?= h($task['title']) ?></h5>
                                                <span class="badge bg-<?= $priorityColor ?>-soft text-<?= $priorityColor ?> text-uppercase" style="font-size: 0.65rem;"><?= h($task['priority']) ?></span>
                                                <?php if ($task['subject_name']): ?>
                                                        <span class="badge rounded-pill" style="background-color: <?= h($task['subject_color']) ?>20; color: <?= h($task['subject_color']) ?>; font-size: 0.7rem;">
                                                            <i class="bi bi-circle-fill small me-1"></i> <?= h($task['subject_name']) ?>
                                                        </span>
                                                <?php endif; ?>
                                                <?php if ($task['due_date']): ?>
                                                        <?php
                                                        $isOverdue = !$isCompleted && strtotime($task['due_date']) < strtotime(date('Y-m-d'));
                                                        ?>
                                                        <span class="badge <?= $isOverdue ? 'bg-danger' : 'bg-light text-dark border' ?>" style="font-size: 0.7rem;">
                                                            <i class="bi bi-calendar-event me-1"></i> <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                                        </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($task['description']): ?>
                                                    <p class="text-muted small mb-0 mt-2 <?= $isCompleted ? 'text-decoration-line-through' : '' ?>"><?= nl2br(h($task['description'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <form method="post" action="" class="d-inline" data-confirm="Delete this task?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Delete Task"><i class="bi bi-trash fs-5"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                    <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addTaskForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= h($_POST['title'] ?? '') ?>" required>
                        <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= h($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject (Optional)</label>
                        <select class="form-select <?= isset($errors['subject_id']) ? 'is-invalid' : '' ?>" id="subject_id" name="subject_id">
                            <option value="">No specific subject...</option>
                            <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                                        <?= h($subject['name']) ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['subject_id'])): ?>
                                <div class="invalid-feedback"><?= h($errors['subject_id']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date (Optional)</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="<?= h($_POST['due_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?= (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= (!isset($_POST['priority']) || $_POST['priority'] == 'medium') ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : '' ?>>High</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addTaskForm" class="btn btn-primary">Save Task</button>
            </div>
        </div>
    </div>
</div>

<?php
// Show modal if there are errors from submitting
if (isset($_POST['action']) && $_POST['action'] === 'add' && !empty($errors)) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('addTaskModal')).show(); });</script>";
}
require_once __DIR__ . '/../includes/footer.php';
?>