<?php
$page_title = 'GST Return Audit Center';
$active_menu = 'gst_audit';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';


$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$export = isset($_GET['export']) ? $_GET['export'] : '';

$invoices = db_get_table('invoices');

$filtered_invoices = [];
$b2b_invoices = [];
$b2c_invoices = [];

$total_taxable = 0;
$total_cgst = 0;
$total_sgst = 0;
$total_igst = 0;
$total_gst = 0;
$total_sales = 0;

foreach ($invoices as $inv) {
    if ($inv['status'] === 'Draft' || $inv['status'] === 'Cancelled') {
        continue;
    }
    $inv_date = date('Y-m-d', strtotime($inv['invoice_date']));
    if ($inv_date >= $start_date && $inv_date <= $end_date) {
        $c = db_find('customers', $inv['customer_id']);
        $taxable = ($inv['gst_type'] === 'freight_only') ? (float)$inv['freight_charge'] : (float)$inv['subtotal'];
        
        $inv_item = [
            'invoice_no' => $inv['invoice_number'],
            'invoice_date' => $inv['invoice_date'],
            'customer_name' => $c ? $c['name'] : 'Unknown',
            'customer_gstin' => $c ? trim($c['gstin']) : '',
            'state' => $c ? trim($c['state']) : 'Odisha',
            'taxable_amount' => $taxable,
            'gst_rate' => (float)$inv['gst_rate'],
            'cgst' => (float)$inv['cgst_amount'],
            'sgst' => (float)$inv['sgst_amount'],
            'igst' => (float)$inv['igst_amount'],
            'gst_amount' => (float)$inv['gst_amount'],
            'grand_total' => (float)$inv['grand_total'],
            'status' => $inv['status']
        ];

        $filtered_invoices[] = $inv_item;
        
        if (!empty($inv_item['customer_gstin'])) {
            $b2b_invoices[] = $inv_item;
        } else {
            $b2c_invoices[] = $inv_item;
        }

        $total_taxable += $taxable;
        $total_cgst += $inv_item['cgst'];
        $total_sgst += $inv_item['sgst'];
        $total_igst += $inv_item['igst'];
        $total_gst += $inv_item['gst_amount'];
        $total_sales += $inv_item['grand_total'];
    }
}

// Excel/CSV GSTR-1 format download
if ($export === 'excel') {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=GSTR1_Audit_Report_' . $start_date . '_to_' . $end_date . '.csv');
    
    $output = fopen('php://output', 'w');
    // GSTR-1 Matching Columns
    fputcsv($output, ['GSTIN of Recipient', 'Receiver Name', 'Invoice Number', 'Invoice Date', 'Invoice Value', 'Place Of Supply (State)', 'Reverse Charge', 'Applicable % of Tax Rate', 'Invoice Type', 'E-Commerce GSTIN', 'Rate (%)', 'Taxable Value', 'CGST Amount (₹)', 'SGST Amount (₹)', 'IGST Amount (₹)', 'Total GST (₹)']);
    
    foreach ($filtered_invoices as $inv) {
        $pos = $inv['state'];
        $inv_type = !empty($inv['customer_gstin']) ? 'Regular B2B' : 'B2C Large/Small';
        fputcsv($output, [
            $inv['customer_gstin'],
            $inv['customer_name'],
            $inv['invoice_no'],
            date('d-M-Y', strtotime($inv['invoice_date'])),
            $inv['grand_total'],
            $pos,
            'N',
            $inv['gst_rate'],
            $inv_type,
            '',
            $inv['gst_rate'],
            $inv['taxable_amount'],
            $inv['cgst'],
            $inv['sgst'],
            $inv['igst'],
            $inv['gst_amount']
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
                <h4 class="mb-0">GST Return GSTR-1 Audit Helper</h4>
                <p class="text-muted fs-14 mb-0">Generate single-click reports formatted specifically for CA auditing and filing GST GSTR-1 & GSTR-3B returns.</p>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <a href="gst_audit.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=excel" class="btn btn-success d-inline-flex align-items-center shadow-sm">
                    <i class="ti ti-file-spreadsheet me-2 fs-18"></i>Get GSTR-1 Excel (CSV)
                </a>
                <button onclick="window.print()" class="btn btn-danger d-inline-flex align-items-center shadow-sm">
                    <i class="ti ti-pdf me-2 fs-18"></i>Print GSTR Audit PDF
                </button>
            </div>
        </div>

        <!-- Date range selectors -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label fw-bold">From Period (Start Date)</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="form-label fw-bold">To Period (End Date)</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 btn-lg">Generate Audit Data</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- GSTR Verification Highlights -->
        <div class="row mb-4">
            <div class="col-lg-3 col-sm-6">
                <div class="card bg-primary-transparent border-0 mb-3 mb-sm-0">
                    <div class="card-body py-3">
                        <span class="fs-12 text-muted d-block mb-1">Total GSTR Taxable Sales</span>
                        <h4 class="fw-bold text-dark mb-0">₹<?php echo number_format($total_taxable, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card bg-success-transparent border-0 mb-3 mb-sm-0">
                    <div class="card-body py-3">
                        <span class="fs-12 text-muted d-block mb-1">CGST (Central Tax Collected)</span>
                        <h4 class="fw-bold text-success mb-0">₹<?php echo number_format($total_cgst, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card bg-info-transparent border-0 mb-3 mb-sm-0">
                    <div class="card-body py-3">
                        <span class="fs-12 text-muted d-block mb-1">SGST (State Tax Collected)</span>
                        <h4 class="fw-bold text-info mb-0">₹<?php echo number_format($total_sgst, 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="card bg-warning-transparent border-0 mb-0">
                    <div class="card-body py-3">
                        <span class="fs-12 text-muted d-block mb-1">IGST (Integrated Tax Collected)</span>
                        <h4 class="fw-bold text-warning mb-0">₹<?php echo number_format($total_igst, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- GSTR Splits Summary: B2B vs B2C -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="card-title mb-0">B2B Registered Invoices (GSTIN Available)</h5>
                            <span class="badge bg-primary"><?php echo count($b2b_invoices); ?> Records</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm fs-12">
                                <thead class="table-light">
                                    <tr>
                                        <th>Inv No</th>
                                        <th>GSTIN</th>
                                        <th>Name</th>
                                        <th>Taxable Value</th>
                                        <th>Tax (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($b2b_invoices)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No B2B invoices found in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($b2b_invoices as $b2b): ?>
                                            <tr>
                                                <td><strong>#<?php echo htmlspecialchars($b2b['invoice_no']); ?></strong></td>
                                                <td><code><?php echo htmlspecialchars($b2b['customer_gstin']); ?></code></td>
                                                <td><?php echo htmlspecialchars($b2b['customer_name']); ?></td>
                                                <td>₹<?php echo number_format($b2b['taxable_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($b2b['gst_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="card-title mb-0">B2C Unregistered Invoices (Consumer Sales)</h5>
                            <span class="badge bg-secondary"><?php echo count($b2c_invoices); ?> Records</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm fs-12">
                                <thead class="table-light">
                                    <tr>
                                        <th>Inv No</th>
                                        <th>State / Place</th>
                                        <th>Customer Name</th>
                                        <th>Taxable Value</th>
                                        <th>Tax (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($b2c_invoices)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No B2C invoices found in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($b2c_invoices as $b2c): ?>
                                            <tr>
                                                <td><strong>#<?php echo htmlspecialchars($b2c['invoice_no']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($b2c['state']); ?></td>
                                                <td><?php echo htmlspecialchars($b2c['customer_name']); ?></td>
                                                <td>₹<?php echo number_format($b2c['taxable_amount'], 2); ?></td>
                                                <td>₹<?php echo number_format($b2c['gst_amount'], 2); ?></td>
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

        <!-- Master GST Log for CA audit check -->
        <div class="card border-0 mb-4 print-full-width">
            <div class="card-body">
                <h5 class="card-title mb-3 border-bottom pb-2">Consolidated GSTR-1 Master Return Data Sheet</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-nowrap">
                        <thead class="table-dark fs-12 text-center">
                            <tr>
                                <th>Invoice No</th>
                                <th>Invoice Date</th>
                                <th>GSTIN</th>
                                <th>Customer / Receiver</th>
                                <th>Place of Supply</th>
                                <th>Taxable Amt</th>
                                <th>Tax Rate (%)</th>
                                <th>CGST (₹)</th>
                                <th>SGST (₹)</th>
                                <th>IGST (₹)</th>
                                <th>GST Amount</th>
                                <th>Invoice Value</th>
                            </tr>
                        </thead>
                        <tbody class="fs-12 text-center">
                            <?php if (empty($filtered_invoices)): ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-3">No matching invoice record found for GST Return.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_invoices as $row): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($row['invoice_no']); ?></strong></td>
                                        <td><?php echo date('d-M-Y', strtotime($row['invoice_date'])); ?></td>
                                        <td><code><?php echo htmlspecialchars($row['customer_gstin'] ?: 'N/A'); ?></code></td>
                                        <td class="text-start"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['state']); ?></td>
                                        <td class="text-end fw-semibold">₹<?php echo number_format($row['taxable_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['gst_rate']); ?>%</td>
                                        <td class="text-end">₹<?php echo number_format($row['cgst'], 2); ?></td>
                                        <td class="text-end">₹<?php echo number_format($row['sgst'], 2); ?></td>
                                        <td class="text-end">₹<?php echo number_format($row['igst'], 2); ?></td>
                                        <td class="text-end text-danger fw-semibold">₹<?php echo number_format($row['gst_amount'], 2); ?></td>
                                        <td class="text-end fw-bold">₹<?php echo number_format($row['grand_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($filtered_invoices)): ?>
                            <tfoot class="table-light fs-12 fw-bold text-center">
                                <tr>
                                    <td colspan="5" class="text-end text-uppercase">Total Consolidated Summary:</td>
                                    <td class="text-end text-dark">₹<?php echo number_format($total_taxable, 2); ?></td>
                                    <td>-</td>
                                    <td class="text-end text-success">₹<?php echo number_format($total_cgst, 2); ?></td>
                                    <td class="text-end text-info">₹<?php echo number_format($total_sgst, 2); ?></td>
                                    <td class="text-end text-warning">₹<?php echo number_format($total_igst, 2); ?></td>
                                    <td class="text-end text-danger">₹<?php echo number_format($total_gst, 2); ?></td>
                                    <td class="text-end text-dark">₹<?php echo number_format($total_sales, 2); ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @media print {
        .print-full-width {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        .table-responsive table {
            width: 100% !important;
            table-layout: auto !important;
        }
    }
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
