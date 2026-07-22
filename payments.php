<?php
$page_title = 'Payments Ledger';
$active_menu = 'payments';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $invoice_id = $_POST['invoice_id'];
        $amount = (float)$_POST['amount'];
        $payment_date = trim($_POST['payment_date']) ?: date('Y-m-d');
        $mode = $_POST['mode'];
        $ref_number = trim($_POST['ref_number']);
        $remarks = trim($_POST['remarks']);

        $invoice = db_find('invoices', $invoice_id);
        if ($invoice) {
            if ($amount <= 0) {
                $error = 'Payment amount must be greater than zero!';
                $action = 'new';
            } elseif ($amount > $invoice['outstanding_balance']) {
                $error = 'Payment amount (₹' . number_format($amount, 2) . ') cannot exceed the outstanding balance (₹' . number_format($invoice['outstanding_balance'], 2) . ')!';
                $action = 'new';
            } else {
                // Save payment transaction
                $payment_data = [
                    'invoice_id' => $invoice_id,
                    'amount' => $amount,
                    'payment_date' => $payment_date,
                    'payment_mode' => $mode,
                    'reference_number' => $ref_number,
                    'remarks' => $remarks
                ];
                $payment_id = db_insert('payments', $payment_data);

                // Update invoice calculations
                $new_paid = (float)$invoice['amount_paid'] + $amount;
                $new_outstanding = (float)$invoice['grand_total'] - $new_paid;
                
                $new_status = 'Partially Paid';
                if ($new_outstanding <= 0.01) {
                    $new_status = 'Paid';
                }

                db_update('invoices', $invoice_id, [
                    'amount_paid' => $new_paid,
                    'outstanding_balance' => $new_outstanding,
                    'status' => $new_status
                ]);

                log_audit('Payment Received', 'Recorded payment of ₹' . $amount . ' for Invoice ' . $invoice['invoice_number'] . ' via ' . $mode);
                $success = 'Payment logged and invoice updated successfully!';
                
                // Redirect to print money receipt
                echo "<script>window.location.href='payments.php?action=receipt&id=" . $payment_id . "';</script>";
                exit;
            }
        } else {
            $error = 'Invalid Invoice reference!';
            $action = 'list';
        }
    }
}

$payments = db_get_table('payments');
$invoices = db_get_table('invoices');
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

        <!-- ACTION: LIST PAYMENTS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Payments Ledger</h4>
                    <p class="text-muted fs-14 mb-0">List payments recorded, track collections, and print money receipts.</p>
                </div>
                <div>
                    <a href="payments.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-credit-card me-2"></i>Record Payment
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice Ref</th>
                                    <th>Customer</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Mode</th>
                                    <th>Ref / Transaction ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): 
                                    $inv = db_find('invoices', $p['invoice_id']);
                                    $c = $inv ? db_find('customers', $inv['customer_id']) : null;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                                        <td><a href="invoices.php?action=view&id=<?php echo $p['invoice_id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($inv ? $inv['invoice_number'] : 'Deleted'); ?></a></td>
                                        <td><?php echo htmlspecialchars($c ? $c['name'] : 'Unknown'); ?></td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($p['amount'], 2); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($p['payment_mode']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($p['reference_number'] ?: 'N/A'); ?></code></td>
                                        <td>
                                            <a href="payments.php?action=receipt&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" title="Money Receipt"><i class="ti ti-printer"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: NEW PAYMENT -->
        <?php elseif ($action === 'new'):
            $invoice_id_param = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : '';
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Record Payment Received</h4>
                    <p class="text-muted fs-14 mb-0">Log collections against outstanding balances. Outstanding amount is updated instantly.</p>
                </div>
                <div>
                    <a href="payments.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="payments.php?action=save" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Select Invoice *</label>
                                <select name="invoice_id" id="invoice_select" class="form-select" required onchange="updateMaxLimit()">
                                    <option value="">-- Select Invoice --</option>
                                    <?php foreach ($invoices as $inv): 
                                        if ($inv['outstanding_balance'] <= 0) continue; // Skip fully paid
                                        $c = db_find('customers', $inv['customer_id']);
                                        ?>
                                        <option value="<?php echo $inv['id']; ?>" data-balance="<?php echo $inv['outstanding_balance']; ?>" <?php echo $invoice_id_param === $inv['id'] ? 'selected' : ''; ?>>
                                            #<?php echo htmlspecialchars($inv['invoice_number']); ?> - <?php echo htmlspecialchars($c ? $c['name'] : 'Unknown'); ?> (Due: ₹<?php echo number_format($inv['outstanding_balance'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Amount Received (₹) *</label>
                                <input type="number" name="amount" id="pay_amount" step="0.01" class="form-control" placeholder="0.00" required>
                                <span class="fs-12 text-muted mt-1 d-block" id="balance_label"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Payment Mode *</label>
                                <select name="mode" class="form-select" required>
                                    <option value="Cash" selected>Cash</option>
                                    <option value="UPI">UPI (GooglePay / PhonePe / Paytm)</option>
                                    <option value="Bank Transfer">Bank Transfer (NEFT/IMPS/RTGS)</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Card">Card Payment</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-medium">Reference Number (Txn ID, Cheque No, Bank Ref)</label>
                                <input type="text" name="ref_number" class="form-control" placeholder="Transaction Ref ID / Cheque Number">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-medium">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-lg btn-success">Record Payment & Money Receipt</button>
                            <a href="payments.php" class="btn btn-lg btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function updateMaxLimit() {
                    var select = document.getElementById('invoice_select');
                    var selectedOption = select.options[select.selectedIndex];
                    var balance = selectedOption.getAttribute('data-balance');
                    
                    var label = document.getElementById('balance_label');
                    var amountInput = document.getElementById('pay_amount');

                    if (balance) {
                        label.innerHTML = "Maximum allowed: ₹" + parseFloat(balance).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        amountInput.max = balance;
                    } else {
                        label.innerHTML = "";
                        amountInput.removeAttribute('max');
                    }
                }
                document.addEventListener("DOMContentLoaded", function() {
                    updateMaxLimit();
                });
            </script>

        <!-- ACTION: VIEW MONEY RECEIPT (PRINT-FRIENDLY SLIP) -->
        <?php elseif ($action === 'receipt' && !empty($id)):
            $p = db_find('payments', $id);
            $inv = db_find('invoices', $p['invoice_id']);
            $c = db_find('customers', $inv['customer_id']);
            ?>
            <!-- Actions -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                <div>
                    <h4 class="mb-0">Money Receipt Preview</h4>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <button onclick="window.print()" class="btn btn-dark"><i class="ti ti-printer me-2"></i>Print Receipt</button>
                    <a href="payments.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <!-- Receipt Document -->
            <div class="card shadow-sm border-0 p-4" id="print-receipt" style="max-width: 700px; margin: 0 auto;">
                <div class="card-body border p-4 rounded bg-white">
                    <!-- Receipt Header -->
                    <div class="row align-items-center border-bottom pb-2 mb-3">
                        <div class="col-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle" style="width: 50px; height: 50px;">
                                <i class="ti ti-truck-delivery fs-24"></i>
                            </div>
                        </div>
                        <div class="col-9 text-end">
                            <h4 class="fw-bold mb-0 text-danger"><?php echo htmlspecialchars($settings['company_name']); ?></h4>
                            <span class="badge bg-secondary py-1 px-2 fs-12 mt-1">MONEY RECEIPT</span>
                            <p class="fs-10 text-muted mb-0 mt-1"><?php echo htmlspecialchars($settings['address']); ?></p>
                        </div>
                    </div>

                    <!-- Receipt Details -->
                    <div class="row mt-3">
                        <div class="col-6 mb-2">
                            <span class="text-muted fs-12">Receipt Number:</span>
                            <strong class="d-block">REC-<?php echo strtoupper(substr($p['id'], -6)); ?></strong>
                        </div>
                        <div class="col-6 text-end mb-2">
                            <span class="text-muted fs-12">Date:</span>
                            <strong class="d-block"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></strong>
                        </div>
                        <div class="col-12 mb-3 mt-2">
                            <div class="p-3 bg-light rounded">
                                <table class="table table-sm table-borderless mb-0 fs-13">
                                    <tr>
                                        <td class="text-muted" width="150">Received with thanks from:</td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">A sum of Rupees:</td>
                                        <td class="fw-medium text-dark text-capitalize">
                                            <?php
                                            echo amount_in_words($p['amount']);
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Towards Invoice No:</td>
                                        <td class="fw-bold text-primary">#<?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Payment Mode:</td>
                                        <td><span class="badge bg-white text-dark border"><?php echo htmlspecialchars($p['payment_mode']); ?></span></td>
                                    </tr>
                                    <?php if ($p['reference_number']): ?>
                                        <tr>
                                            <td class="text-muted">Reference / UPI ID:</td>
                                            <td><code><?php echo htmlspecialchars($p['reference_number']); ?></code></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($p['remarks']): ?>
                                        <tr>
                                            <td class="text-muted">Remarks:</td>
                                            <td><span class="text-muted fs-12"><?php echo htmlspecialchars($p['remarks']); ?></span></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Box & Signatures -->
                    <div class="row align-items-center mt-3 pt-3 border-top">
                        <div class="col-6">
                            <div class="p-3 bg-dark text-white text-center rounded d-inline-block" style="min-width: 180px;">
                                <span class="fs-11 text-white-50 d-block">AMOUNT RECEIVED</span>
                                <h4 class="text-white mb-0 fw-bold">₹<?php echo number_format($p['amount'], 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-4 text-muted fs-11">For <?php echo htmlspecialchars($settings['company_name']); ?></p>
                            <div class="border-top w-75 ms-auto"></div>
                            <span class="fs-11 text-muted">Authorized Representative</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
