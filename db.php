<?php
/**
 * MySQL Database Helper for Packers & Movers CRM
 * Migrated from offline JSON document store to auto-installing MySQL PDO
 */

function db_connect() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $db   = 'deepsde_Deepak';
        $user = 'deepsde_Deepak';
        $pass = 'Deepak9955@';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Check if tables exist, otherwise auto-create tables
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                db_create_schema($pdo);
            } else {
                // Self-healing check: Ensure created_at exists in all tables (required for date conversions in PHP frontend)
                $tables_to_check = ['users', 'products', 'customers', 'quotations', 'invoices', 'payments', 'lorry_receipts'];
                foreach ($tables_to_check as $tbl) {
                    $chk = $pdo->query("SHOW COLUMNS FROM `$tbl` LIKE 'created_at'");
                    if ($chk->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                    }
                }
                // Ensure all 65 checklist products exist
                $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                if ($stmt->fetchColumn() < 60) {
                    $pdo->exec("DELETE FROM products");
                    
                    $predefined_items = [
                        'Double Bed (Box Dism)', 'Deewan', 'Sofaset (3+1+1)(3+3+1)', 'Centre Table', 'Almirah (Small & Big)',
                        'Television (Small) LCD/LED', 'T.V. Trolly', 'Fridge (165/280Ltr.)', 'Washing Machine', 'Air Conditioner',
                        'Cooler (Small & Big)', 'Dressing Table', 'Computer', 'Computer Table', 'Writing Table',
                        'Dining Table Glass/Wooden(4+1)', 'Bunk Bed', 'Side Table', 'Music System', 'Show Case (Small & Big)',
                        'Wall Unit', 'Cabinet (Small & Big)', 'Single Cot', 'Folding Cot', 'Folding Chair',
                        'Wooden Chair', 'Rocking Chair', 'Cane Set', 'Oven', 'Cooking Range', 'Aquaguard',
                        'Rack (Shoes/Kitchen)', 'Crockery', 'Book Shelf', 'Books', 'Geyser', 'Gas Stove',
                        'Temple', 'Matries, Flat/Fold', 'Fan', 'Vaccum Cleaner', 'Stabilizer', 'Sewing Machine',
                        'Palmets/Steel Box', 'Carpet', 'Wetgrinder', 'Bean Bag', 'Toys', 'Photo Frame', 'Wall Frame',
                        'Music System Trolly', 'Jhoola', 'Utensils', 'Steel Drums', 'Plastic Chair', 'Cloths',
                        'Flower Posts', 'Inverter/Generator', 'Cycle (Baby & Other)', 'Brief-Case', 'Trunk',
                        'Exercise Cycle', 'Scooter/Motorcycle', 'Car', 'Mice'
                    ];
                    
                    $ins = $pdo->prepare("INSERT INTO products (id, name, default_rate) VALUES (:id, :name, 0.00)");
                    foreach ($predefined_items as $index => $item) {
                        $ins->execute([
                            ':id' => 'p_' . $index . '_' . uniqid(),
                            ':name' => $item
                        ]);
                    }
                }
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Auto Installer / Tables creator
function db_create_schema($pdo) {
    // 1. Settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        company_name VARCHAR(255) NOT NULL,
        logo_url VARCHAR(255) NULL,
        address TEXT NULL,
        gstin VARCHAR(50) NULL,
        pan VARCHAR(50) NULL,
        phone VARCHAR(255) NULL,
        email VARCHAR(255) NULL,
        website VARCHAR(255) NULL,
        bank_details TEXT NULL,
        upi_qr VARCHAR(255) NULL,
        terms TEXT NULL,
        footer TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(50) PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        default_rate DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Customers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        alt_mobile VARCHAR(20) NULL,
        email VARCHAR(255) NULL,
        address TEXT NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        pincode VARCHAR(20) NULL,
        gstin VARCHAR(50) NULL,
        aadhaar VARCHAR(50) NULL,
        pan VARCHAR(50) NULL,
        remarks TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Quotations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS quotations (
        id VARCHAR(50) PRIMARY KEY,
        quotation_number VARCHAR(50) NOT NULL,
        customer_id VARCHAR(50) NOT NULL,
        from_city VARCHAR(255) NULL,
        to_city VARCHAR(255) NULL,
        phone VARCHAR(20) NULL,
        quotation_date DATE NULL,
        packing_charge DECIMAL(10,2) DEFAULT 0.00,
        unpacking_charge DECIMAL(10,2) DEFAULT 0.00,
        loading_charge DECIMAL(10,2) DEFAULT 0.00,
        unloading_charge DECIMAL(10,2) DEFAULT 0.00,
        escort_charge DECIMAL(10,2) DEFAULT 0.00,
        storage_charge DECIMAL(10,2) DEFAULT 0.00,
        insurance_charge DECIMAL(10,2) DEFAULT 0.00,
        subtotal DECIMAL(10,2) DEFAULT 0.00,
        gst_rate DECIMAL(10,2) DEFAULT 0.00,
        gst_amount DECIMAL(10,2) DEFAULT 0.00,
        grand_total DECIMAL(10,2) DEFAULT 0.00,
        items TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Invoices table
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id VARCHAR(50) PRIMARY KEY,
        invoice_number VARCHAR(50) NOT NULL,
        invoice_date DATE NULL,
        customer_id VARCHAR(50) NOT NULL,
        from_city VARCHAR(255) NULL,
        to_city VARCHAR(255) NULL,
        vehicle_number VARCHAR(50) NULL,
        driver_name VARCHAR(100) NULL,
        freight_charge DECIMAL(10,2) DEFAULT 0.00,
        packing_charge DECIMAL(10,2) DEFAULT 0.00,
        loading_charge DECIMAL(10,2) DEFAULT 0.00,
        unloading_charge DECIMAL(10,2) DEFAULT 0.00,
        unpacking_charge DECIMAL(10,2) DEFAULT 0.00,
        escort_charge DECIMAL(10,2) DEFAULT 0.00,
        subtotal DECIMAL(10,2) DEFAULT 0.00,
        gst_type VARCHAR(50) DEFAULT 'freight_only',
        gst_rate DECIMAL(10,2) DEFAULT 0.00,
        cgst_amount DECIMAL(10,2) DEFAULT 0.00,
        sgst_amount DECIMAL(10,2) DEFAULT 0.00,
        igst_amount DECIMAL(10,2) DEFAULT 0.00,
        gst_amount DECIMAL(10,2) DEFAULT 0.00,
        grand_total DECIMAL(10,2) DEFAULT 0.00,
        amount_paid DECIMAL(10,2) DEFAULT 0.00,
        outstanding_balance DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'Draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 7. Payments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id VARCHAR(50) PRIMARY KEY,
        invoice_id VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        amount DECIMAL(10,2) DEFAULT 0.00,
        reference_number VARCHAR(100) NULL,
        payment_mode VARCHAR(50) NOT NULL,
        remarks TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 8. Audit logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id VARCHAR(50) PRIMARY KEY,
        action_type VARCHAR(255) NOT NULL,
        description TEXT NULL,
        timestamp DATETIME NULL,
        user_name VARCHAR(255) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 9. Lorry Receipts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS lorry_receipts (
        id VARCHAR(50) PRIMARY KEY,
        lr_no VARCHAR(50) UNIQUE NOT NULL,
        lr_date DATE NULL,
        invoice_id VARCHAR(50) NOT NULL,
        consignee_name VARCHAR(255) NOT NULL,
        consignee_mobile VARCHAR(20) NULL,
        consignee_gstin VARCHAR(50) NULL,
        delivery_address TEXT NULL,
        vehicle_number VARCHAR(50) NULL,
        driver_name VARCHAR(100) NULL,
        driver_mobile VARCHAR(20) NULL,
        driving_license VARCHAR(50) NULL,
        carrier_name VARCHAR(255) NULL,
        articles_count INT DEFAULT 0,
        description VARCHAR(255) NULL,
        actual_weight DECIMAL(10,2) DEFAULT 0.00,
        charged_weight DECIMAL(10,2) DEFAULT 0.00,
        value_of_goods DECIMAL(10,2) DEFAULT 0.00,
        freight_terms VARCHAR(50) DEFAULT 'To Pay',
        remarks TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert Default Settings
    $pdo->exec("INSERT INTO settings (
        company_name, logo_url, address, gstin, pan, phone, email, website, bank_details, terms, footer
    ) VALUES (
        'M/s. OM GUPTESWAR TRANSPORTER PACKERS & MOVERS',
        'assets/img/logo.svg',
        'AT- SEMILIGUDA, PIN - 764036, DAMANJODI CHOWK, DIST. KORAPUT (ODISHA)',
        '21EWHPS2961F1ZH',
        'EWHPS2961F',
        '7789052910, 8457952219, 7504663498',
        'dillipsethi796@gmail.com',
        'http://www.omgupteswarpackers.in',
        'Bank Name: State Bank of India\\nAccount No: 1234567890\\nIFSC: SBIN0001234\\nBranch: Semiliguda',
        '1. Minimum 15 ltr petrol/diesel should be available in the car (minimum) vehicle.\\n2. We do not undertake Electrical, Carpentry & Plumber Job.\\n3. We or our agent shall be exempted from any kind of loss damage done due to accident, pilferage, fire, rain, or any other natural calamity.\\n4. We request you to pay us 25% as advance on total amount.',
        'Thank you for your business. Welcome again!'
    );");

    // Insert Default Users
    $pdo->exec("INSERT INTO users (id, username, password, role, name) VALUES 
        ('u_admin', 'admin', 'admin123', 'Administrator', 'Deepak Kumar'),
        ('u_staff', 'staff', 'staff123', 'Staff', 'Staff Member');");

    // Insert Default Products catalog checklist items
    $predefined_items = [
        'Double Bed (Box Dism)', 'Deewan', 'Sofaset (3+1+1)(3+3+1)', 'Centre Table', 'Almirah (Small & Big)',
        'Television (Small) LCD/LED', 'T.V. Trolly', 'Fridge (165/280Ltr.)', 'Washing Machine', 'Air Conditioner',
        'Cooler (Small & Big)', 'Dressing Table', 'Computer', 'Computer Table', 'Writing Table',
        'Dining Table Glass/Wooden(4+1)', 'Bunk Bed', 'Side Table', 'Music System', 'Show Case (Small & Big)',
        'Wall Unit', 'Cabinet (Small & Big)', 'Single Cot', 'Folding Cot', 'Folding Chair',
        'Wooden Chair', 'Rocking Chair', 'Cane Set', 'Oven', 'Cooking Range', 'Aquaguard',
        'Rack (Shoes/Kitchen)', 'Crockery', 'Book Shelf', 'Books', 'Geyser', 'Gas Stove',
        'Temple', 'Matries, Flat/Fold', 'Fan', 'Vaccum Cleaner', 'Stabilizer', 'Sewing Machine',
        'Palmets/Steel Box', 'Carpet', 'Wetgrinder', 'Bean Bag', 'Toys', 'Photo Frame', 'Wall Frame',
        'Music System Trolly', 'Jhoola', 'Utensils', 'Steel Drums', 'Plastic Chair', 'Cloths',
        'Flower Posts', 'Inverter/Generator', 'Cycle (Baby & Other)', 'Brief-Case', 'Trunk',
        'Exercise Cycle', 'Scooter/Motorcycle', 'Car', 'Mice'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (id, name, default_rate) VALUES (:id, :name, 0.00)");
    foreach ($predefined_items as $index => $item) {
        $stmt->execute([
            ':id' => 'p_' . $index . '_' . uniqid(),
            ':name' => $item
        ]);
    }
}

// Get Table rows helper
function db_get_table($table) {
    $pdo = db_connect();
    
    if ($table === 'settings') {
        $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
        return $stmt->fetch() ?: [];
    }
    
    $stmt = $pdo->query("SELECT * FROM " . preg_replace('/[^a-zA-Z0-9_]/', '', $table));
    $rows = $stmt->fetchAll();
    
    // Transparently deserialize items inside quotations
    if ($table === 'quotations') {
        foreach ($rows as &$row) {
            if (isset($row['items'])) {
                $row['items'] = json_decode($row['items'], true) ?: [];
            }
        }
    }
    return $rows;
}

// Find single row helper
function db_find($table, $id) {
    $pdo = db_connect();
    
    if ($table === 'settings') {
        return db_get_table('settings');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM " . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . " WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    
    if ($row && $table === 'quotations' && isset($row['items'])) {
        $row['items'] = json_decode($row['items'], true) ?: [];
    }
    return $row ?: null;
}

// Insert single row helper
function db_insert($table, $data) {
    $pdo = db_connect();
    
    if (!isset($data['id']) && $table !== 'settings') {
        $data['id'] = uniqid();
    }
    
    // Automatically serialize arrays (e.g. items checklist)
    foreach ($data as $col => &$val) {
        if (is_array($val)) {
            $val = json_encode($val);
        }
    }
    unset($val);
    
    $columns = array_keys($data);
    $placeholders = array_map(function($c) { return ":$c"; }, $columns);
    
    $sql = "INSERT INTO " . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return isset($data['id']) ? $data['id'] : true;
}

// Update single row helper
function db_update($table, $id, $data) {
    $pdo = db_connect();
    
    // Remove ID if present in columns to update
    if (isset($data['id'])) {
        unset($data['id']);
    }
    
    if ($table === 'settings') {
        // Settings has only one row (no ID matching)
        $sets = [];
        foreach ($data as $col => $val) {
            $sets[] = "$col = :$col";
        }
        $sql = "UPDATE settings SET " . implode(', ', $sets);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return true;
    }
    
    // Automatically serialize arrays (e.g. items checklist)
    foreach ($data as $col => &$val) {
        if (is_array($val)) {
            $val = json_encode($val);
        }
    }
    unset($val);
    
    $sets = [];
    foreach ($data as $col => $val) {
        $sets[] = "$col = :$col";
    }
    
    $sql = "UPDATE " . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . " SET " . implode(', ', $sets) . " WHERE id = :matching_id";
    
    // Add matching ID parameter
    $data['matching_id'] = $id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return true;
}

// Delete single row helper
function db_delete($table, $id) {
    $pdo = db_connect();
    $stmt = $pdo->prepare("DELETE FROM " . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . " WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return true;
}

// Audit trail logging helper
function log_audit($action_type, $description) {
    $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System';
    db_insert('audit_log', [
        'action_type' => $action_type,
        'description' => $description,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_name' => $user_name
    ]);
}

// Convert numeric amount to words (Indian numbering format)
function amount_in_words($number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'one', 2 => 'two',
        3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve',
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
        16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty',
        70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
    );
    $digits = array('', 'hundred','thousand','lakh', 'crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "and " . ($words[floor($decimal / 10) * 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ucwords(trim(($Rupees ? $Rupees . 'Rupees ' : '') . $paise)) . ' Only';
}

// Run initial connect to check and setup db structure on file load
db_connect();
