<?php
$page_title = 'Business Reports';
$active_menu = 'reports';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$export = isset($_GET['export']) ? $_GET['export'] : '';

$invoices = db_get_table('invoices');
$payments = db_get_table('payments');
$customers = db_get_table('customers');

// Filtered data
$filtered_invoices = [];
$total_sales = 0;
$total_taxable = 0;
$total_gst = 0;
$total_cgst = 0;
$total_sgst = 0;
$total_igst = 0;
$total_collected = 0;
$total_outstanding = 0;

foreach ($invoices as $inv) {
    if ($inv['status'] === 'Draft' || $inv['status'] === 'Cancelled') {
        continue;
    }
    $inv_date = date('Y-m-d', strtotime($inv['invoice_date']));
    if ($inv_date >= $start_date && $inv_date <= $end_date) {
        $filtered_invoices[] = $inv;
        
        $total_sales += (float)$inv['grand_total'];
        $total_gst += (float)$inv['gst_amount'];
        $total_cgst += (float)$inv['cgst_amount'];
        $total_sgst += (float)$inv['sgst_amount'];
        $total_igst += (float)$inv['igst_amount'];
        
        // Compute taxable base
        $taxable = ($inv['gst_type'] === 'freight_only') ? (float)$inv['freight_charge'] : (float)$inv['subtotal'];
        $total_taxable += $taxable;
        
        $total_collected += (float)$inv['amount_paid'];
        $total_outstanding += (float)$inv['outstanding_balance'];
    }
}

// Filtered payments (collections)
$filtered_payments = [];
foreach ($payments as $p) {
    $p_date = date('Y-m-d', strtotime($p['payment_date']));
    if ($p_date >= $start_date && $p_date <= $end_date) {
        $filtered_payments[] = $p;
    }
}

// CSV Export Logic
if ($export === 'csv') {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=GST_Sales_Report_' . $start_date . '_to_' . $end_date . '.csv');
    
    $output = fopen('php://output', 'w');
    // headers
    fputcsv($output, ['Invoice Number', 'Customer Name', 'GSTIN', 'Date', 'Route', 'Subtotal', 'Taxable Amt', 'GST Rate', 'CGST', 'SGST', 'IGST', 'Grand Total', 'Amt Paid', 'Outstanding', 'Status']);
    
    foreach ($filtered_invoices as $inv) {
        $c = db_find('customers', $inv['customer_id']);
        $taxable = ($inv['gst_type'] === 'freight_only') ? $inv['freight_charge'] : $inv['subtotal'];
        
        fputcsv($output, [
            $inv['invoice_number'],
            $c ? $c['name'] : 'Unknown',
            $c ? $c['gstin'] : '',
            $inv['invoice_date'],
            $inv['from_city'] . ' - ' . $inv['to_city'],
            $inv['subtotal'],
            $taxable,
            $inv['gst_rate'] . '%',
            $inv['cgst_amount'],
            $inv['sgst_amount'],
            $inv['igst_amount'],
            $inv['grand_total'],
            $inv['amount_paid'],
            $inv['outstanding_balance'],
            $inv['status']
        ]);
    }
    fclose($output);
    exit;
}
?>

<div class="page-wrapper">
    <div class="content pb-0">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap no-print">
            <div>
                <h4 class="mb-0">GST Summary & Business Reports</h4>
                <p class="text-muted fs-14 mb-0">Generate financial summaries and export GSTR-1 matching tables.</p>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <a href="reports.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=csv" class="btn btn-outline-success">
                    <i class="ti ti-download me-2"></i>Export CSV Spreadsheet
                </a>
                <button onclick="window.print()" class="btn btn-dark">
                    <i class="ti ti-printer me-2"></i>Print Summary
                </button>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label fw-medium">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label fw-medium">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Date Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tax & Business Summary Widgets -->
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted fs-13">Total Taxable Value</p>
                        <h4 class="fw-bold">₹<?php echo number_format($total_taxable, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center bg-light-transparent">
                        <p class="mb-1 text-muted fs-13">Total GST Tax</p>
                        <h4 class="text-indigo fw-bold">₹<?php echo number_format($total_gst, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted fs-13">Total Collections</p>
                        <h4 class="text-success fw-bold">₹<?php echo number_format($total_collected, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted fs-13">Outstanding Due</p>
                        <h4 class="text-danger fw-bold">₹<?php echo number_format($total_outstanding, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed GST Tax Split Breakdown -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="card-title">GST Tax Splits</h5>
                <div class="row text-center fs-14">
                    <div class="col-4 border-end py-2">
                        <p class="mb-1 text-muted">CGST (Central Tax)</p>
                        <h5 class="fw-bold">₹<?php echo number_format($total_cgst, 2); ?></h5>
                    </div>
                    <div class="col-4 border-end py-2">
                        <p class="mb-1 text-muted">SGST (State Tax)</p>
                        <h5 class="fw-bold">₹<?php echo number_format($total_sgst, 2); ?></h5>
                    </div>
                    <div class="col-4 py-2">
                        <p class="mb-1 text-muted">IGST (Integrated Tax)</p>
                        <h5 class="fw-bold">₹<?php echo number_format($total_igst, 2); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices List Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Detailed Sales Invoice Log (<?php echo date('d M Y', strtotime($start_date)); ?> to <?php echo date('d M Y', strtotime($end_date)); ?>)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light fs-13">
                            <tr>
                                <th>Invoice No</th>
                                <th>Customer Name</th>
                                <th>GSTIN</th>
                                <th>Taxable Amt</th>
                                <th>CGST</th>
                                <th>SGST</th>
                                <th>IGST</th>
                                <th>Grand Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="fs-13">
                            <?php if (empty($filtered_invoices)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No invoices found matching the selected range.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_invoices as $inv): 
                                    $c = db_find('customers', $inv['customer_id']);
                                    $taxable = ($inv['gst_type'] === 'freight_only') ? $inv['freight_charge'] : $inv['subtotal'];
                                    ?>
                                    <tr>
                                        <td><a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                        <td><?php echo htmlspecialchars($c ? $c['name'] : 'Unknown'); ?></td>
                                        <td><code><?php echo htmlspecialchars($c ? $c['gstin'] : 'N/A'); ?></code></td>
                                        <td>₹<?php echo number_format($taxable, 2); ?></td>
                                        <td>₹<?php echo number_format($inv['cgst_amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($inv['sgst_amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($inv['igst_amount'], 2); ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $inv['status'] === 'Paid' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($inv['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
