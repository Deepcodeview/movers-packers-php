<?php
$page_title = 'Dashboard';
$active_menu = 'dashboard';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

// Fetch all database tables
$customers = db_get_table('customers');
$quotations = db_get_table('quotations');
$invoices = db_get_table('invoices');
$payments = db_get_table('payments');
$audit_log = db_get_table('audit_log');

// Statistics calculations
$total_customers = count($customers);
$total_quotations = count($quotations);
$total_invoices = count($invoices);

$today_date = date('Y-m-d');
$today_sales = 0;
$today_collection = 0;
$pending_payments = 0;

$paid_count = 0;
$partial_count = 0;
$unpaid_count = 0;

foreach ($invoices as $inv) {
    // Check if invoice belongs to today
    $inv_date = date('Y-m-d', strtotime($inv['created_at']));
    if ($inv_date === $today_date) {
        $today_sales += (float)$inv['grand_total'];
    }
    
    // Status counters
    if ($inv['status'] === 'Paid') {
        $paid_count++;
    } elseif ($inv['status'] === 'Partially Paid') {
        $partial_count++;
    } elseif ($inv['status'] === 'Unpaid' || $inv['status'] === 'Confirmed') {
        $unpaid_count++;
    }
    
    // Sum outstanding
    $pending_payments += (float)$inv['outstanding_balance'];
}

// Calculate collections today
foreach ($payments as $pay) {
    $pay_date = date('Y-m-d', strtotime($pay['payment_date']));
    if ($pay_date === $today_date) {
        $today_collection += (float)$pay['amount'];
    }
}

// Recent activities (top 5)
$recent_activities = array_slice($audit_log, 0, 5);

// Recent invoices (top 5)
usort($invoices, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_invoices = array_slice($invoices, 0, 5);

// Calculate monthly sales & collection data for the last 6 months
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $months_data[$month_key] = [
        'label' => $month_label,
        'sales' => 0,
        'collection' => 0
    ];
}

foreach ($invoices as $inv) {
    $m = date('Y-m', strtotime($inv['created_at']));
    if (isset($months_data[$m])) {
        $months_data[$m]['sales'] += (float)$inv['grand_total'];
    }
}

foreach ($payments as $pay) {
    $m = date('Y-m', strtotime($pay['payment_date']));
    if (isset($months_data[$m])) {
        $months_data[$m]['collection'] += (float)$pay['amount'];
    }
}

// Convert to simple JS arrays
$chart_labels = [];
$chart_sales = [];
$chart_collections = [];
foreach ($months_data as $m => $data) {
    $chart_labels[] = $data['label'];
    $chart_sales[] = $data['sales'];
    $chart_collections[] = $data['collection'];
}
?>

<div class="page-wrapper">
    <div class="content pb-0">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
                <p class="text-muted fs-14 mb-0">Here's what is happening with your packers & movers operations today.</p>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <a href="customers.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                    <i class="ti ti-user-plus me-2"></i>Add Customer
                </a>
                <a href="quotations.php?action=new" class="btn btn-secondary d-inline-flex align-items-center">
                    <i class="ti ti-file-report me-2"></i>New Quotation
                </a>
                <a href="invoices.php?action=new" class="btn btn-danger d-inline-flex align-items-center">
                    <i class="ti ti-file-invoice me-2"></i>New Invoice
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Quick Stats Widget Row -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-13 text-muted mb-1">Today's Sales</p>
                                <h4 class="mb-0">₹<?php echo number_format($today_sales, 2); ?></h4>
                            </div>
                            <span class="avatar avatar-md bg-teal-transparent text-teal rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-trending-up fs-20"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-13 text-muted mb-1">Today's Collection</p>
                                <h4 class="mb-0">₹<?php echo number_format($today_collection, 2); ?></h4>
                            </div>
                            <span class="avatar avatar-md bg-success-transparent text-success rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-report-money fs-20"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-13 text-muted mb-1">Outstanding Balance</p>
                                <h4 class="mb-0 text-danger">₹<?php echo number_format($pending_payments, 2); ?></h4>
                            </div>
                            <span class="avatar avatar-md bg-danger-transparent text-danger rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-alert-triangle fs-20"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-13 text-muted mb-1">Total Customers</p>
                                <h4 class="mb-0"><?php echo $total_customers; ?></h4>
                            </div>
                            <span class="avatar avatar-md bg-indigo-transparent text-indigo rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-users fs-20"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Power BI Charts Row -->
        <div class="row">
            <!-- Left: Sales vs Collection Trend Chart -->
            <div class="col-xl-8 col-lg-7 d-flex">
                <div class="card flex-fill shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="card-title mb-0">Monthly Shifting Business Trend (Last 6 Months)</h5>
                            <span class="badge bg-light text-dark border">Power BI Report</span>
                        </div>
                        <div style="height: 310px; position: relative;">
                            <canvas id="businessTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Invoice Payment Status Doughnut Chart -->
            <div class="col-xl-4 col-lg-5 d-flex">
                <div class="card flex-fill shadow-sm border-0 mb-4">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title mb-3">Invoice Payment Status</h5>
                            <div style="height: 200px; position: relative; margin-top: 15px;">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                        <div class="row text-center mt-3 fs-13 border-top pt-3">
                            <div class="col-4 border-end">
                                <span class="d-block text-success fw-semibold"><i class="ti ti-circle-filled me-1"></i>Paid</span>
                                <h6 class="mb-0 mt-1"><?php echo $paid_count; ?></h6>
                            </div>
                            <div class="col-4 border-end">
                                <span class="d-block text-warning fw-semibold"><i class="ti ti-circle-filled me-1"></i>Partial</span>
                                <h6 class="mb-0 mt-1"><?php echo $partial_count; ?></h6>
                            </div>
                            <div class="col-4">
                                <span class="d-block text-danger fw-semibold"><i class="ti ti-circle-filled me-1"></i>Unpaid</span>
                                <h6 class="mb-0 mt-1"><?php echo $unpaid_count; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices & Activities Row -->
        <div class="row">
            <!-- Left: Recent Invoices Table (Symmetric 8-col) -->
            <div class="col-xl-8 col-lg-7 d-flex">
                <div class="card flex-fill shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">Recent GST Invoices</h5>
                            <a href="invoices.php" class="btn btn-sm btn-light">View All Invoices</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice No.</th>
                                        <th>Customer</th>
                                        <th>Route</th>
                                        <th>Total Value</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_invoices)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No invoices generated yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_invoices as $inv): ?>
                                            <tr>
                                                <td><a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="fw-bold">#<?php echo htmlspecialchars($inv['invoice_number']); ?></a></td>
                                                <td>
                                                    <?php 
                                                    $c = db_find('customers', $inv['customer_id']);
                                                    echo htmlspecialchars($c ? $c['name'] : 'Unknown Customer');
                                                    ?>
                                                </td>
                                                <td><span class="badge bg-teal-transparent text-teal"><?php echo htmlspecialchars($inv['from_city']); ?> ➜ <?php echo htmlspecialchars($inv['to_city']); ?></span></td>
                                                <td class="fw-medium">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $status_badge = 'bg-secondary';
                                                    if ($inv['status'] === 'Paid') $status_badge = 'bg-success';
                                                    elseif ($inv['status'] === 'Partially Paid') $status_badge = 'bg-warning';
                                                    elseif ($inv['status'] === 'Unpaid') $status_badge = 'bg-danger';
                                                    elseif ($inv['status'] === 'Cancelled') $status_badge = 'bg-dark';
                                                    ?>
                                                    <span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($inv['status']); ?></span>
                                                </td>
                                                <td>
                                                    <a href="invoices.php?action=view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="tooltip" title="View & Print"><i class="ti ti-eye"></i></a>
                                                    <a href="payments.php?action=new&invoice_id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-icon btn-outline-success" data-bs-toggle="tooltip" title="Add Payment"><i class="ti ti-credit-card"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Recent Activities (Symmetric 4-col) -->
            <div class="col-xl-4 col-lg-5 d-flex">
                <div class="card flex-fill shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Recent Activity Logs</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>User & Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_activities)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No actions recorded.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_activities as $act): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-medium text-dark d-block"><?php echo htmlspecialchars($act['user_name']); ?></span>
                                                    <span class="badge bg-light text-dark fs-10"><?php echo htmlspecialchars($act['action_type']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="fs-12 text-muted text-wrap d-block" style="max-width: 180px;"><?php echo htmlspecialchars($act['description']); ?></span>
                                                    <small class="text-muted fs-10"><?php echo date('d M, h:i A', strtotime($act['timestamp'])); ?></small>
                                                </td>
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

    </div>
</div>

<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Business Trend Chart (Sales vs Collections Line Chart)
        var trendCtx = document.getElementById('businessTrendChart').getContext('2d');
        var trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Gross Revenue (₹)',
                        data: <?php echo json_encode($chart_sales); ?>,
                        borderColor: '#FF5E3A', // Primary Red/Orange
                        backgroundColor: 'rgba(255, 94, 58, 0.04)',
                        borderWidth: 3.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#FF5E3A',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 4.5,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Collected Cash (₹)',
                        data: <?php echo json_encode($chart_collections); ?>,
                        borderColor: '#10B981', // Clean Success Emerald
                        backgroundColor: 'rgba(16, 185, 129, 0.04)',
                        borderWidth: 3.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 4.5,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { family: 'Inter, Outfit, sans-serif', size: 12, weight: '500' }
                        }
                    },
                    tooltip: {
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Inter, sans-serif', weight: 'bold', size: 13 },
                        bodyFont: { family: 'Inter, sans-serif', size: 12 },
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ₹' + context.raw.toLocaleString('en-IN', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { family: 'Inter, sans-serif', size: 11 },
                            color: '#64748b',
                            callback: function(value) { return '₹' + value.toLocaleString('en-IN'); }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter, sans-serif', size: 11 }, color: '#64748b' }
                    }
                }
            }
        });

        // 2. Payment Status Chart (Doughnut Chart)
        var statusCtx = document.getElementById('paymentStatusChart').getContext('2d');
        var statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid Invoices', 'Partially Paid', 'Unpaid Invoices'],
                datasets: [{
                    data: [
                        <?php echo $paid_count; ?>,
                        <?php echo $partial_count; ?>,
                        <?php echo $unpaid_count; ?>
                    ],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 6,
                        bodyFont: { family: 'Inter, sans-serif' }
                    }
                },
                cutout: '72%'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
