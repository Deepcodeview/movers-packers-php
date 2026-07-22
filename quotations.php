<?php
$page_title = 'Quotations';
$active_menu = 'quotations';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$success = '';
$error = '';
?>
<style>
    /* Premium checklist styling for both desktop and mobile */
    .checklist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 8px;
    }
    .item-row {
        padding: 10px 12px !important;
        border-radius: 10px !important;
        margin-bottom: 0 !important;
        background: #ffffff !important;
        border: 1px solid #E2E8F0 !important;
        transition: all 0.2s ease !important;
    }
    .item-row.active-item {
        background: #ECFDF5 !important; /* soft emerald/green background */
        border-color: #10B981 !important; /* green border */
    }
    .item-row .item-name {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #1E293B !important;
    }
    .item-row.active-item .item-name {
        color: #065F46 !important;
    }
    /* Style quantity selector button controls */
    .qty-controls {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    .qty-btn {
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        border: 1.5px solid #CBD5E1 !important;
        background: #ffffff !important;
        color: #64748B !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        user-select: none !important;
    }
    .qty-btn:active {
        transform: scale(0.9) !important;
    }
    .qty-btn.btn-plus {
        border-color: #FF5E3A !important;
        color: #FF5E3A !important;
    }
    .qty-btn.btn-plus:active {
        background-color: rgba(255, 94, 58, 0.05) !important;
    }
    .qty-btn.btn-minus:active {
        background-color: #F1F5F9 !important;
    }
    .qty-display {
        width: 28px !important;
        text-align: center !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        color: #1E293B !important;
        border: none !important;
        background: transparent !important;
    }
    .item-row.active-item .qty-display {
        color: #065F46 !important;
    }
</style>
<?php
// Fetch items dynamically from products catalog
$products = db_get_table('products');
$predefined_items = [];
foreach ($products as $p) {
    $predefined_items[] = $p['name'];
}


if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = $_POST['customer_id'];
        $from_city = trim($_POST['from_city']);
        $to_city = trim($_POST['to_city']);
        $phone = trim($_POST['phone']);
        $date = trim($_POST['quotation_date']);
        
        // Items checklist parsing
        $items = [];
        if (isset($_POST['items_qty']) && is_array($_POST['items_qty'])) {
            foreach ($_POST['items_qty'] as $item_name => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    $items[] = [
                        'particulars' => $item_name,
                        'qty' => $qty
                    ];
                }
            }
        }

        // Costs
        $packing_charge = (float)$_POST['packing_charge'];
        $unpacking_charge = (float)$_POST['unpacking_charge'];
        $loading_charge = (float)$_POST['loading_charge'];
        $unloading_charge = (float)$_POST['unloading_charge'];
        $escort_charge = (float)$_POST['escort_charge'];
        $storage_charge = (float)$_POST['storage_charge'];
        $insurance_charge = (float)$_POST['insurance_charge'];
        $gst_rate = (float)$_POST['gst_rate'];

        $subtotal = $packing_charge + $unpacking_charge + $loading_charge + $unloading_charge + $escort_charge + $storage_charge + $insurance_charge;
        $gst_amount = ($subtotal * $gst_rate) / 100;
        $grand_total = $subtotal + $gst_amount;

        // Fetch quotations first to prevent undefined variable count error
        $quotations = db_get_table('quotations');
        $max_q_seq = 0;
        foreach ($quotations as $q_item) {
            $parts = explode('/', $q_item['quotation_number']);
            $seq = (int)end($parts);
            if ($seq > $max_q_seq) {
                $max_q_seq = $seq;
            }
        }
        $next_q_seq = $max_q_seq + 1;
        $quotation_number = 'Q/' . date('y') . '-' . date('y', strtotime('+1 year')) . '/' . str_pad($next_q_seq, 4, '0', STR_PAD_LEFT);

        $quotation_data = [
            'quotation_number' => $quotation_number,
            'customer_id' => $customer_id,
            'from_city' => $from_city,
            'to_city' => $to_city,
            'phone' => $phone,
            'quotation_date' => $date ?: date('Y-m-d'),
            'items' => $items,
            'packing_charge' => $packing_charge,
            'unpacking_charge' => $unpacking_charge,
            'loading_charge' => $loading_charge,
            'unloading_charge' => $unloading_charge,
            'escort_charge' => $escort_charge,
            'storage_charge' => $storage_charge,
            'insurance_charge' => $insurance_charge,
            'subtotal' => $subtotal,
            'gst_rate' => $gst_rate,
            'gst_amount' => $gst_amount,
            'grand_total' => $grand_total,
        ];

        db_insert('quotations', $quotation_data);
        log_audit('Quotation Created', 'Generated Quotation: ' . $quotation_number . ' for route ' . $from_city . ' to ' . $to_city);
        $success = 'Quotation created successfully!';
        $action = 'list';
    }
}

$quotations = db_get_table('quotations');
$customers = db_get_table('customers');
?>

<div class="page-wrapper">
    <div class="content pb-0">

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-circle-check me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ACTION: LIST QUOTATIONS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Quotations Registry</h4>
                    <p class="text-muted fs-14 mb-0">Manage price estimates and transit item lists.</p>
                </div>
                <div>
                    <a href="quotations.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-file-plus me-2"></i>New Quotation
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Quotation No.</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Subtotal</th>
                                    <th>GST Tax (Amount)</th>
                                    <th>Grand Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotations as $q): ?>
                                    <tr>
                                        <td><a href="quotations.php?action=view&id=<?php echo $q['id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($q['quotation_number']); ?></a></td>
                                        <td>
                                            <?php 
                                            $c = db_find('customers', $q['customer_id']);
                                            echo htmlspecialchars($c ? $c['name'] : 'Unknown');
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($q['quotation_date'])); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($q['from_city']); ?> ➜ <?php echo htmlspecialchars($q['to_city']); ?></span></td>
                                        <td>₹<?php echo number_format($q['subtotal'], 2); ?></td>
                                        <td><?php echo $q['gst_rate']; ?>% (₹<?php echo number_format($q['gst_amount'], 2); ?>)</td>
                                        <td class="fw-bold">₹<?php echo number_format($q['grand_total'], 2); ?></td>
                                        <td>
                                            <a href="quotations.php?action=view&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" title="View / Print Slip"><i class="ti ti-printer"></i></a>
                                            <a href="invoices.php?action=new&quotation_id=<?php echo $q['id']; ?>" class="btn btn-sm btn-icon btn-outline-success" data-bs-toggle="tooltip" title="Convert to Invoice"><i class="ti ti-file-invoice"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: NEW QUOTATION -->
        <?php elseif ($action === 'new'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Generate New Quotation</h4>
                    <p class="text-muted fs-14 mb-0">Select a customer, input transport details, inventory quantities, and estimation rates.</p>
                </div>
                <div>
                    <a href="quotations.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <form action="quotations.php?action=save" method="POST">
                <div class="row">
                    <!-- Left Column: Core Fields & Charges -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Transit & Customer Details</h5>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-medium">Select Customer *</label>
                                        <select name="customer_id" id="customer_select" class="form-select" required>
                                            <option value="">-- Choose Customer --</option>
                                            <?php foreach ($customers as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" data-city="<?php echo htmlspecialchars($c['city']); ?>" data-mobile="<?php echo htmlspecialchars($c['mobile']); ?>">
                                                    <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">From Location *</label>
                                        <input type="text" name="from_city" id="from_city" class="form-control" placeholder="e.g. Koraput" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">To Location *</label>
                                        <input type="text" name="to_city" class="form-control" placeholder="e.g. Phulbani" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">Transit Contact Phone</label>
                                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Mobile Number">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-medium">Quotation Date</label>
                                        <input type="date" name="quotation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Cost Estimation Setup (₹)</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Packing Charge</label>
                                        <input type="number" name="packing_charge" id="packing_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Unpacking Charge</label>
                                        <input type="number" name="unpacking_charge" id="unpacking_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Loading Charge</label>
                                        <input type="number" name="loading_charge" id="loading_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Unloading Charge</label>
                                        <input type="number" name="unloading_charge" id="unloading_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Escort Charge (Freight/Transit)</label>
                                        <input type="number" name="escort_charge" id="escort_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Storage Charge</label>
                                        <input type="number" name="storage_charge" id="storage_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Insurance (Valuation Cover)</label>
                                        <input type="number" name="insurance_charge" id="insurance_charge" class="form-control cost-input calc-trigger" value="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">GST Tax Rate (%)</label>
                                        <input type="number" name="gst_rate" id="gst_rate" step="0.01" class="form-control cost-input calc-trigger mb-2" value="5">
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-outline-secondary preset-gst" type="button" data-val="0">0%</button>
                                            <button class="btn btn-sm btn-outline-secondary preset-gst" type="button" data-val="5">5%</button>
                                            <button class="btn btn-sm btn-outline-secondary preset-gst" type="button" data-val="12">12%</button>
                                            <button class="btn btn-sm btn-outline-secondary preset-gst" type="button" data-val="18">18%</button>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="alert alert-secondary py-3 border-0 bg-light shadow-none">
                                            <div class="row text-center fs-14">
                                                <div class="col-4 border-end">
                                                    <span class="text-muted d-block mb-1">Subtotal</span>
                                                    <strong class="text-dark fs-15" id="calc_subtotal">₹0.00</strong>
                                                </div>
                                                <div class="col-4 border-end">
                                                    <span class="text-muted d-block mb-1">GST Amount</span>
                                                    <strong class="text-indigo fs-15" id="calc_gst">₹0.00</strong>
                                                </div>
                                                <div class="col-4">
                                                    <span class="text-muted d-block mb-1">Estimated Total</span>
                                                    <strong class="text-danger fs-15" id="calc_grand_total">₹0.00</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Predefined Item Checklist (Responsive Qty Taps) -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-1">Household Item Checklist</h5>
                                <p class="text-muted fs-13 mb-3">Tap `+` or `-` to adjust inventory checklist counts.</p>
                                
                                <!-- Search filter for checklist items -->
                                <div class="mb-3">
                                    <input type="text" id="item_search" class="form-control" placeholder="🔍 Search moving items (e.g. Bed, Sofa, TV)...">
                                </div>
                                
                                <div style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                                    <div class="checklist-grid">
                                        <?php foreach ($predefined_items as $item): ?>
                                            <div class="item-row d-flex align-items-center justify-content-between" data-name="<?php echo htmlspecialchars($item); ?>">
                                                <span class="item-name"><?php echo htmlspecialchars($item); ?></span>
                                                <div class="qty-controls">
                                                    <button class="qty-btn btn-minus" type="button" onclick="adjustQty(this, -1)">−</button>
                                                    <input type="text" name="items_qty[<?php echo htmlspecialchars($item); ?>]" class="qty-display qty-input" value="0" readonly>
                                                    <button class="qty-btn btn-plus" type="button" onclick="adjustQty(this, 1)">+</button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <button type="submit" class="btn btn-lg btn-success">Generate & Save Quotation</button>
                    <a href="quotations.php" class="btn btn-lg btn-light">Cancel</a>
                </div>
            </form>

            <script src="assets/js/jquery-3.7.1.min.js"></script>
            <script>
                function adjustQty(btn, delta) {
                    var container = btn.closest('.qty-controls');
                    var input = container.querySelector('.qty-input');
                    var row = btn.closest('.item-row');
                    var val = parseInt(input.value) || 0;
                    val = val + delta;
                    if (val < 0) val = 0;
                    input.value = val;
                    
                    // Toggle active-item class depending on count
                    if (val > 0) {
                        row.classList.add('active-item');
                    } else {
                        row.classList.remove('active-item');
                    }
                }

                $(document).ready(function() {
                    function calculateLiveTotals() {
                        var packing = parseFloat($('#packing_charge').val()) || 0;
                        var unpacking = parseFloat($('#unpacking_charge').val()) || 0;
                        var loading = parseFloat($('#loading_charge').val()) || 0;
                        var unloading = parseFloat($('#unloading_charge').val()) || 0;
                        var escort = parseFloat($('#escort_charge').val()) || 0;
                        var storage = parseFloat($('#storage_charge').val()) || 0;
                        var insurance = parseFloat($('#insurance_charge').val()) || 0;
                        var gstRate = parseFloat($('#gst_rate').val()) || 0;

                        var subtotal = packing + unpacking + loading + unloading + escort + storage + insurance;
                        var gstAmount = (subtotal * gstRate) / 100;
                        var grandTotal = subtotal + gstAmount;

                        $('#calc_subtotal').text('₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        $('#calc_gst').text('₹' + gstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                        $('#calc_grand_total').text('₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                    }

                    $('.calc-trigger').on('input change', function() {
                        calculateLiveTotals();
                    });

                    $('#customer_select').on('change', function() {
                        var selectedOption = $(this).find('option:selected');
                        var city = selectedOption.data('city');
                        var mobile = selectedOption.data('mobile');
                        if (city) {
                            $('#from_city').val(city);
                        }
                        if (mobile) {
                            $('#phone').val(mobile);
                        }
                    });

                    // GST Preset Rate click handler
                    $('.preset-gst').on('click', function() {
                        var val = $(this).data('val');
                        $('#gst_rate').val(val).trigger('change');
                    });

                    // Live search filter for moving items checklist
                    $('#item_search').on('keyup', function() {
                        var query = $(this).val().toLowerCase().trim();
                        $('.item-row').each(function() {
                            var name = $(this).data('name').toLowerCase();
                            if (name.indexOf(query) !== -1) {
                                $(this).attr('style', 'display: flex !important;');
                            } else {
                                $(this).attr('style', 'display: none !important;');
                            }
                        });
                    });

                    calculateLiveTotals();
                });
            </script>

        <?php elseif ($action === 'view' && !empty($id)):
            $q = db_find('quotations', $id);
            $c = db_find('customers', $q['customer_id']);
            
            // Generate dynamic WhatsApp share link
            $clean_phone = preg_replace('/[^0-9]/', '', $c['mobile']);
            if (strlen($clean_phone) === 10) { $clean_phone = '91' . $clean_phone; }
            $share_text = urlencode("🚚 *OM GUPTESWAR TRANSPORTER PACKERS & MOVERS* 🚚\n"
                        . "📋 *SHIFTING PRICE ESTIMATE & QUOTATION*\n\n"
                        . "Dear *" . $c['name'] . "*,\n"
                        . "Thank you for reaching out to us! Please find below the estimated shifting costs for your request:\n\n"
                        . "📝 *Quotation No:* #" . $q['quotation_number'] . "\n"
                        . "📅 *Date:* " . date('d-M-Y', strtotime($q['quotation_date'])) . "\n"
                        . "🛣️ *Route:* " . $q['from_city'] . " ➜ " . $q['to_city'] . "\n\n"
                        . "💰 *Estimated Charges:*\n"
                        . "• Packing Charge: ₹" . number_format($q['packing_charge'], 2) . "\n"
                        . "• Unpacking Charge: ₹" . number_format($q['unpacking_charge'], 2) . "\n"
                        . "• Loading Charge: ₹" . number_format($q['loading_charge'], 2) . "\n"
                        . "• Unloading Charge: ₹" . number_format($q['unloading_charge'], 2) . "\n"
                        . "• Freight / Escort Charge: ₹" . number_format($q['escort_charge'], 2) . "\n"
                        . "• Insurance / Storage / Other Cover: ₹" . number_format(($q['insurance_charge'] + $q['storage_charge']), 2) . "\n"
                        . "• Subtotal: ₹" . number_format($q['subtotal'], 2) . "\n"
                        . "• GST Tax (" . $q['gst_rate'] . "%): ₹" . number_format($q['gst_amount'], 2) . "\n"
                        . "💵 *Estimated Shifting Grand Total: ₹" . number_format($q['grand_total'], 2) . "*\n\n"
                        . "We guarantee a safe, damage-free, and hassle-free packing and transit process.\n\n"
                        . "📞 For booking confirmations or changes, contact us at: 7789052910, 8457952219.\n"
                        . "We look forward to moving with you!");
            $wa_link = "https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . $share_text;
            ?>
            <!-- Top Action Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0">Quotation Preview</h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <a href="invoices.php?action=new&quotation_id=<?php echo $q['id']; ?>" class="btn btn-primary"><i class="ti ti-file-invoice me-2"></i>Convert to Invoice</a>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success"><i class="ti ti-brand-whatsapp me-2"></i>Share on WhatsApp</a>
                    <button onclick="window.print()" class="btn btn-dark"><i class="ti ti-printer me-2"></i>Print Quotation</button>
                    <a href="quotations.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <!-- Beautiful Print Slip -->
            <div class="card shadow-sm border-0 p-4" id="print-slip">
                <div class="card-body">
                    <!-- Top header template metadata -->
                    <div class="row align-items-center border-bottom pb-3 mb-4">
                        <div class="col-sm-2 text-center text-sm-start">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle" style="width: 70px; height: 70px;">
                                <i class="ti ti-truck-delivery fs-36"></i>
                            </div>
                        </div>
                        <div class="col-sm-7 text-center">
                            <h2 class="fw-bold mb-1" style="color: var(--primary);"><?php echo htmlspecialchars($settings['company_name']); ?></h2>
                            <p class="fs-12 text-muted mb-0"><?php echo htmlspecialchars($settings['address']); ?></p>
                            <p class="fs-12 text-muted mb-0">Mob: <?php echo htmlspecialchars($settings['phone']); ?> | Website: <?php echo htmlspecialchars($settings['website']); ?></p>
                        </div>
                        <div class="col-sm-3 text-center text-sm-end">
                            <span class="badge bg-danger p-2 fs-14">QUOTATION</span>
                            <div class="mt-2 fs-12">
                                <strong>GSTIN:</strong> <?php echo htmlspecialchars($settings['gstin']); ?><br>
                                <strong>PAN:</strong> <?php echo htmlspecialchars($settings['pan']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Client & Route metadata -->
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="100">Customer:</td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Mobile:</td>
                                    <td><?php echo htmlspecialchars($q['phone'] ?: $c['mobile']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">From Route:</td>
                                    <td><span class="badge bg-light text-dark fs-13"><?php echo htmlspecialchars($q['from_city']); ?></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <table class="table table-sm table-borderless float-sm-end">
                                <tr>
                                    <td class="text-muted text-sm-end" width="120">Quotation No:</td>
                                    <td class="fw-bold text-sm-start" width="150">#<?php echo htmlspecialchars($q['quotation_number']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted text-sm-end">Date:</td>
                                    <td class="text-sm-start"><?php echo date('d/m/Y', strtotime($q['quotation_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted text-sm-end">To Destination:</td>
                                    <td class="text-sm-start"><span class="badge bg-light text-dark fs-13"><?php echo htmlspecialchars($q['to_city']); ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Inventory Checklist Grid (2 Columns) -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="border-bottom pb-2 mb-2 text-dark fw-bold">Inventory Details (Checked Items List)</h6>
                        <div class="row">
                            <?php 
                            $chunks = array_chunk($q['items'], ceil(count($q['items']) / 2 ?: 1));
                            foreach ($chunks as $chunk):
                            ?>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <thead>
                                        <tr class="border-bottom fs-12 text-muted">
                                            <th>Particulars</th>
                                            <th class="text-end" width="60">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($chunk as $item): ?>
                                            <tr class="fs-13 border-bottom-dashed">
                                                <td><?php echo htmlspecialchars($item['particulars']); ?></td>
                                                <td class="text-end fw-bold"><?php echo $item['qty']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Costs Estimates breakdown Table -->
                    <div class="row">
                        <div class="col-md-7">
                            <div class="p-3 border rounded h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-2">Terms & Conditions:</h6>
                                <p class="fs-11 text-muted" style="white-space: pre-line; line-height: 1.5;"><?php echo htmlspecialchars($settings['terms']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <table class="table table-bordered table-sm text-nowrap">
                                <thead>
                                    <tr class="table-light fs-13">
                                        <th>Charges Breakdown</th>
                                        <th class="text-end" width="120">Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-13">
                                    <tr>
                                        <td>Packing Charges</td>
                                        <td class="text-end">₹<?php echo number_format($q['packing_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Unpacking Charges</td>
                                        <td class="text-end">₹<?php echo number_format($q['unpacking_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Loading Charges</td>
                                        <td class="text-end">₹<?php echo number_format($q['loading_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Unloading Charges</td>
                                        <td class="text-end">₹<?php echo number_format($q['unloading_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Freight Transit / Escort</td>
                                        <td class="text-end">₹<?php echo number_format($q['escort_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Storage Charges</td>
                                        <td class="text-end">₹<?php echo number_format($q['storage_charge'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Valuation Cover Insurance</td>
                                        <td class="text-end">₹<?php echo number_format($q['insurance_charge'], 2); ?></td>
                                    </tr>
                                    <tr class="table-light border-top-double">
                                        <td class="fw-bold text-dark">Subtotal</td>
                                        <td class="text-end fw-bold text-dark">₹<?php echo number_format($q['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>GST Taxes (<?php echo $q['gst_rate']; ?>%)</td>
                                        <td class="text-end text-muted">₹<?php echo number_format($q['gst_amount'], 2); ?></td>
                                    </tr>
                                    <tr class="table-dark text-white font-weight-bold">
                                        <td class="fw-bold text-white fs-15">Estimated Total</td>
                                        <td class="text-end fw-bold text-white fs-15">₹<?php echo number_format($q['grand_total'], 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Signatures footer -->
                    <div class="row mt-5 pt-4 text-center">
                        <div class="col-6">
                            <p class="mb-5 text-muted">Customer Signature</p>
                            <div class="border-top w-50 mx-auto"></div>
                        </div>
                        <div class="col-6">
                            <p class="mb-5 text-muted">For <?php echo htmlspecialchars($settings['company_name']); ?></p>
                            <div class="border-top w-50 mx-auto"></div>
                            <span class="fs-12 text-muted">Proprietor / Supervisor</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
