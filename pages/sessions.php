

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="h3 mb-0">Study Sessions</h2>
        <p class="text-muted mb-0">Log and review your study time.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSessionModal">
            <i class="bi bi-plus-lg me-1"></i> Log Session
        </button>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Subject</th>
                        <th>Duration</th>
                        <th>Notes</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                                No study sessions logged yet.<br>
                                <button type="button" class="btn btn-link mt-2" data-bs-toggle="modal" data-bs-target="#addSessionModal">Log your first session</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td class="ps-4"><?= date('M j, Y', strtotime($session['session_date'])) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background-color: <?= h($session['subject_color']) ?>20; color: <?= h($session['subject_color']) ?>;">
                                        <i class="bi bi-circle-fill small me-1"></i> <?= h($session['subject_name']) ?>
                                    </span>
                                </td>
                                <td><strong><?= h((string)$session['duration_min']) ?></strong> min</td>
                                <td class="text-muted small text-truncate" style="max-width: 200px;" title="<?= h($session['notes']) ?>">
                                    <?= h($session['notes'] ?: '-') ?>
                                </td>
                                <td class="text-end pe-4">
                                    <form method="post" action="" class="d-inline" data-confirm="Delete this session?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Log Study Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addSessionForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['subject_id']) ? 'is-invalid' : '' ?>" id="subject_id" name="subject_id" required>
                            <option value="">Select a subject...</option>
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
                    
                    <div class="mb-3">
                        <label for="session_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['session_date']) ? 'is-invalid' : '' ?>" id="session_date" name="session_date" value="<?= h($_POST['session_date'] ?? date('Y-m-d')) ?>" required>
                        <?php if (isset($errors['session_date'])): ?>
                            <div class="invalid-feedback"><?= h($errors['session_date']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="duration_min" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control <?= isset($errors['duration_min']) ? 'is-invalid' : '' ?>" id="duration_min" name="duration_min" min="1" value="<?= h($_POST['duration_min'] ?? '60') ?>" required>
                        <?php if (isset($errors['duration_min'])): ?>
                            <div class="invalid-feedback"><?= h($errors['duration_min']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="What did you study?"><?= h($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addSessionForm" class="btn btn-primary">Save Session</button>
            </div>
        </div>
    </div>
</div>

<?php 
// Show modal if there are errors from submitting
if (isset($_POST['action']) && $_POST['action'] === 'add' && !empty($errors)) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('addSessionModal')).show(); });</script>";
}
require_once __DIR__ . '/../includes/footer.php'; 
?>
