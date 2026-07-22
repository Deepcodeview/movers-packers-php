<?php
$page_title = 'Lorry Receipts (LR)';
$active_menu = 'lorry_receipt';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : '';
$error = '';
$success = '';

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $lr_no = trim($_POST['lr_no']);
        $lr_date = trim($_POST['lr_date']) ?: date('Y-m-d');
        $invoice_id = $_POST['invoice_id'];
        
        // Consignee (Receiver)
        $consignee_name = trim($_POST['consignee_name']);
        $consignee_mobile = trim($_POST['consignee_mobile']);
        $consignee_gstin = trim($_POST['consignee_gstin']);
        $delivery_address = trim($_POST['delivery_address']);

        // Carrier details
        $vehicle_number = trim($_POST['vehicle_number']);
        $driver_name = trim($_POST['driver_name']);
        $driver_mobile = trim($_POST['driver_mobile']);
        $driving_license = trim($_POST['driving_license']);
        $carrier_name = trim($_POST['carrier_name']) ?: 'SELF';

        // Shifting Details
        $articles_count = (int)$_POST['articles_count'];
        $description = trim($_POST['description']) ?: 'Household Goods / Personal Effects Shifting';
        $actual_weight = (float)$_POST['actual_weight'];
        $charged_weight = (float)$_POST['charged_weight'];
        $value_of_goods = (float)$_POST['value_of_goods'];
        
        // Payment Terms
        $freight_terms = $_POST['freight_terms']; // 'Paid', 'To Pay', 'TBB' (To Be Billed)
        $remarks = trim($_POST['remarks']);

        if (empty($lr_no) || empty($consignee_name) || empty($invoice_id)) {
            $error = 'LR Number, Consignee Name, and Associated Invoice are required!';
            $action = empty($id) ? 'new' : 'edit';
        } else {
            $lr_data = [
                'lr_no' => $lr_no,
                'lr_date' => $lr_date,
                'invoice_id' => $invoice_id,
                'consignee_name' => $consignee_name,
                'consignee_mobile' => $consignee_mobile,
                'consignee_gstin' => $consignee_gstin,
                'delivery_address' => $delivery_address,
                'vehicle_number' => $vehicle_number,
                'driver_name' => $driver_name,
                'driver_mobile' => $driver_mobile,
                'driving_license' => $driving_license,
                'carrier_name' => $carrier_name,
                'articles_count' => $articles_count,
                'description' => $description,
                'actual_weight' => $actual_weight,
                'charged_weight' => $charged_weight,
                'value_of_goods' => $value_of_goods,
                'freight_terms' => $freight_terms,
                'remarks' => $remarks
            ];

            if (empty($id)) {
                // Check duplicate LR No
                $existing_lrs = db_get_table('lorry_receipts');
                $is_duplicate = false;
                foreach ($existing_lrs as $elr) {
                    if ($elr['lr_no'] === $lr_no) {
                        $is_duplicate = true;
                        break;
                    }
                }
                if ($is_duplicate) {
                    $error = 'Lorry Receipt Number already exists! Please use a unique number.';
                    $action = 'new';
                } else {
                    db_insert('lorry_receipts', $lr_data);
                    log_audit('LR Bilty Created', 'Created Lorry Receipt (Bilty) No: ' . $lr_no);
                    $success = 'Lorry Receipt generated successfully!';
                    $action = 'list';
                }
            } else {
                db_update('lorry_receipts', $id, $lr_data);
                log_audit('LR Bilty Updated', 'Updated Lorry Receipt (Bilty) No: ' . $lr_no);
                $success = 'Lorry Receipt details updated!';
                $action = 'list';
            }
        }
    }
} elseif ($action === 'delete' && !empty($id)) {
    // Access restriction: Staff cannot delete LRs
    if ($_SESSION['user_role'] !== 'Administrator') {
        $error = 'Access Denied: Staff users do not have permissions to delete Lorry Receipts.';
    } else {
        $lr = db_find('lorry_receipts', $id);
        if ($lr) {
            db_delete('lorry_receipts', $id);
            log_audit('LR Bilty Deleted', 'Deleted Lorry Receipt No: ' . $lr['lr_no']);
            $success = 'Lorry Receipt deleted successfully!';
        }
    }
    $action = 'list';
}

$lorry_receipts = db_get_table('lorry_receipts');
$invoices = db_get_table('invoices');
?>

<div class="page-wrapper">
    <div class="content pb-0">

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <i class="ti ti-circle-check me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <i class="ti ti-alert-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ACTION: LIST LORRY RECEIPTS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0">Lorry Receipts (LR / Bilty)</h4>
                    <p class="text-muted fs-14 mb-0">Generate, view, and print consignment transport notes (Bilty copy) for active consignments.</p>
                </div>
                <div>
                    <a href="lorry_receipts.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-file-plus me-2"></i>Generate Lorry Receipt (Bilty)
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0 no-print">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>LR Number</th>
                                    <th>LR Date</th>
                                    <th>Invoice Ref</th>
                                    <th>From ➜ To</th>
                                    <th>Consignee (Receiver)</th>
                                    <th>Freight Term</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lorry_receipts as $lr): 
                                    $inv = db_find('invoices', $lr['invoice_id']);
                                    $c = $inv ? db_find('customers', $inv['customer_id']) : null;
                                    ?>
                                    <tr>
                                        <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($lr['lr_no']); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($lr['lr_date'])); ?></td>
                                        <td>
                                            <?php if ($inv): ?>
                                                <a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="fw-semibold">
                                                    <?php echo htmlspecialchars($inv['invoice_number']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($inv): ?>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($lr['consignee_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $lr['freight_terms'] === 'Paid' ? 'success' : ($lr['freight_terms'] === 'To Pay' ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars($lr['freight_terms']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="lorry_receipts.php?action=view&id=<?php echo $lr['id']; ?>" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" title="Print Bilty Copy"><i class="ti ti-printer"></i></a>
                                            <a href="lorry_receipts.php?action=edit&id=<?php echo $lr['id']; ?>" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-edit"></i></a>
                                            <?php if ($_SESSION['user_role'] === 'Administrator'): ?>
                                                <a href="lorry_receipts.php?action=delete&id=<?php echo $lr['id']; ?>" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this LR copy?')"><i class="ti ti-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: CREATE/EDIT LORRY RECEIPT -->
        <?php elseif ($action === 'new' || $action === 'edit'):
            $lr = [];
            $selected_inv = null;
            
            if ($action === 'edit') {
                $lr = db_find('lorry_receipts', $id);
                $selected_inv = db_find('invoices', $lr['invoice_id']);
            } elseif (!empty($invoice_id)) {
                $selected_inv = db_find('invoices', $invoice_id);
            }
            $selected_cust = null;
            $total_articles = 0;
            $items_desc_str = 'Household Goods / Personal Effects Shifting';
            if ($selected_inv) {
                $selected_cust = db_find('customers', $selected_inv['customer_id']);
                if ($selected_inv['quotation_id']) {
                    $selected_q = db_find('quotations', $selected_inv['quotation_id']);
                    if ($selected_q && !empty($selected_q['items'])) {
                        $items_arr = json_decode($selected_q['items'], true);
                        if (is_array($items_arr)) {
                            $items_desc_list = [];
                            foreach ($items_arr as $name => $qty) {
                                $qty = (int)$qty;
                                if ($qty > 0) {
                                    $total_articles += $qty;
                                    $items_desc_list[] = $qty . " " . $name;
                                }
                            }
                            if (!empty($items_desc_list)) {
                                $items_desc_str = implode(", ", $items_desc_list);
                            }
                        }
                    }
                }
            }
            
            // Auto generate LR No sequentially in professional LR/YY-YY/XXXX format
            $max_lr_seq = 0;
            $all_lrs = db_get_table('lorry_receipts');
            foreach ($all_lrs as $item) {
                $parts = explode('/', $item['lr_no']);
                $seq = (int)end($parts);
                if ($seq > $max_lr_seq) {
                    $max_lr_seq = $seq;
                }
            }
            $next_lr_seq = $max_lr_seq + 1;
            $lr_no_val = isset($lr['lr_no']) ? $lr['lr_no'] : 'LR/' . date('y') . '-' . date('y', strtotime('+1 year')) . '/' . str_pad($next_lr_seq, 4, '0', STR_PAD_LEFT);
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0"><?php echo $action === 'new' ? 'Generate Lorry Receipt (Bilty)' : 'Edit Lorry Receipt'; ?></h4>
                    <p class="text-muted fs-14 mb-0">Fill in transit details. Send origin, destination, and consignment specs to carriage layout.</p>
                </div>
                <div>
                    <a href="lorry_receipts.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0 no-print">
                <div class="card-body">
                    <form action="lorry_receipts.php?action=save<?php echo !empty($id) ? '&id=' . $id : ''; ?>" method="POST">
                        <div class="row">
                            <!-- Left: Lorry Details -->
                            <div class="col-lg-6">
                                <h5 class="mb-3 text-dark border-bottom pb-2">Carrier & Consignment Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">LR Number *</label>
                                        <input type="text" name="lr_no" class="form-control" value="<?php echo htmlspecialchars($lr_no_val); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">LR Date</label>
                                        <input type="date" name="lr_date" class="form-control" value="<?php echo htmlspecialchars(isset($lr['lr_date']) ? $lr['lr_date'] : date('Y-m-d')); ?>">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-medium">Select Tax Invoice Link *</label>
                                        <select name="invoice_id" id="invoice_select" class="form-select" required>
                                            <option value="">-- Choose GST Invoice --</option>
                                            <?php foreach ($invoices as $inv): 
                                                $cust = db_find('customers', $inv['customer_id']);
                                                $cust_name = $cust ? $cust['name'] : 'Unknown';
                                                
                                                // Retrieve linked quotation to count items
                                                $q_ref = $inv['quotation_id'] ? db_find('quotations', $inv['quotation_id']) : null;
                                                $inv_articles_count = 0;
                                                $inv_items_desc = [];
                                                if ($q_ref && !empty($q_ref['items'])) {
                                                    $items_arr = json_decode($q_ref['items'], true);
                                                    if (is_array($items_arr)) {
                                                        foreach ($items_arr as $name => $qty) {
                                                            $qty = (int)$qty;
                                                            if ($qty > 0) {
                                                                $inv_articles_count += $qty;
                                                                $inv_items_desc[] = $qty . " " . $name;
                                                            }
                                                        }
                                                    }
                                                }
                                                $inv_items_desc_str = !empty($inv_items_desc) ? implode(", ", $inv_items_desc) : 'Household Goods / Personal Effects Shifting';
                                                ?>
                                                <option value="<?php echo $inv['id']; ?>"
                                                        data-vehicle="<?php echo htmlspecialchars($inv['vehicle_number']); ?>"
                                                        data-driver="<?php echo htmlspecialchars($inv['driver_name']); ?>"
                                                        data-from="<?php echo htmlspecialchars($inv['from_city']); ?>"
                                                        data-to="<?php echo htmlspecialchars($inv['to_city']); ?>"
                                                        data-cust-name="<?php echo htmlspecialchars($cust_name); ?>"
                                                        data-cust-mobile="<?php echo htmlspecialchars($cust ? $cust['mobile'] : ''); ?>"
                                                        data-cust-gstin="<?php echo htmlspecialchars($cust ? $cust['gstin'] : ''); ?>"
                                                        data-cust-address="<?php echo htmlspecialchars($inv['to_city']); ?>"
                                                        data-grand-total="<?php echo htmlspecialchars($inv['grand_total']); ?>"
                                                        data-articles-count="<?php echo $inv_articles_count; ?>"
                                                        data-items-desc="<?php echo htmlspecialchars($inv_items_desc_str); ?>"
                                                        <?php echo (($selected_inv && $selected_inv['id'] === $inv['id']) || (isset($lr['invoice_id']) && $lr['invoice_id'] === $inv['id'])) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($inv['invoice_number']); ?> - <?php echo htmlspecialchars($cust_name); ?> (<?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Vehicle/Truck Number</label>
                                        <input type="text" name="vehicle_number" id="lr_vehicle" class="form-control" placeholder="e.g. OD-10-A-1234" value="<?php echo htmlspecialchars(isset($lr['vehicle_number']) ? $lr['vehicle_number'] : ($selected_inv ? $selected_inv['vehicle_number'] : '')); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Driver Name</label>
                                        <input type="text" name="driver_name" id="lr_driver" class="form-control" placeholder="e.g. Shyam Sethi" value="<?php echo htmlspecialchars(isset($lr['driver_name']) ? $lr['driver_name'] : ($selected_inv ? $selected_inv['driver_name'] : '')); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Driver Mobile</label>
                                        <input type="text" name="driver_mobile" class="form-control" placeholder="Driver phone" value="<?php echo htmlspecialchars(isset($lr['driver_mobile']) ? $lr['driver_mobile'] : ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Driver License Number (DL)</label>
                                        <input type="text" name="driving_license" class="form-control" placeholder="Driver license" value="<?php echo htmlspecialchars(isset($lr['driving_license']) ? $lr['driving_license'] : ''); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Carrier Name</label>
                                        <input type="text" name="carrier_name" class="form-control" placeholder="Company self or third party lorry agency" value="<?php echo htmlspecialchars(isset($lr['carrier_name']) ? $lr['carrier_name'] : 'OM GUPTESWAR TRANSPORTER'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Consignee & Goods Details -->
                            <div class="col-lg-6">
                                <h5 class="mb-3 text-dark border-bottom pb-2">Consignee (Receiver) & Shifting Info</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">Consignee (Receiver) Name *</label>
                                        <input type="text" name="consignee_name" id="lr_consignee_name" class="form-control" placeholder="e.g. Ramesh Kumar" value="<?php echo htmlspecialchars(isset($lr['consignee_name']) ? $lr['consignee_name'] : ($selected_cust ? $selected_cust['name'] : '')); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Consignee Mobile</label>
                                        <input type="text" name="consignee_mobile" id="lr_consignee_mobile" class="form-control" placeholder="Mobile Number" value="<?php echo htmlspecialchars(isset($lr['consignee_mobile']) ? $lr['consignee_mobile'] : ($selected_cust ? $selected_cust['mobile'] : '')); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Consignee GSTIN (Optional)</label>
                                        <input type="text" name="consignee_gstin" id="lr_consignee_gstin" class="form-control" placeholder="GSTIN if corporate consignment" value="<?php echo htmlspecialchars(isset($lr['consignee_gstin']) ? $lr['consignee_gstin'] : ($selected_cust ? $selected_cust['gstin'] : '')); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-medium">Delivery Destination Address *</label>
                                        <textarea name="delivery_address" id="lr_delivery_address" class="form-control" rows="2" placeholder="Receiver delivery location details" required><?php echo htmlspecialchars(isset($lr['delivery_address']) ? $lr['delivery_address'] : ($selected_inv ? $selected_inv['to_city'] : '')); ?></textarea>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Articles Count (No of boxes/pkgs)</label>
                                        <input type="number" name="articles_count" id="lr_articles_count" class="form-control" value="<?php echo htmlspecialchars(isset($lr['articles_count']) ? $lr['articles_count'] : ($total_articles ?: '0')); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Actual Weight (Kg)</label>
                                        <input type="number" step="0.1" name="actual_weight" class="form-control" value="<?php echo htmlspecialchars(isset($lr['actual_weight']) ? $lr['actual_weight'] : '0'); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Charged Weight (Kg)</label>
                                        <input type="number" step="0.1" name="charged_weight" class="form-control" value="<?php echo htmlspecialchars(isset($lr['charged_weight']) ? $lr['charged_weight'] : '0'); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Freight Terms *</label>
                                        <select name="freight_terms" class="form-select" required>
                                            <option value="Paid" <?php echo (isset($lr['freight_terms']) && $lr['freight_terms'] === 'Paid') ? 'selected' : ''; ?>>Paid (Sender Paid)</option>
                                            <option value="To Pay" <?php echo (isset($lr['freight_terms']) && $lr['freight_terms'] === 'To Pay') ? 'selected' : ''; ?>>To Pay (Receiver Will Pay)</option>
                                            <option value="TBB" <?php echo (isset($lr['freight_terms']) && $lr['freight_terms'] === 'TBB') ? 'selected' : ''; ?>>TBB (To Be Billed / Corporate Account)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estimated Value of Goods (₹)</label>
                                        <input type="number" name="value_of_goods" id="lr_value_of_goods" class="form-control" value="<?php echo htmlspecialchars(isset($lr['value_of_goods']) ? $lr['value_of_goods'] : ($selected_inv ? $selected_inv['grand_total'] : '0')); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Description of Goods</label>
                                        <input type="text" name="description" id="lr_description" class="form-control" placeholder="e.g. Household Goods Shifting" value="<?php echo htmlspecialchars(isset($lr['description']) ? $lr['description'] : $items_desc_str); ?>">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Remarks / Special Instructions</label>
                                        <input type="text" name="remarks" class="form-control" placeholder="Special carriage handling remarks" value="<?php echo htmlspecialchars(isset($lr['remarks']) ? $lr['remarks'] : ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Generate Lorry Receipt</button>
                            <a href="lorry_receipts.php" class="btn btn-light btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Auto fill from selected Invoice selection -->
            <script src="assets/js/jquery-3.7.1.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#invoice_select').on('change', function() {
                        var selectedOption = $(this).find('option:selected');
                        if (selectedOption.val()) {
                            $('#lr_vehicle').val(selectedOption.data('vehicle'));
                            $('#lr_driver').val(selectedOption.data('driver'));
                            $('#lr_consignee_name').val(selectedOption.data('cust-name'));
                            $('#lr_consignee_mobile').val(selectedOption.data('cust-mobile'));
                            $('#lr_consignee_gstin').val(selectedOption.data('cust-gstin'));
                            $('#lr_delivery_address').val(selectedOption.data('cust-address'));
                            $('#lr_value_of_goods').val(selectedOption.data('grand-total'));
                            $('#lr_articles_count').val(selectedOption.data('articles-count'));
                            $('#lr_description').val(selectedOption.data('items-desc'));
                        }
                    });
                });
            </script>

        <?php elseif ($action === 'view' && !empty($id)):
            $lr = db_find('lorry_receipts', $id);
            $inv = db_find('invoices', $lr['invoice_id']);
            $c = db_find('customers', $inv['customer_id']);
            $settings = db_get_table('settings');
            
            // Generate dynamic WhatsApp share link
            $clean_phone = preg_replace('/[^0-9]/', '', $lr['consignee_mobile'] ?: ($c ? $c['mobile'] : ''));
            if (strlen($clean_phone) === 10) { $clean_phone = '91' . $clean_phone; }
            $share_text = urlencode("🚚 *OM GUPTESWAR TRANSPORTER PACKERS & MOVERS* 🚚\n"
                        . "📦 *LORRY RECEIPT (BILTY) IN TRANSIT*\n\n"
                        . "Dear *" . $lr['consignee_name'] . "*,\n"
                        . "Your consignment has been loaded and is currently in transit. Here are the carriage details:\n\n"
                        . "🎫 *LR Number (Bilty):* " . $lr['lr_no'] . "\n"
                        . "📅 *Date:* " . date('d-M-Y', strtotime($lr['lr_date'])) . "\n"
                        . "🚛 *Vehicle/Truck No:* " . ($lr['vehicle_number'] ?: 'N/A') . "\n"
                        . "👨🏻‍✈️ *Driver Name:* " . ($lr['driver_name'] ?: 'N/A') . " (" . ($lr['driver_mobile'] ?: 'N/A') . ")\n"
                        . "📍 *Delivery Destination:* " . $lr['delivery_address'] . "\n"
                        . "📦 *Goods Description:* " . $lr['description'] . " (" . $lr['articles_count'] . " articles)\n"
                        . "⚖️ *Charged Weight:* " . number_format($lr['charged_weight'], 1) . " Kg\n"
                        . "💳 *Freight Terms:* " . $lr['freight_terms'] . "\n\n"
                        . "Have a safe and secure transit!\n"
                        . "For tracking assistance, call: 7789052910 / 8457952219.");
            $wa_link = "https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . $share_text;

            // Transport bill copies
            $copies = [
                'CONSIGNOR COPY' => 'text-primary border-primary',
                'CONSIGNEE COPY' => 'text-success border-success',
                'CARRIER / DRIVER COPY' => 'text-danger border-danger'
            ];
            ?>
            <!-- Actions -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0">Lorry Receipt Slip (Consignment Note)</h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success"><i class="ti ti-brand-whatsapp me-2"></i>Share on WhatsApp</a>
                    <button onclick="window.print()" class="btn btn-dark"><i class="ti ti-printer me-2"></i>Print Bilty Copies</button>
                    <a href="lorry_receipts.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <!-- Print multiple copies on a single print layout page -->
            <div id="print-bilty">
                <?php 
                $copy_index = 0;
                foreach ($copies as $copy_name => $copy_color_class): 
                    $copy_index++;
                    ?>
                    <div class="card shadow-sm border border-secondary p-3 mb-5 bilty-copy-block <?php echo ($copy_index > 1) ? 'page-break-before' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <span class="fs-12 fw-bold text-muted">CONSIGNMENT NOTE / LORRY RECEIPT (BILTY)</span>
                            <span class="badge border <?php echo $copy_color_class; ?> px-3 py-1 fs-12 fw-bold"><?php echo $copy_name; ?></span>
                        </div>
                        
                        <!-- Header Company Info -->
                        <div class="row align-items-center mb-3">
                            <div class="col-sm-3 text-center text-sm-start">
                                <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-2" style="width: 55px; height: 55px;">
                                    <i class="ti ti-truck-delivery fs-28"></i>
                                </div>
                            </div>
                            <div class="col-sm-9 text-center text-sm-start">
                                <h4 class="fw-bold mb-1 text-danger"><?php echo htmlspecialchars($settings['company_name']); ?></h4>
                                <p class="fs-12 text-muted mb-0"><?php echo htmlspecialchars($settings['address']); ?></p>
                                <p class="fs-12 mb-0"><strong>GSTIN:</strong> <?php echo htmlspecialchars($settings['gstin']); ?> | <strong>Phone:</strong> <?php echo htmlspecialchars($settings['phone']); ?></p>
                            </div>
                        </div>

                        <div class="row g-2 border border-dark rounded p-2 bg-light mb-3">
                            <div class="col-4 border-end border-dark">
                                <span class="fs-11 text-muted d-block">LR Number</span>
                                <strong class="fs-14 text-dark"><?php echo htmlspecialchars($lr['lr_no']); ?></strong>
                            </div>
                            <div class="col-4 border-end border-dark">
                                <span class="fs-11 text-muted d-block">LR Date</span>
                                <strong class="fs-14 text-dark"><?php echo date('d-M-Y', strtotime($lr['lr_date'])); ?></strong>
                            </div>
                            <div class="col-4">
                                <span class="fs-11 text-muted d-block">Associated Invoice</span>
                                <strong class="fs-14 text-dark"><?php echo htmlspecialchars($inv['invoice_number']); ?></strong>
                            </div>
                        </div>

                        <!-- Routing and Consignment addresses -->
                        <div class="row border rounded border-dark p-2 mb-3">
                            <!-- Consignor / Sender -->
                            <div class="col-6 border-end border-dark">
                                <span class="badge bg-secondary mb-2 fs-10">CONSIGNOR (SENDER)</span>
                                <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($c['name']); ?></h6>
                                <p class="fs-12 text-muted mb-1"><?php echo htmlspecialchars($c['address']); ?></p>
                                <span class="fs-11 d-block text-dark"><strong>Phone:</strong> <?php echo htmlspecialchars($c['mobile']); ?></span>
                                <?php if (!empty($c['gstin'])): ?>
                                    <span class="fs-11 d-block text-dark"><strong>GSTIN:</strong> <?php echo htmlspecialchars($c['gstin']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Consignee / Receiver -->
                            <div class="col-6">
                                <span class="badge bg-danger mb-2 fs-10">CONSIGNEE (RECEIVER)</span>
                                <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($lr['consignee_name']); ?></h6>
                                <p class="fs-12 text-muted mb-1"><?php echo htmlspecialchars($lr['delivery_address']); ?></p>
                                <span class="fs-11 d-block text-dark"><strong>Phone:</strong> <?php echo htmlspecialchars($lr['consignee_mobile']); ?></span>
                                <?php if (!empty($lr['consignee_gstin'])): ?>
                                    <span class="fs-11 d-block text-dark"><strong>GSTIN:</strong> <?php echo htmlspecialchars($lr['consignee_gstin']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Carrier and Driver Information -->
                        <div class="row border rounded border-dark p-2 mb-3 bg-light">
                            <div class="col-md-3 col-6">
                                <span class="fs-11 text-muted d-block">Vehicle / Truck No</span>
                                <span class="fw-bold text-dark fs-12"><?php echo htmlspecialchars($lr['vehicle_number'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="fs-11 text-muted d-block">Driver Name</span>
                                <span class="fw-bold text-dark fs-12"><?php echo htmlspecialchars($lr['driver_name'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="fs-11 text-muted d-block">Driver DL Number</span>
                                <span class="fw-bold text-dark fs-12"><?php echo htmlspecialchars($lr['driving_license'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="fs-11 text-muted d-block">Lorry Carrier Name</span>
                                <span class="fw-bold text-dark fs-12"><?php echo htmlspecialchars($lr['carrier_name'] ?: 'N/A'); ?></span>
                            </div>
                        </div>

                        <!-- Consignment Cargo Specs Table -->
                        <div class="table-responsive mb-3 border border-dark rounded">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th style="width: 15%;">No of Articles</th>
                                        <th style="width: 45%;">Description of Goods (Inv Content)</th>
                                        <th style="width: 12%;">Act Weight (Kg)</th>
                                        <th style="width: 12%;">Chg Weight (Kg)</th>
                                        <th style="width: 16%;">Estimated Value (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-center">
                                        <td class="fw-bold fs-13"><?php echo htmlspecialchars($lr['articles_count']); ?> Box/Pkgs</td>
                                        <td class="text-start fs-13"><?php echo htmlspecialchars($lr['description']); ?></td>
                                        <td class="fs-13"><?php echo number_format($lr['actual_weight'], 1); ?> Kg</td>
                                        <td class="fs-13 fw-semibold"><?php echo number_format($lr['charged_weight'], 1); ?> Kg</td>
                                        <td class="fs-13 fw-bold">₹<?php echo number_format($lr['value_of_goods'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sub-details and signature -->
                        <div class="row align-items-center mt-3">
                            <div class="col-md-8 col-sm-7 fs-11 text-muted">
                                <p class="mb-1"><strong>Payment Freight Terms:</strong> <span class="badge bg-dark px-2 text-white"><?php echo htmlspecialchars($lr['freight_terms']); ?></span></p>
                                <?php if (!empty($lr['remarks'])): ?>
                                    <p class="mb-1"><strong>Special Instructions:</strong> <?php echo htmlspecialchars($lr['remarks']); ?></p>
                                <?php endif; ?>
                                <p class="mb-0"><strong>Note:</strong> Cargo transit insurance is subject to policy limitations. Goods are loaded and dispatched in good order.</p>
                            </div>
                            <div class="col-md-4 col-sm-5 text-center mt-3 mt-sm-0">
                                <p class="fs-11 text-muted mb-4">Authorized Signature / Stamp</p>
                                <div class="border-top border-dark w-75 mx-auto"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom Bilty Print Layout overrides -->
            <style>
                @media print {
                    .page-break-before {
                        page-break-before: always !important;
                    }
                    .bilty-copy-block {
                        border: 2px solid #000 !important;
                        box-shadow: none !important;
                        margin-bottom: 2rem !important;
                    }
                    .bilty-copy-block select, 
                    .bilty-copy-block button {
                        display: none !important;
                    }
                }
            </style>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
