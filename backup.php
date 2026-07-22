<?php
require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$success = '';
$error = '';

// Handle Direct Download Backup Action
if ($action === 'download') {
    ob_end_clean();
    $backup_data = [];
    $tables = ['settings', 'users', 'customers', 'products', 'quotations', 'invoices', 'lorry_receipts', 'payments', 'audit_log'];
    foreach ($tables as $table) {
        $backup_data[$table] = db_get_table($table);
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=OM_Gupteswar_CRM_Backup_' . date('Y-m-d_H-i-s') . '.json');
    echo json_encode($backup_data, JSON_PRETTY_PRINT);
    exit;
}

// Handle Restore Backup Action
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['backup_file']['tmp_name'];
        $content = file_get_contents($file_tmp);
        $decoded = json_decode($content, true);
        
        if (is_array($decoded)) {
            global $pdo;
            $tables = ['settings', 'users', 'customers', 'products', 'quotations', 'invoices', 'lorry_receipts', 'payments', 'audit_log'];
            
            try {
                $pdo->beginTransaction();
                foreach ($tables as $table) {
                    if (isset($decoded[$table]) && is_array($decoded[$table])) {
                        // Clear the table first
                        $pdo->exec("DELETE FROM `$table`");
                        // Insert rows back
                        foreach ($decoded[$table] as $row) {
                            db_insert($table, $row);
                        }
                    }
                }
                $pdo->commit();
                log_audit('Database Restored', 'Database backup imported successfully');
                $success = 'Database restored successfully! All tables updated.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Restore failed: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid backup file structure! Please upload a valid JSON backup file.';
        }
    } else {
        $error = 'Error uploading file! Please try again.';
    }
}

$page_title = 'Backup & Restore';
$active_menu = 'backup';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';
?>

<div class="page-wrapper">
    <div class="content pb-0">

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-circle-check me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-0">Database Backup & Disaster Restore</h4>
                <p class="text-muted fs-14 mb-0">Export database as JSON snapshots, or import previous backups to recover data.</p>
            </div>
        </div>

        <div class="row">
            <!-- Download Section -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title text-primary"><i class="ti ti-download me-2"></i>Export JSON Backup</h5>
                            <p class="text-muted fs-13">Download a complete snapshot of your offline CRM databases. This file contains settings, customer records, quotation records, invoices, and payments ledger.</p>
                            <div class="alert alert-info py-2 fs-12">
                                <i class="ti ti-info-circle me-1"></i>Backup regularly to keep data safe from local disk failures.
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="backup.php?action=download" class="btn btn-primary btn-lg w-100">Download Backup File</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restore Section -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title text-danger"><i class="ti ti-upload me-2"></i>Restore Database Backup</h5>
                            <p class="text-muted fs-13">Import a previously downloaded JSON database backup file. Uploading a backup will overwrite all current system data.</p>
                            
                            <form action="backup.php?action=restore" method="POST" enctype="multipart/form-data" class="mt-3" id="restoreForm">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Upload JSON Backup File</label>
                                    <input type="file" name="backup_file" accept=".json" class="form-control" required>
                                </div>
                            </form>
                        </div>
                        <div>
                            <button type="submit" form="restoreForm" class="btn btn-danger btn-lg w-100" onclick="return confirm('WARNING: Restoring will overwrite all current database entries. Are you sure you want to proceed?')">Restore Data Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
