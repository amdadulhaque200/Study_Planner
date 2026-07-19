<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$userId = (int) $user['id'];
$errors = [];

// Handle Note Deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();
    $noteId = (int)($_POST['note_id'] ?? 0);
    
    // Ensure note belongs to user
    $note = fetch_one('SELECT id FROM notes WHERE id = ? AND user_id = ?', 'ii', [$noteId, $userId]);
    
    if ($note) {
        execute_statement('DELETE FROM notes WHERE id = ?', 'i', [$noteId]);
        set_flash('success', 'Note deleted successfully.');
    } else {
        set_flash('error', 'Note not found.');
    }
    redirect('pages/notes.php');
}

// Handle Note Addition
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();
    
    $subjectId = !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title)) {
        $errors['title'] = 'Note title is required.';
    }
    if (empty($content)) {
        $errors['content'] = 'Note content is required.';
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
        $sql = 'INSERT INTO notes (user_id, subject_id, title, content) VALUES (?, ?, ?, ?)';
        $params = [$userId, $subjectId, $title, $content];
        $types = 'iiss';
        
        $success = execute_statement($sql, $types, $params);
        
        if ($success) {
            set_flash('success', 'Note added successfully.');
            redirect('pages/notes.php');
        } else {
            $errors['general'] = 'Failed to add note.';
        }
    }
}

// Fetch all subjects for the dropdown
$subjects = fetch_all('SELECT id, name, color FROM subjects WHERE user_id = ? ORDER BY name ASC', 'i', [$userId]);

// Fetch notes
$notes = fetch_all(
    'SELECT n.*, s.name as subject_name, s.color as subject_color 
     FROM notes n 
     LEFT JOIN subjects s ON n.subject_id = s.id 
     WHERE n.user_id = ? 
     ORDER BY n.updated_at DESC, n.id DESC', 
    'i', 
    [$userId]
);

$pageTitle = 'Notes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h3 mb-0">My Notes</h2>
        <p class="text-muted mb-0">Write down important study materials and summaries.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
            <i class="bi bi-plus-lg me-1"></i> New Note
        </button>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php if (empty($notes)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-journal-text display-1 text-muted mb-3"></i>
            <h4>No notes yet</h4>
            <p class="text-muted">Start writing down your study notes.</p>
            <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#addNoteModal">Create your first note</button>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0 text-truncate" title="<?= h($note['title']) ?>"><?= h($note['title']) ?></h5>
                            <form method="post" action="" class="d-inline" data-confirm="Are you sure you want to delete this note?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                <button type="submit" class="btn btn-link text-danger p-0 ms-2" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        
                        <?php if ($note['subject_name']): ?>
                            <div class="mb-3">
                                <span class="badge rounded-pill" style="background-color: <?= h($note['subject_color']) ?>20; color: <?= h($note['subject_color']) ?>; font-size: 0.7rem;">
                                    <i class="bi bi-circle-fill small me-1"></i> <?= h($note['subject_name']) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">Uncategorized</span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="card-text text-muted small flex-grow-1" style="white-space: pre-wrap; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;"><?= h($note['content']) ?></p>
                        
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-clock me-1"></i> Updated <?= date('M j, Y g:i A', strtotime($note['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addNoteForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label">Note Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= h($_POST['title'] ?? '') ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?= h($errors['title']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="subject_id" class="form-label">Subject (Optional)</label>
                            <select class="form-select <?= isset($errors['subject_id']) ? 'is-invalid' : '' ?>" id="subject_id" name="subject_id">
                                <option value="">None...</option>
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
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Note Content <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>" id="content" name="content" rows="8" required placeholder="Start typing your notes here..."><?= h($_POST['content'] ?? '') ?></textarea>
                        <?php if (isset($errors['content'])): ?>
                            <div class="invalid-feedback"><?= h($errors['content']) ?></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addNoteForm" class="btn btn-primary">Save Note</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Show modal if there are errors from submitting
if (isset($_POST['action']) && $_POST['action'] === 'add' && !empty($errors)) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('addNoteModal')).show(); });</script>";
}
require_once __DIR__ . '/../includes/footer.php'; 
?>
