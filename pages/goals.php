
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
