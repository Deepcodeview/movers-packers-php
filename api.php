<?php
// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper to return error
function send_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Helper to return success
function send_success($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        
        if (empty($username) || empty($password)) {
            send_error('Username and password are required');
        }
        
        $users = db_get_table('users');
        foreach ($users as $u) {
            if ($u['username'] === $username && $u['password'] === $password) {
                // Return simple user token / profile
                send_success([
                    'user' => [
                        'id' => $u['id'],
                        'name' => $u['name'],
                        'role' => $u['role'],
                        'username' => $u['username']
                    ]
                ]);
            }
        }
        send_error('Invalid Username or Password!', 401);
        break;

    case 'dashboard':
        $customers = db_get_table('customers');
        $quotations = db_get_table('quotations');
        $invoices = db_get_table('invoices');
        $payments = db_get_table('payments');
        
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
            $inv_date = date('Y-m-d', strtotime($inv['created_at']));
            if ($inv_date === $today_date) {
                $today_sales += (float)$inv['grand_total'];
            }
            if ($inv['status'] === 'Paid') {
                $paid_count++;
            } elseif ($inv['status'] === 'Partially Paid') {
                $partial_count++;
            } elseif ($inv['status'] === 'Unpaid' || $inv['status'] === 'Confirmed') {
                $unpaid_count++;
            }
            $pending_payments += (float)$inv['outstanding_balance'];
        }
        
        foreach ($payments as $pay) {
            $pay_date = date('Y-m-d', strtotime($pay['payment_date']));
            if ($pay_date === $today_date) {
                $today_collection += (float)$pay['amount'];
            }
        }
        
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
        
        send_success([
            'metrics' => [
                'today_sales' => $today_sales,
                'today_collection' => $today_collection,
                'pending_payments' => $pending_payments,
                'total_customers' => $total_customers,
                'total_quotations' => $total_quotations,
                'total_invoices' => $total_invoices,
                'invoice_status' => [
                    'paid' => $paid_count,
                    'partial' => $partial_count,
                    'unpaid' => $unpaid_count
                ]
            ],
            'chart_data' => array_values($months_data)
        ]);
        break;

    case 'customers_list':
        send_success(['customers' => db_get_table('customers')]);
        break;

    case 'customer_add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = isset($input['name']) ? trim($input['name']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $address = isset($input['address']) ? trim($input['address']) : '';
        $state = isset($input['state']) ? trim($input['state']) : '';
        $gstin = isset($input['gstin']) ? trim($input['gstin']) : '';
        
        if (empty($name) || empty($phone)) {
            send_error('Customer Name and Phone are required');
        }
        
        $cust_data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'state' => $state,
            'gstin' => $gstin
        ];
        db_insert('customers', $cust_data);
        send_success(['message' => 'Customer added successfully!']);
        break;

    case 'quotations_list':
        send_success(['quotations' => db_get_table('quotations')]);
        break;

    case 'quotation_add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $customer_id = isset($input['customer_id']) ? $input['customer_id'] : '';
        $from_city = isset($input['from_city']) ? trim($input['from_city']) : '';
        $to_city = isset($input['to_city']) ? trim($input['to_city']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $date = isset($input['quotation_date']) ? trim($input['quotation_date']) : '';
        $items = isset($input['items']) ? $input['items'] : []; // expects structure like {"Bed":1, "Sofa":2}
        
        $packing_charge = isset($input['packing_charge']) ? (float)$input['packing_charge'] : 0;
        $unpacking_charge = isset($input['unpacking_charge']) ? (float)$input['unpacking_charge'] : 0;
        $loading_charge = isset($input['loading_charge']) ? (float)$input['loading_charge'] : 0;
        $unloading_charge = isset($input['unloading_charge']) ? (float)$input['unloading_charge'] : 0;
        $escort_charge = isset($input['escort_charge']) ? (float)$input['escort_charge'] : 0;
        $storage_charge = isset($input['storage_charge']) ? (float)$input['storage_charge'] : 0;
        $insurance_charge = isset($input['insurance_charge']) ? (float)$input['insurance_charge'] : 0;
        $gst_rate = isset($input['gst_rate']) ? (float)$input['gst_rate'] : 0;

        $subtotal = $packing_charge + $unpacking_charge + $loading_charge + $unloading_charge + $escort_charge + $storage_charge + $insurance_charge;
        $gst_amount = ($subtotal * $gst_rate) / 100;
        $grand_total = $subtotal + $gst_amount;

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
            'items' => json_encode($items),
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
            'grand_total' => $grand_total
        ];
        db_insert('quotations', $quotation_data);
        send_success(['message' => 'Quotation generated successfully!', 'quotation_number' => $quotation_number]);
        break;

    case 'invoices_list':
        send_success(['invoices' => db_get_table('invoices')]);
        break;

    case 'invoice_add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $customer_id = isset($input['customer_id']) ? $input['customer_id'] : '';
        $quotation_id = isset($input['quotation_id']) ? $input['quotation_id'] : null;
        $from_city = isset($input['from_city']) ? trim($input['from_city']) : '';
        $to_city = isset($input['to_city']) ? trim($input['to_city']) : '';
        $invoice_date = isset($input['invoice_date']) ? trim($input['invoice_date']) : date('Y-m-d');
        $vehicle_number = isset($input['vehicle_number']) ? trim($input['vehicle_number']) : '';
        $driver_name = isset($input['driver_name']) ? trim($input['driver_name']) : '';
        
        $freight = isset($input['freight_charge']) ? (float)$input['freight_charge'] : 0;
        $packing = isset($input['packing_charge']) ? (float)$input['packing_charge'] : 0;
        $loading = isset($input['loading_charge']) ? (float)$input['loading_charge'] : 0;
        $unloading = isset($input['unloading_charge']) ? (float)$input['unloading_charge'] : 0;
        $unpacking = isset($input['unpacking_charge']) ? (float)$input['unpacking_charge'] : 0;
        $escort = isset($input['escort_charge']) ? (float)$input['escort_charge'] : 0;
        
        $gst_type = isset($input['gst_type']) ? trim($input['gst_type']) : 'full_amount'; // default is full_amount
        $gst_rate = isset($input['gst_rate']) ? (float)$input['gst_rate'] : 0;

        $subtotal = $freight + $packing + $loading + $unloading + $unpacking + $escort;

        // Calculate GST base
        $gst_base = ($gst_type === 'freight_only') ? $freight : $subtotal;
        $gst_amount = ($gst_base * $gst_rate) / 100;
        $grand_total = $subtotal + $gst_amount;

        // Determine Intrastate splits vs Interstate
        $c = db_find('customers', $customer_id);
        $is_intrastate = true;
        if ($c) {
            $cust_state = strtolower(trim($c['state']));
            if ($cust_state !== '' && $cust_state !== 'odisha' && $cust_state !== 'orissa') {
                $is_intrastate = false;
            }
        }
        $cgst_amount = 0; $sgst_amount = 0; $igst_amount = 0;
        if ($is_intrastate) {
            $cgst_amount = $gst_amount / 2;
            $sgst_amount = $gst_amount / 2;
        } else {
            $igst_amount = $gst_amount;
        }

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
            'quotation_id' => $quotation_id,
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
            'cgst_amount' => $cgst_amount,
            'sgst_amount' => $sgst_amount,
            'igst_amount' => $igst_amount,
            'gst_amount' => $gst_amount,
            'grand_total' => $grand_total,
            'amount_paid' => 0.0,
            'outstanding_balance' => $grand_total,
            'status' => 'Unpaid'
        ];
        db_insert('invoices', $invoice_data);
        send_success(['message' => 'Invoice generated successfully!', 'invoice_number' => $invoice_number]);
        break;

    case 'lr_list':
        send_success(['lorry_receipts' => db_get_table('lorry_receipts')]);
        break;

    case 'lr_add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $invoice_id = isset($input['invoice_id']) ? $input['invoice_id'] : '';
        $lr_date = isset($input['lr_date']) ? trim($input['lr_date']) : date('Y-m-d');
        $consignor_name = isset($input['consignor_name']) ? trim($input['consignor_name']) : '';
        $consignor_mobile = isset($input['consignor_mobile']) ? trim($input['consignor_mobile']) : '';
        $consignor_gstin = isset($input['consignor_gstin']) ? trim($input['consignor_gstin']) : '';
        $consignee_name = isset($input['consignee_name']) ? trim($input['consignee_name']) : '';
        $consignee_mobile = isset($input['consignee_mobile']) ? trim($input['consignee_mobile']) : '';
        $consignee_gstin = isset($input['consignee_gstin']) ? trim($input['consignee_gstin']) : '';
        
        $from_address = isset($input['from_address']) ? trim($input['from_address']) : '';
        $to_address = isset($input['to_address']) ? trim($input['to_address']) : '';
        
        $vehicle_number = isset($input['vehicle_number']) ? trim($input['vehicle_number']) : '';
        $driver_name = isset($input['driver_name']) ? trim($input['driver_name']) : '';
        $driver_mobile = isset($input['driver_mobile']) ? trim($input['driver_mobile']) : '';
        
        $articles_count = isset($input['articles_count']) ? (int)$input['articles_count'] : 0;
        $description = isset($input['description']) ? trim($input['description']) : '';
        $goods_value = isset($input['goods_value']) ? (float)$input['goods_value'] : 0;
        
        $freight_charges = isset($input['freight_charges']) ? (float)$input['freight_charges'] : 0;
        $to_pay_billing = isset($input['to_pay_billing']) ? trim($input['to_pay_billing']) : 'To Pay';
        
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
        $lr_no = 'LR/' . date('y') . '-' . date('y', strtotime('+1 year')) . '/' . str_pad($next_lr_seq, 4, '0', STR_PAD_LEFT);

        $lr_data = [
            'lr_no' => $lr_no,
            'lr_date' => $lr_date,
            'invoice_id' => $invoice_id,
            'consignor_name' => $consignor_name,
            'consignor_mobile' => $consignor_mobile,
            'consignor_gstin' => $consignor_gstin,
            'consignee_name' => $consignee_name,
            'consignee_mobile' => $consignee_mobile,
            'consignee_gstin' => $consignee_gstin,
            'from_address' => $from_address,
            'to_address' => $to_address,
            'vehicle_number' => $vehicle_number,
            'driver_name' => $driver_name,
            'driver_mobile' => $driver_mobile,
            'articles_count' => $articles_count,
            'description' => $description,
            'goods_value' => $goods_value,
            'freight_charges' => $freight_charges,
            'to_pay_billing' => $to_pay_billing
        ];
        db_insert('lorry_receipts', $lr_data);
        send_success(['message' => 'Lorry Receipt (Bilty) generated successfully!', 'lr_no' => $lr_no]);
        break;

    case 'payments_list':
        send_success(['payments' => db_get_table('payments')]);
        break;

    case 'payment_add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            send_error('POST method required');
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $invoice_id = isset($input['invoice_id']) ? $input['invoice_id'] : '';
        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
        $payment_mode = isset($input['payment_mode']) ? trim($input['payment_mode']) : 'Cash';
        $reference_number = isset($input['reference_number']) ? trim($input['reference_number']) : '';
        $remarks = isset($input['remarks']) ? trim($input['remarks']) : '';
        $payment_date = isset($input['payment_date']) ? trim($input['payment_date']) : date('Y-m-d');
        
        if (empty($invoice_id) || $amount <= 0) {
            send_error('Invoice ID and positive payment amount are required');
        }
        
        // Find invoice to calculate balance updates
        $inv = db_find('invoices', $invoice_id);
        if (!$inv) {
            send_error('Invoice not found');
        }
        
        $new_paid = (float)$inv['amount_paid'] + $amount;
        $new_outstanding = (float)$inv['grand_total'] - $new_paid;
        
        if ($new_outstanding < 0) {
            send_error('Payment amount exceeds outstanding balance. Max allowed: ₹' . $inv['outstanding_balance']);
        }
        
        // Determine status
        $status = 'Partially Paid';
        if ($new_outstanding <= 0.05) { // offset buffer
            $status = 'Paid';
            $new_outstanding = 0.0;
        }

        // Save payment log
        $pay_data = [
            'invoice_id' => $invoice_id,
            'amount' => $amount,
            'payment_date' => $payment_date,
            'payment_mode' => $payment_mode,
            'reference_number' => $reference_number,
            'remarks' => $remarks
        ];
        db_insert('payments', $pay_data);
        
        // Update Invoice status
        db_update('invoices', $invoice_id, [
            'amount_paid' => $new_paid,
            'outstanding_balance' => $new_outstanding,
            'status' => $status
        ]);
        
        send_success(['message' => 'Payment logged successfully!']);
        break;

    case 'gst_audit':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        $invoices = db_get_table('invoices');
        $filtered = [];
        $totals = [
            'taxable' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'igst' => 0,
            'gst' => 0,
            'sales' => 0
        ];
        
        foreach ($invoices as $inv) {
            if ($inv['status'] === 'Draft' || $inv['status'] === 'Cancelled') {
                continue;
            }
            $inv_date = date('Y-m-d', strtotime($inv['invoice_date']));
            if ($inv_date >= $start_date && $inv_date <= $end_date) {
                $c = db_find('customers', $inv['customer_id']);
                $taxable = ($inv['gst_type'] === 'freight_only') ? (float)$inv['freight_charge'] : (float)$inv['subtotal'];
                
                $inv_item = [
                    'id' => $inv['id'],
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
                
                $filtered[] = $inv_item;
                $totals['taxable'] += $taxable;
                $totals['cgst'] += $inv_item['cgst'];
                $totals['sgst'] += $inv_item['sgst'];
                $totals['igst'] += $inv_item['igst'];
                $totals['gst'] += $inv_item['gst_amount'];
                $totals['sales'] += $inv_item['grand_total'];
            }
        }
        send_success(['invoices' => $filtered, 'totals' => $totals, 'start_date' => $start_date, 'end_date' => $end_date]);
        break;

    case 'audit_logs':
        $logs = db_get_table('audit_log');
        usort($logs, function($a, $b) {
            return (int)$b['id'] - (int)$a['id'];
        });
        send_success(['logs' => $logs]);
        break;

    default:
        send_error('Invalid API action request');
        break;
}
