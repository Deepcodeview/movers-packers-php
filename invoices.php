<?php
$page_title = 'GST Invoices';
$active_menu = 'invoices';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_id = $_POST['customer_id'];
        $from_city = trim($_POST['from_city']);
        $to_city = trim($_POST['to_city']);
        $invoice_date = trim($_POST['invoice_date']) ?: date('Y-m-d');
        $gst_type = $_POST['gst_type']; // 'freight_only' or 'full_amount'
        $gst_rate = (float)$_POST['gst_rate'];
        $vehicle_number = trim($_POST['vehicle_number']);
        $driver_name = trim($_POST['driver_name']);

        // Charges
        $freight = (float)$_POST['freight_charge'];
        $packing = (float)$_POST['packing_charge'];
        $loading = (float)$_POST['loading_charge'];
        $unloading = (float)$_POST['unloading_charge'];
        $unpacking = (float)$_POST['unpacking_charge'];
        $escort = (float)$_POST['escort_charge'];

        $subtotal = $freight + $packing + $loading + $unloading + $unpacking + $escort;

        // GST Calculation
        $taxable_base = ($gst_type === 'freight_only') ? $freight : $subtotal;
        $gst_amount = ($taxable_base * $gst_rate) / 100;
        $grand_total = $subtotal + $gst_amount;

        // Fetch customer details to determine GST State mapping
        $c = db_find('customers', $customer_id);
        $is_intrastate = true; // Default to CGST/SGST (intra-state)
        if ($c) {
            // If customer has a GSTIN, check the state code (first 2 digits)
            // Odisha GSTIN prefix is '21'. If it starts with anything else, it's inter-state
            if (!empty($c['gstin'])) {
                $state_code = substr(trim($c['gstin']), 0, 2);
                if ($state_code !== '21') {
                    $is_intrastate = false;
                }
            } else {
                // Check state name (case insensitive)
                $cust_state = strtolower(trim($c['state']));
                if ($cust_state !== '' && $cust_state !== 'odisha' && $cust_state !== 'orissa') {
                    $is_intrastate = false;
                }
            }
        }

        $cgst_amount = 0;
        $sgst_amount = 0;
        $igst_amount = 0;

        if ($is_intrastate) {
            $cgst_amount = $gst_amount / 2;
            $sgst_amount = $gst_amount / 2;
        } else {
            $igst_amount = $gst_amount;
        }

        // Auto-generate invoice number sequentially in professional OG/YY-YY/XXXX format
        $all_invoices = db_get_table('invoices');
        $max_inv_seq = 0;
        foreach ($all_invoices as $inv_item) {
            $parts = explode('/', $inv_item['invoice_number']);
            $seq = (int)end($parts);
            if ($seq > $max_inv_seq) {
                $max_inv_seq = $seq;
            }
        }
        $next_inv_seq = $max_inv_seq + 1;
        $invoice_number = 'OG/' . date('y') . '-' . date('y', strtotime('+1 year')) . '/' . str_pad($next_inv_seq, 4, '0', STR_PAD_LEFT);

        $invoice_data = [
            'invoice_number' => $invoice_number,
            'customer_id' => $customer_id,
            'from_city' => $from_city,
            'to_city' => $to_city,
            'invoice_date' => $invoice_date,
            'vehicle_number' => $vehicle_number,
            'driver_name' => $driver_name,
            'freight_charge' => $freight,
            'packing_charge' => $packing,
            'loading_charge' => $loading,
            'unloading_charge' => $unloading,
            'unpacking_charge' => $unpacking,
            'escort_charge' => $escort,
            'subtotal' => $subtotal,
            'gst_type' => $gst_type,
            'gst_rate' => $gst_rate,
            'gst_amount' => $gst_amount,
            'cgst_amount' => $cgst_amount,
            'sgst_amount' => $sgst_amount,
            'igst_amount' => $igst_amount,
            'grand_total' => $grand_total,
            'amount_paid' => 0.0,
            'outstanding_balance' => $grand_total,
            'status' => 'Unpaid' // Default status
        ];

        db_insert('invoices', $invoice_data);
        log_audit('Invoice Created', 'Generated GST Tax Invoice: ' . $invoice_number . ' for customer ' . ($c ? $c['name'] : 'Unknown'));
        $success = 'Invoice generated successfully!';
        $action = 'list';
    }
} elseif ($action === 'update_status' && !empty($id)) {
    $status = $_GET['status'];
    $inv = db_find('invoices', $id);
    if ($inv) {
        db_update('invoices', $id, ['status' => $status]);
        log_audit('Invoice Status Updated', 'Invoice ' . $inv['invoice_number'] . ' status changed to ' . $status);
        $success = 'Invoice status updated successfully!';
    }
    $action = 'list';
}

$invoices = db_get_table('invoices');
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

        <!-- ACTION: LIST INVOICES -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">GST Tax Invoices</h4>
                    <p class="text-muted fs-14 mb-0">Track bookings, payments, and print GST compliant tax invoices.</p>
                </div>
                <div>
                    <a href="invoices.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-file-plus me-2"></i>New GST Invoice
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice No.</th>
                                    <th>Customer</th>
                                    <th>Invoice Date</th>
                                    <th>Route</th>
                                    <th>Subtotal</th>
                                    <th>GST Amount</th>
                                    <th>Total Value</th>
                                    <th>Balance Due</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                        <td>
                                            <?php 
                                            $c = db_find('customers', $inv['customer_id']);
                                            echo htmlspecialchars($c ? $c['name'] : 'Unknown');
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($inv['invoice_date'])); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?></span></td>
                                        <td>₹<?php echo number_format($inv['subtotal'], 2); ?></td>
                                        <td>₹<?php echo number_format($inv['gst_amount'], 2); ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                        <td class="text-danger fw-semibold">₹<?php echo number_format($inv['outstanding_balance'], 2); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <?php
                                                $status_class = 'btn-outline-secondary';
                                                if ($inv['status'] === 'Paid') $status_class = 'btn-success';
                                                elseif ($inv['status'] === 'Partially Paid') $status_class = 'btn-warning';
                                                elseif ($inv['status'] === 'Unpaid') $status_class = 'btn-danger';
                                                elseif ($inv['status'] === 'Cancelled') $status_class = 'btn-dark';
                                                ?>
                                                <button class="btn btn-sm <?php echo $status_class; ?> dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <?php echo $inv['status']; ?>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="invoices.php?action=update_status&id=<?php echo $inv['id']; ?>&status=Paid">Paid</a></li>
                                                    <li><a class="dropdown-item" href="invoices.php?action=update_status&id=<?php echo $inv['id']; ?>&status=Partially Paid">Partially Paid</a></li>
                                                    <li><a class="dropdown-item" href="invoices.php?action=update_status&id=<?php echo $inv['id']; ?>&status=Unpaid">Unpaid</a></li>
                                                    <li><a class="dropdown-item" href="invoices.php?action=update_status&id=<?php echo $inv['id']; ?>&status=Cancelled">Cancelled</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" title="View / Print"><i class="ti ti-printer"></i></a>
                                            <a href="payments.php?action=new&invoice_id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-icon btn-outline-success" data-bs-toggle="tooltip" title="Record Payment"><i class="ti ti-credit-card"></i></a>
                                            <a href="lorry_receipts.php?action=new&invoice_id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-icon btn-outline-warning" data-bs-toggle="tooltip" title="Generate Bilty / LR"><i class="ti ti-truck"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: NEW INVOICE -->
        <?php elseif ($action === 'new'):
            $q_id = isset($_GET['quotation_id']) ? $_GET['quotation_id'] : '';
            $q_data = [];
            if (!empty($q_id)) {
                $q_data = db_find('quotations', $q_id);
            }
            $quotations = db_get_table('quotations');
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Create GST Tax Invoice</h4>
                    <p class="text-muted fs-14 mb-0">Fill in the transit charges. Taxes will be computed automatically according to state rules.</p>
                </div>
                <div>
                    <a href="invoices.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="invoices.php?action=save" method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-bold text-primary">Import Details from Existing Proposal / Quotation</label>
                                <select id="quotation_select" class="form-select border-primary">
                                    <option value="">-- Select a Proposal / Quotation to Auto-Fill Form --</option>
                                    <?php foreach ($quotations as $q): 
                                        $cust = db_find('customers', $q['customer_id']);
                                        $cust_name = $cust ? $cust['name'] : 'Unknown';
                                        ?>
                                        <option value="<?php echo $q['id']; ?>"
                                                data-customer-id="<?php echo htmlspecialchars($q['customer_id']); ?>"
                                                data-from-city="<?php echo htmlspecialchars($q['from_city']); ?>"
                                                data-to-city="<?php echo htmlspecialchars($q['to_city']); ?>"
                                                
                                                data-packing="<?php echo htmlspecialchars($q['packing_charge']); ?>"
                                                data-unpacking="<?php echo htmlspecialchars($q['unpacking_charge']); ?>"
                                                data-loading="<?php echo htmlspecialchars($q['loading_charge']); ?>"
                                                data-unloading="<?php echo htmlspecialchars($q['unloading_charge']); ?>"
                                                data-escort="<?php echo htmlspecialchars($q['escort_charge']); ?>"
                                                data-gst-rate="<?php echo htmlspecialchars($q['gst_rate']); ?>"
                                                <?php echo ($q_id === $q['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($q['quotation_number']); ?> - <?php echo htmlspecialchars($cust_name); ?> (₹<?php echo number_format($q['grand_total'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="fs-12 text-muted">Choosing a quotation will automatically populate customer, route, and charges breakdown.</span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Customer *</label>
                                <select name="customer_id" id="customer_select" class="form-select" required>
                                    <option value="">-- Choose Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" data-city="<?php echo htmlspecialchars($c['city']); ?>" data-state="<?php echo htmlspecialchars($c['state']); ?>" <?php echo (!empty($q_data) && $q_data['customer_id'] === $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Invoice Date</label>
                                <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">From City *</label>
                                <input type="text" name="from_city" id="from_city" class="form-control" value="<?php echo htmlspecialchars(isset($q_data['from_city']) ? $q_data['from_city'] : ''); ?>" placeholder="e.g. Semiliguda" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">To City *</label>
                                <input type="text" name="to_city" id="to_city" class="form-control" value="<?php echo htmlspecialchars(isset($q_data['to_city']) ? $q_data['to_city'] : ''); ?>" placeholder="e.g. Malkangiri" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Vehicle / Truck Number</label>
                                <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. OD-10-A-1234">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Driver Name</label>
                                <input type="text" name="driver_name" class="form-control" placeholder="Driver Name">
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3 text-dark">Charges Breakdown</h5>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Freight Charge *</label>
                                <input type="number" name="freight_charge" id="freight_charge" class="form-control calc-trigger" value="<?php echo htmlspecialchars(isset($q_data['escort_charge']) ? $q_data['escort_charge'] : '0'); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Packing Charge</label>
                                <input type="number" name="packing_charge" id="packing_charge" class="form-control calc-trigger" value="<?php echo htmlspecialchars(isset($q_data['packing_charge']) ? $q_data['packing_charge'] : '0'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Loading Charge</label>
                                <input type="number" name="loading_charge" id="loading_charge" class="form-control calc-trigger" value="<?php echo htmlspecialchars(isset($q_data['loading_charge']) ? $q_data['loading_charge'] : '0'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unloading Charge</label>
                                <input type="number" name="unloading_charge" id="unloading_charge" class="form-control calc-trigger" value="<?php echo htmlspecialchars(isset($q_data['unloading_charge']) ? $q_data['unloading_charge'] : '0'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unpacking Charge</label>
                                <input type="number" name="unpacking_charge" id="unpacking_charge" class="form-control calc-trigger" value="<?php echo htmlspecialchars(isset($q_data['unpacking_charge']) ? $q_data['unpacking_charge'] : '0'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Other Escort Charges</label>
                                <input type="number" name="escort_charge" id="escort_charge" class="form-control calc-trigger" value="0">
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3 text-dark">GST Calculation Options</h5>

                            <input type="hidden" name="gst_type" id="gst_type" value="full_amount" class="calc-trigger">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">GST Rate (%)</label>
                                <input type="number" name="gst_rate" id="gst_rate" step="0.01" class="form-control calc-trigger mb-2" value="<?php echo htmlspecialchars(isset($q_data['gst_rate']) ? $q_data['gst_rate'] : '5'); ?>">
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
                                            <strong class="text-dark fs-16" id="calc_subtotal">₹0.00</strong>
                                        </div>
                                        <div class="col-4 border-end">
                                            <span class="text-muted d-block mb-1">GST Tax Amount</span>
                                            <strong class="text-indigo fs-16" id="calc_gst">₹0.00</strong>
                                        </div>
                                        <div class="col-4">
                                            <span class="text-muted d-block mb-1">Estimated Grand Total</span>
                                            <strong class="text-danger fs-16" id="calc_grand_total">₹0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-lg btn-danger">Generate GST Tax Invoice</button>
                            <a href="invoices.php" class="btn btn-lg btn-light">Cancel</a>
                        </div>
                    </form>

                    <!-- Realtime Calculator jQuery Script -->
                    <script src="assets/js/jquery-3.7.1.min.js"></script>
                    <script>
                        $(document).ready(function() {
                            function calculateLiveTotals() {
                                var freight = parseFloat($('#freight_charge').val()) || 0;
                                var packing = parseFloat($('#packing_charge').val()) || 0;
                                var loading = parseFloat($('#loading_charge').val()) || 0;
                                var unloading = parseFloat($('#unloading_charge').val()) || 0;
                                var unpacking = parseFloat($('#unpacking_charge').val()) || 0;
                                var escort = parseFloat($('#escort_charge').val()) || 0;
                                
                                var gstType = $('#gst_type').val();
                                var gstRate = parseFloat($('#gst_rate').val()) || 0;

                                var subtotal = freight + packing + loading + unloading + unpacking + escort;
                                var taxableBase = (gstType === 'freight_only') ? freight : subtotal;
                                var gstAmount = (taxableBase * gstRate) / 100;
                                var grandTotal = subtotal + gstAmount;

                                $('#calc_subtotal').text('₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                                $('#calc_gst').text('₹' + gstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                                $('#calc_grand_total').text('₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                            }

                            // Trigger calculations on typing or change
                            $('.calc-trigger').on('input change', function() {
                                calculateLiveTotals();
                            });

                            // Import details from quotation select
                            $('#quotation_select').on('change', function() {
                                var selectedOption = $(this).find('option:selected');
                                if (!selectedOption.val()) return;

                                var customerId = selectedOption.data('customer-id');
                                var fromCity = selectedOption.data('from-city');
                                var toCity = selectedOption.data('to-city');
                                var packing = selectedOption.data('packing') || 0;
                                var unpacking = selectedOption.data('unpacking') || 0;
                                var loading = selectedOption.data('loading') || 0;
                                var unloading = selectedOption.data('unloading') || 0;
                                var escort = selectedOption.data('escort') || 0; // freight
                                var gstRate = selectedOption.data('gst-rate') || 5;

                                $('#customer_select').val(customerId);
                                $('#from_city').val(fromCity);
                                $('#to_city').val(toCity);
                                $('#freight_charge').val(escort);
                                $('#packing_charge').val(packing);
                                $('#loading_charge').val(loading);
                                $('#unloading_charge').val(unloading);
                                $('#unpacking_charge').val(unpacking);
                                $('#gst_rate').val(gstRate);

                                calculateLiveTotals();
                            });

                            // Auto-populate From City on customer select
                            $('#customer_select').on('change', function() {
                                var selectedOption = $(this).find('option:selected');
                                var city = selectedOption.data('city');
                                if (city && !$('#from_city').val()) { // Only auto-fill if empty to avoid overwriting quotation import
                                    $('#from_city').val(city);
                                }
                            });

                            // GST Preset Rate click handler
                            $(document).on('click', '.preset-gst', function() {
                                var val = $(this).data('val');
                                $('#gst_rate').val(val).trigger('change');
                                calculateLiveTotals();
                            });

                            // If a quotation is pre-selected on page load, trigger importing
                            if ($('#quotation_select').val()) {
                                $('#quotation_select').trigger('change');
                            }

                            // Init calculations
                            calculateLiveTotals();
                        });
                    </script>
                </div>
            </div>

        <?php elseif ($action === 'view' && !empty($id)):
            $inv = db_find('invoices', $id);
            $c = db_find('customers', $inv['customer_id']);
            
            // Generate dynamic WhatsApp share link
            $clean_phone = preg_replace('/[^0-9]/', '', $c['mobile']);
            if (strlen($clean_phone) === 10) { $clean_phone = '91' . $clean_phone; }
            $share_text = urlencode("🚚 *OM GUPTESWAR TRANSPORTER PACKERS & MOVERS* 🚚\n"
                        . "📄 *TAX INVOICE DETAILS*\n\n"
                        . "Dear *" . $c['name'] . "*,\n"
                        . "Thank you for shifting with us! Here are the details of your generated Tax Invoice:\n\n"
                        . "📝 *Invoice No:* #" . $inv['invoice_number'] . "\n"
                        . "📅 *Date:* " . date('d-M-Y', strtotime($inv['invoice_date'])) . "\n"
                        . "🛣️ *Route:* " . $inv['from_city'] . " ➜ " . $inv['to_city'] . "\n"
                        . "🚛 *Vehicle No:* " . ($inv['vehicle_number'] ?: 'N/A') . "\n\n"
                        . "💰 *Billing Summary:*\n"
                        . "• Subtotal: ₹" . number_format($inv['subtotal'], 2) . "\n"
                        . "• GST Tax Amount: ₹" . number_format($inv['gst_amount'], 2) . " (" . $inv['gst_rate'] . "%)\n"
                        . "• *Grand Total:* ₹" . number_format($inv['grand_total'], 2) . "\n"
                        . "• Amount Paid: ₹" . number_format($inv['amount_paid'], 2) . "\n"
                        . "• *Outstanding Balance Due:* ₹" . number_format($inv['outstanding_balance'], 2) . "\n\n"
                        . "📞 For queries, contact us at 7789052910 / 8457952219.\n"
                        . "We look forward to serving you again!");
            $wa_link = "https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . $share_text;
            ?>
            <!-- Actions -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0">Tax Invoice Slip</h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <?php if ($inv['outstanding_balance'] > 0): ?>
                        <a href="payments.php?action=new&invoice_id=<?php echo $inv['id']; ?>" class="btn btn-primary"><i class="ti ti-credit-card me-2"></i>Record Payment</a>
                    <?php endif; ?>
                    <a href="lorry_receipts.php?action=new&invoice_id=<?php echo $inv['id']; ?>" class="btn btn-warning text-dark fw-medium"><i class="ti ti-truck me-2"></i>Generate Bilty (LR)</a>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-success"><i class="ti ti-brand-whatsapp me-2"></i>Share on WhatsApp</a>
                    <button onclick="window.print()" class="btn btn-dark"><i class="ti ti-printer me-2"></i>Print Invoice</button>
                    <a href="invoices.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <!-- Invoice Document -->
            <div class="card shadow-sm border-0 p-4" id="print-invoice">
                <div class="card-body">
                    <!-- Title Header -->
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
                            <span class="badge bg-success p-2 fs-14">TAX INVOICE</span>
                            <div class="mt-2 fs-12">
                                <strong>GSTIN:</strong> <?php echo htmlspecialchars($settings['gstin']); ?><br>
                                <strong>PAN:</strong> <?php echo htmlspecialchars($settings['pan']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice metadata -->
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="120">Consignor Name:</td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">GSTIN:</td>
                                    <td><code><?php echo htmlspecialchars($c['gstin'] ?: 'N/A'); ?></code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Route:</td>
                                    <td><span class="badge bg-light text-dark fs-12"><?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Address:</td>
                                    <td class="fs-13 text-muted"><?php echo htmlspecialchars($c['address']); ?>, <?php echo htmlspecialchars($c['city']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <table class="table table-sm table-borderless float-sm-end">
                                <tr>
                                    <td class="text-muted text-sm-end" width="120">Bill No:</td>
                                    <td class="fw-bold text-sm-start" width="150">#<?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted text-sm-end">Date:</td>
                                    <td class="text-sm-start"><?php echo date('d/m/Y', strtotime($inv['invoice_date'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted text-sm-end">Truck Number:</td>
                                    <td class="text-sm-start fw-bold text-success"><?php echo htmlspecialchars($inv['vehicle_number'] ?: 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted text-sm-end">Driver:</td>
                                    <td class="text-sm-start"><?php echo htmlspecialchars($inv['driver_name'] ?: 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Items & Charges Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Description of Services (SAC: 9965 GTA)</th>
                                    <th class="text-end" width="200">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Freight Charges / Escort Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['freight_charge'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Packing Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['packing_charge'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Loading Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['loading_charge'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Unloading Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['unloading_charge'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Unpacking Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['unpacking_charge'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Other Transit Charges</td>
                                    <td class="text-end">₹<?php echo number_format($inv['escort_charge'], 2); ?></td>
                                </tr>
                                <tr class="border-top-double">
                                    <td class="text-end fw-bold text-dark">Subtotal</td>
                                    <td class="text-end fw-bold text-dark">₹<?php echo number_format($inv['subtotal'], 2); ?></td>
                                </tr>
                                <?php if ($inv['cgst_amount'] > 0 || $inv['sgst_amount'] > 0): ?>
                                    <tr>
                                        <td class="text-end text-muted">CGST (<?php echo $inv['gst_rate']/2; ?>%) <?php echo $inv['gst_type'] === 'freight_only' ? '(On Freight Only)' : ''; ?></td>
                                        <td class="text-end text-muted">₹<?php echo number_format($inv['cgst_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-end text-muted">SGST (<?php echo $inv['gst_rate']/2; ?>%) <?php echo $inv['gst_type'] === 'freight_only' ? '(On Freight Only)' : ''; ?></td>
                                        <td class="text-end text-muted">₹<?php echo number_format($inv['sgst_amount'], 2); ?></td>
                                    </tr>
                                <?php elseif ($inv['igst_amount'] > 0): ?>
                                    <tr>
                                        <td class="text-end text-muted">IGST (<?php echo $inv['gst_rate']; ?>%) <?php echo $inv['gst_type'] === 'freight_only' ? '(On Freight Only)' : ''; ?></td>
                                        <td class="text-end text-muted">₹<?php echo number_format($inv['igst_amount'], 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-dark text-white font-weight-bold">
                                    <td class="text-end fw-bold text-white fs-15">Grand Total Value</td>
                                    <td class="text-end fw-bold text-white fs-15">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                </tr>
                                <tr class="fs-13">
                                    <td class="text-end text-success">Total Amount Paid</td>
                                    <td class="text-end text-success fw-bold">₹<?php echo number_format($inv['amount_paid'], 2); ?></td>
                                </tr>
                                <tr class="fs-14 table-light">
                                    <td class="text-end text-danger fw-bold">Outstanding Balance Due</td>
                                    <td class="text-end text-danger fw-bold">₹<?php echo number_format($inv['outstanding_balance'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-light rounded border mb-4 fs-13">
                        <span class="text-muted">Total Amount in Words:</span>
                        <strong class="text-dark ms-1"><?php echo amount_in_words($inv['grand_total']); ?></strong>
                    </div>

                    <!-- Bottom Terms & Bank Info -->
                    <div class="row mt-4">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-2">Our Bank Information:</h6>
                                <p class="fs-12 text-muted" style="white-space: pre-line; line-height: 1.5;"><?php echo htmlspecialchars($settings['bank_details']); ?></p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded h-100 bg-light text-center">
                                <h6 class="fw-bold text-dark mb-2">Payment Status</h6>
                                <span class="badge bg-<?php echo $inv['status'] === 'Paid' ? 'success' : 'danger'; ?> fs-14 p-2 mb-2"><?php echo htmlspecialchars($inv['status']); ?></span>
                                <p class="fs-11 text-muted mb-0">Automatic outstanding adjustment upon adding payment transactions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Signatures -->
                    <div class="row mt-5 pt-4 text-center">
                        <div class="col-6">
                            <p class="mb-5 text-muted">Customer / Consignor Signature</p>
                            <div class="border-top w-50 mx-auto"></div>
                        </div>
                        <div class="col-6">
                            <p class="mb-5 text-muted">For <?php echo htmlspecialchars($settings['company_name']); ?></p>
                            <div class="border-top w-50 mx-auto"></div>
                            <span class="fs-12 text-muted">Authorized Signatory</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
