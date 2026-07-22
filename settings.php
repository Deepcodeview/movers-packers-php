<?php
$page_title = 'Company Settings';
$active_menu = 'settings';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_settings = [
        'company_name' => trim($_POST['company_name']),
        'address' => trim($_POST['address']),
        'gstin' => trim($_POST['gstin']),
        'pan' => trim($_POST['pan']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'website' => trim($_POST['website']),
        'bank_details' => trim($_POST['bank_details']),
        'terms' => trim($_POST['terms']),
        'footer' => trim($_POST['footer'])
    ];

    if (empty($updated_settings['company_name'])) {
        $error = 'Company name cannot be blank!';
    } else {
        db_update('settings', '', $updated_settings);
        log_audit('Settings Updated', 'Company profiles and settings variables updated');
        $success = 'Company settings saved successfully!';
        
        // Refresh local settings variable
        $settings = $updated_settings;
    }
}
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
                <h4 class="mb-0">Company Profile & Custom Settings</h4>
                <p class="text-muted fs-14 mb-0">Configure metadata printed on quotations, bills, receipts, and invoices.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="settings.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Company Name *</label>
                            <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">GSTIN (GST Number) *</label>
                            <input type="text" name="gstin" class="form-control" value="<?php echo htmlspecialchars($settings['gstin']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">PAN Number</label>
                            <input type="text" name="pan" class="form-control" value="<?php echo htmlspecialchars($settings['pan']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Phone Numbers (Comma separated)</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($settings['phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Contact Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($settings['email']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Website</label>
                            <input type="text" name="website" class="form-control" value="<?php echo htmlspecialchars($settings['website']); ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-medium">Full Office Address</label>
                            <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3 text-dark">Invoice & Bank Printout Details</h5>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Bank Information (Printed on Invoices)</label>
                            <textarea name="bank_details" class="form-control" rows="5"><?php echo htmlspecialchars($settings['bank_details']); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Terms & Conditions (Printed on Quotations)</label>
                            <textarea name="terms" class="form-control" rows="5"><?php echo htmlspecialchars($settings['terms']); ?></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-medium">Default Invoice Footer Notice</label>
                            <input type="text" name="footer" class="form-control" value="<?php echo htmlspecialchars($settings['footer']); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-lg btn-primary">Save Company Settings</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
