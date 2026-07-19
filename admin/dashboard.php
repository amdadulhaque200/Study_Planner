
<div class="row mb-4">
    <div class="col-12">
        <h2 class="h3 mb-1">Admin Dashboard</h2>
        <p class="text-muted mb-0">Platform overview and user management.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-4 h-100">
            <div class="card-body">
                <i class="bi bi-people display-4 text-primary mb-3"></i>
                <h3 class="fw-bold mb-0"><?= $totalUsers ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-4 h-100">
            <div class="card-body">
                <i class="bi bi-stopwatch display-4 text-success mb-3"></i>
                <h3 class="fw-bold mb-0"><?= $totalSessions ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Study Sessions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-4 h-100">
            <div class="card-body">
                <i class="bi bi-check2-square display-4 text-info mb-3"></i>
                <h3 class="fw-bold mb-0"><?= $totalTasks ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Total Tasks</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-4 h-100">
            <div class="card-body">
                <i class="bi bi-journal-text display-4 text-warning mb-3"></i>
                <h3 class="fw-bold mb-0"><?= $totalNotes ?></h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold">Total Notes</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 pb-0">
        <h5 class="fw-bold">User Management</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4 text-muted">#<?= $u['id'] ?></td>
                            <td>
                                <div class="fw-bold"><?= h($u['username']) ?></div>
                                <div class="small text-muted"><?= h($u['email']) ?></div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Student</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td class="small">
                                <?= $u['last_login'] ? date('M j, Y H:i', strtotime($u['last_login'])) : '<span class="text-muted">Never</span>' ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                                    <form method="post" action="" class="d-inline"
                                        data-confirm="Are you sure you want to completely delete this user and all their data?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary disabled" title="Cannot delete yourself">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>