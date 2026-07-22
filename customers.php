<?php
$page_title = 'Customer Master';
$active_menu = 'customers';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

// Handle actions
if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $customer_data = [
            'name' => trim($_POST['name']),
            'mobile' => trim($_POST['mobile']),
            'alt_mobile' => trim($_POST['alt_mobile']),
            'email' => trim($_POST['email']),
            'address' => trim($_POST['address']),
            'city' => trim($_POST['city']),
            'state' => trim($_POST['state']),
            'pincode' => trim($_POST['pincode']),
            'gstin' => trim($_POST['gstin']),
            'aadhaar' => trim($_POST['aadhaar']),
            'pan' => trim($_POST['pan']),
            'remarks' => trim($_POST['remarks'])
        ];

        if (empty($customer_data['name']) || empty($customer_data['mobile'])) {
            $error = 'Customer Name and Mobile Number are required!';
            $action = empty($id) ? 'new' : 'edit';
        } else {
            if (empty($id)) {
                // New customer
                db_insert('customers', $customer_data);
                log_audit('Customer Created', 'Added new customer: ' . $customer_data['name']);
                $success = 'Customer created successfully!';
            } else {
                // Edit customer
                db_update('customers', $id, $customer_data);
                log_audit('Customer Updated', 'Updated customer details for ID ' . $id . ': ' . $customer_data['name']);
                $success = 'Customer updated successfully!';
            }
            $action = 'list';
        }
    }
} elseif ($action === 'delete' && !empty($id)) {
    $c = db_find('customers', $id);
    if ($c) {
        db_delete('customers', $id);
        log_audit('Customer Deleted', 'Deleted customer: ' . $c['name']);
        $success = 'Customer deleted successfully!';
    }
    $action = 'list';
}

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

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ACTION: LIST CUSTOMERS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Customer Master</h4>
                    <p class="text-muted fs-14 mb-0">View, add, edit and inspect your business ledger for each customer.</p>
                </div>
                <div class="gap-2 d-flex align-items-center flex-wrap">
                    <a href="customers.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-user-plus me-2"></i>Add Customer
                    </a>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>City / State</th>
                                    <th>GSTIN</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td><a href="customers.php?action=view&id=<?php echo $c['id']; ?>" class="fw-semibold text-primary"><?php echo htmlspecialchars($c['name']); ?></a></td>
                                        <td><?php echo htmlspecialchars($c['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($c['city']); ?>, <?php echo htmlspecialchars($c['state']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($c['gstin'] ?: 'N/A'); ?></span></td>
                                        <td><span class="fs-13 text-muted"><?php echo htmlspecialchars($c['remarks']); ?></span></td>
                                        <td>
                                            <a href="customers.php?action=view&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" title="View Ledger"><i class="ti ti-file-analytics"></i></a>
                                            <a href="customers.php?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-edit"></i></a>
                                            <a href="customers.php?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this customer?')"><i class="ti ti-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: CREATE/EDIT CUSTOMER -->
        <?php elseif ($action === 'new' || $action === 'edit'): 
            $cust = [];
            if ($action === 'edit') {
                $cust = db_find('customers', $id);
            }
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0"><?php echo $action === 'new' ? 'Add New Customer' : 'Edit Customer'; ?></h4>
                    <p class="text-muted fs-14 mb-0">Fill in the customer details below. Fields marked with * are mandatory.</p>
                </div>
                <div>
                    <a href="customers.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="customers.php?action=save<?php echo !empty($id) ? '&id=' . $id : ''; ?>" method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Customer Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars(isset($cust['name']) ? $cust['name'] : ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Mobile Number *</label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars(isset($cust['mobile']) ? $cust['mobile'] : ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Alternate Mobile</label>
                                <input type="text" name="alt_mobile" class="form-control" value="<?php echo htmlspecialchars(isset($cust['alt_mobile']) ? $cust['alt_mobile'] : ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(isset($cust['email']) ? $cust['email'] : ''); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-medium">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars(isset($cust['address']) ? $cust['address'] : ''); ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars(isset($cust['city']) ? $cust['city'] : ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">State</label>
                                <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars(isset($cust['state']) ? $cust['state'] : ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars(isset($cust['pincode']) ? $cust['pincode'] : ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">GST Number (GSTIN)</label>
                                <input type="text" name="gstin" class="form-control" placeholder="e.g. 21AAAAA0000A1Z0" value="<?php echo htmlspecialchars(isset($cust['gstin']) ? $cust['gstin'] : ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">Aadhaar (Optional)</label>
                                <input type="text" name="aadhaar" class="form-control" value="<?php echo htmlspecialchars(isset($cust['aadhaar']) ? $cust['aadhaar'] : ''); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-medium">PAN (Optional)</label>
                                <input type="text" name="pan" class="form-control" value="<?php echo htmlspecialchars(isset($cust['pan']) ? $cust['pan'] : ''); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-medium">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"><?php echo htmlspecialchars(isset($cust['remarks']) ? $cust['remarks'] : ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save Customer</button>
                            <a href="customers.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

        <!-- ACTION: VIEW CUSTOMER LEDGER -->
        <?php elseif ($action === 'view' && !empty($id)):
            $cust = db_find('customers', $id);
            if (!$cust) {
                echo '<div class="alert alert-danger">Customer not found!</div>';
            } else {
                // Fetch customer's transactions
                $all_quotations = db_get_table('quotations');
                $all_invoices = db_get_table('invoices');
                $all_payments = db_get_table('payments');

                $customer_quotations = [];
                foreach ($all_quotations as $q) {
                    if ($q['customer_id'] === $id) {
                        $customer_quotations[] = $q;
                    }
                }

                $customer_invoices = [];
                $total_business_value = 0;
                $total_paid = 0;
                $total_outstanding = 0;

                foreach ($all_invoices as $inv) {
                    if ($inv['customer_id'] === $id) {
                        $customer_invoices[] = $inv;
                        $total_business_value += (float)$inv['grand_total'];
                        $total_paid += (float)$inv['amount_paid'];
                        $total_outstanding += (float)$inv['outstanding_balance'];
                    }
                }

                $customer_payments = [];
                foreach ($all_payments as $p) {
                    // Check if payment links to an invoice belonging to this customer
                    $invoice = db_find('invoices', $p['invoice_id']);
                    if ($invoice && $invoice['customer_id'] === $id) {
                        $p['invoice_number'] = $invoice['invoice_number'];
                        $customer_payments[] = $p;
                    }
                }
                ?>
                <!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
                    <div>
                        <h4 class="mb-0">Customer Ledger - <?php echo htmlspecialchars($cust['name']); ?></h4>
                        <p class="text-muted fs-14 mb-0">Financial summary and business history overview.</p>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <button onclick="window.print()" class="btn btn-outline-dark d-inline-flex align-items-center">
                            <i class="ti ti-printer me-2"></i>Print Ledger
                        </button>
                        <a href="customers.php" class="btn btn-light">Back to List</a>
                    </div>
                </div>

                <!-- Ledger Summary Card Row -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-primary text-white">
                            <div class="card-body text-center">
                                <p class="mb-1 opacity-75">Total Business Value</p>
                                <h3 class="text-white mb-0">₹<?php echo number_format($total_business_value, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-success text-white">
                            <div class="card-body text-center">
                                <p class="mb-1 opacity-75">Total Paid</p>
                                <h3 class="text-white mb-0">₹<?php echo number_format($total_paid, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-danger text-white">
                            <div class="card-body text-center">
                                <p class="mb-1 opacity-75">Outstanding Balance</p>
                                <h3 class="text-white mb-0">₹<?php echo number_format($total_outstanding, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 bg-light text-dark">
                            <div class="card-body text-center">
                                <p class="mb-1 text-muted">Total Bookings</p>
                                <h3 class="mb-0"><?php echo count($customer_invoices); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Customer Information</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Name</p>
                                <h6 class="text-dark"><?php echo htmlspecialchars($cust['name']); ?></h6>
                                <p class="mb-1 text-muted mt-2">Mobile</p>
                                <h6 class="text-dark"><?php echo htmlspecialchars($cust['mobile']); ?> (Alt: <?php echo htmlspecialchars($cust['alt_mobile'] ?: 'None'); ?>)</h6>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Email</p>
                                <h6 class="text-dark"><?php echo htmlspecialchars($cust['email'] ?: 'N/A'); ?></h6>
                                <p class="mb-1 text-muted mt-2">Address</p>
                                <h6 class="text-dark"><?php echo htmlspecialchars($cust['address']); ?>, <?php echo htmlspecialchars($cust['city']); ?>, <?php echo htmlspecialchars($cust['state']); ?> - <?php echo htmlspecialchars($cust['pincode']); ?></h6>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">GSTIN</p>
                                <h6 class="text-dark"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($cust['gstin'] ?: 'Not Registered'); ?></span></h6>
                                <p class="mb-1 text-muted mt-2">Identity Details</p>
                                <h6 class="text-dark">Aadhaar: <?php echo htmlspecialchars($cust['aadhaar'] ?: 'N/A'); ?> | PAN: <?php echo htmlspecialchars($cust['pan'] ?: 'N/A'); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Tab Tables -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-tabs-solid no-print" id="ledgerTabs">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#invoicesTab">GST Invoices (<?php echo count($customer_invoices); ?>)</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#quotationsTab">Quotations (<?php echo count($customer_quotations); ?>)</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#paymentsTab">Payments Ledger (<?php echo count($customer_payments); ?>)</a></li>
                        </ul>
                        
                        <div class="tab-content mt-4">
                            <!-- Invoices Tab -->
                            <div class="tab-pane fade show active" id="invoicesTab">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Invoice No.</th>
                                                <th>Date</th>
                                                <th>Route</th>
                                                <th>Subtotal</th>
                                                <th>GST Tax</th>
                                                <th>Total Invoice</th>
                                                <th>Outstanding</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($customer_invoices)): ?>
                                                <tr><td colspan="8" class="text-center text-muted">No Invoices generated yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($customer_invoices as $inv): ?>
                                                    <tr>
                                                        <td><a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                                        <td><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                                                        <td><?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?></td>
                                                        <td>₹<?php echo number_format($inv['subtotal'], 2); ?></td>
                                                        <td>₹<?php echo number_format($inv['gst_amount'], 2); ?></td>
                                                        <td class="fw-bold">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                                        <td class="text-danger">₹<?php echo number_format($inv['outstanding_balance'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $inv['status'] === 'Paid' ? 'success' : ($inv['status'] === 'Partially Paid' ? 'warning' : 'danger'); ?>">
                                                                <?php echo htmlspecialchars($inv['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quotations Tab -->
                            <div class="tab-pane fade" id="quotationsTab">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Quotation No.</th>
                                                <th>Date</th>
                                                <th>Route</th>
                                                <th>Est. Items count</th>
                                                <th>Total Valuation</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($customer_quotations)): ?>
                                                <tr><td colspan="6" class="text-center text-muted">No Quotations generated yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($customer_quotations as $q): ?>
                                                    <tr>
                                                        <td><span class="fw-bold">#<?php echo htmlspecialchars($q['quotation_number']); ?></span></td>
                                                        <td><?php echo date('d M Y', strtotime($q['created_at'])); ?></td>
                                                        <td><?php echo htmlspecialchars($q['from_city']); ?> ➜ <?php echo htmlspecialchars($q['to_city']); ?></td>
                                                        <td><?php echo count($q['items']); ?> items</td>
                                                        <td class="fw-bold">₹<?php echo number_format($q['grand_total'], 2); ?></td>
                                                        <td><a href="quotations.php?action=view&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-outline-info">Print / View</a></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Payments Tab -->
                            <div class="tab-pane fade" id="paymentsTab">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Receipt Date</th>
                                                <th>Invoice No.</th>
                                                <th>Amount Paid</th>
                                                <th>Payment Mode</th>
                                                <th>Ref No / UPI Id</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($customer_payments)): ?>
                                                <tr><td colspan="6" class="text-center text-muted">No Payment transactions recorded yet.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($customer_payments as $p): ?>
                                                    <tr>
                                                        <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                                                        <td><a href="invoices.php?action=view&id=<?php echo $p['invoice_id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($p['invoice_number']); ?></a></td>
                                                        <td class="text-success fw-bold">₹<?php echo number_format($p['amount'], 2); ?></td>
                                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($p['payment_mode']); ?></span></td>
                                                        <td><code><?php echo htmlspecialchars($p['reference_number'] ?: 'N/A'); ?></code></td>
                                                        <td><span class="fs-13 text-muted"><?php echo htmlspecialchars($p['remarks']); ?></span></td>
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
            <?php } ?>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
