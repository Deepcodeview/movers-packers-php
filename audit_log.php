<?php
$page_title = 'Audit Logs';
$active_menu = 'audit_log';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$audit_logs = db_get_table('audit_log');
?>

<div class="page-wrapper">
    <div class="content pb-0">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-0">System Audit Trail Logs</h4>
                <p class="text-muted fs-14 mb-0">Chronological history of all important database adjustments, logs, payments, and invoices.</p>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <button onclick="window.print()" class="btn btn-dark"><i class="ti ti-printer me-2"></i>Print Trail Log</button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User ID</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($audit_logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No system logs registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($audit_logs as $log): ?>
                                    <tr>
                                        <td><code><?php echo date('d M Y, h:i:s A', strtotime($log['timestamp'])); ?></code></td>
                                        <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($log['user_name']); ?></span></td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-light text-dark';
                                            if (strpos($log['action_type'], 'Created') !== false) $badge_class = 'bg-success-transparent text-success';
                                            elseif (strpos($log['action_type'], 'Updated') !== false) $badge_class = 'bg-warning-transparent text-warning';
                                            elseif (strpos($log['action_type'], 'Deleted') !== false) $badge_class = 'bg-danger-transparent text-danger';
                                            elseif (strpos($log['action_type'], 'Payment') !== false) $badge_class = 'bg-info-transparent text-info';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> fs-12"><?php echo htmlspecialchars($log['action_type']); ?></span>
                                        </td>
                                        <td><span class="fs-13 text-muted"><?php echo htmlspecialchars($log['description']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
