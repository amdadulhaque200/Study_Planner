<?php
$pageTitle = 'Subjects';
require_once __DIR__ . '/../includes/header.php';

require_login();

$userId = (int) current_user()['id'];
$errors = [];

// Handle Subject Deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    
    // Ensure subject belongs to user
    $subject = fetch_one('SELECT id FROM subjects WHERE id = ? AND user_id = ?', 'ii', [$subjectId, $userId]);
    
    if ($subject) {
        // study_sessions are RESTRICT, so we can't delete if there are sessions. Wait, schema says:
        // CONSTRAINT fk_study_sessions_subject_id FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
        // We need to check if there are sessions first.
        $sessionCount = fetch_one('SELECT COUNT(*) as count FROM study_sessions WHERE subject_id = ?', 'i', [$subjectId])['count'];
        
        if ($sessionCount > 0) {
            set_flash('error', 'Cannot delete subject. It has associated study sessions.');
        } else {
            execute_statement('DELETE FROM subjects WHERE id = ?', 'i', [$subjectId]);
            set_flash('success', 'Subject deleted successfully.');
        }
    } else {
        set_flash('error', 'Subject not found.');
    }
    redirect('pages/subjects.php');
}

// Handle Subject Addition
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = $_POST['color'] ?? '#4f46e5';

    if (empty($name)) {
        $errors['name'] = 'Subject name is required.';
    } else {
        // Check if subject with same name exists for this user
        $existing = fetch_one('SELECT id FROM subjects WHERE user_id = ? AND name = ?', 'is', [$userId, $name]);
        if ($existing) {
            $errors['name'] = 'You already have a subject with this name.';
        }
    }

    if (empty($errors)) {
        $success = execute_statement(
            'INSERT INTO subjects (user_id, name, description, color) VALUES (?, ?, ?, ?)',
            'isss',
            [$userId, $name, $description, $color]
        );
        
        if ($success) {
            set_flash('success', 'Subject added successfully.');
            redirect('pages/subjects.php');
        } else {
            $errors['general'] = 'Failed to add subject.';
        }
    }
}

// Fetch all subjects
$subjects = fetch_all('SELECT * FROM subjects WHERE user_id = ? ORDER BY name ASC', 'i', [$userId]);
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h3 mb-0">Your Subjects</h2>
        <p class="text-muted mb-0">Manage all your study subjects here.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-lg me-1"></i> Add Subject
        </button>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php if (empty($subjects)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-journal-x display-1 text-muted mb-3"></i>
            <h4>No subjects yet</h4>
            <p class="text-muted">Get started by adding your first subject.</p>
            <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>
        </div>
    <?php else: ?>
        <?php foreach ($subjects as $subject): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-lift" style="border-top: 4px solid <?= h($subject['color']) ?> !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0"><?= h($subject['name']) ?></h5>
                            <form method="post" action="" class="d-inline" data-confirm="Are you sure you want to delete this subject?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
                                <button type="submit" class="btn btn-link text-danger p-0" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <p class="card-text text-muted small"><?= h($subject['description'] ?: 'No description provided.') ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge" style="background-color: <?= h($subject['color']) ?>20; color: <?= h($subject['color']) ?>;">
                                <i class="bi bi-circle-fill small me-1"></i> <?= h($subject['name']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addSubjectForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= h($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Color Code</label>
                        <input type="color" class="form-control form-control-color w-100" id="color" name="color" value="#4f46e5" title="Choose your color">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addSubjectForm" class="btn btn-primary">Save Subject</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Show modal if there are errors from submitting
if (isset($_POST['action']) && $_POST['action'] === 'add' && !empty($errors)) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('addSubjectModal')).show(); });</script>";
}
require_once __DIR__ . '/../includes/footer.php'; 
?>
