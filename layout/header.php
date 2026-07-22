<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../db.php';
$settings = db_get_table('settings');

// Check user login (role management)
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> | Packers & Movers CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Advanced Packers & Movers CRM with Invoicing, Quotations, and GST Reports">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.png">
    
    <!-- Theme Config Js -->
    <script src="assets/js/theme-script.js"></script>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    
    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
    
    <!-- Simplebar CSS -->
    <link rel="stylesheet" href="assets/plugins/simplebar/simplebar.min.css">
    
    <!-- Datatable CSS -->
    <link rel="stylesheet" href="assets/plugins/datatables/css/dataTables.bootstrap5.min.css">
    
    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" href="assets/plugins/daterangepicker/daterangepicker.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css" id="app-style">
    
    <!-- Custom Style tweaks for printing and layout -->
    <style>
        .sidebar-logo a img {
            max-height: 40px;
        }
        @media print {
            .sidebar, .navbar-header, .btn-icon, .btn-primary, .btn-outline-light, .footer, .header-collapse, .no-print {
                display: none !important;
            }
            .page-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .content {
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
        }
        
        /* Sitewide Form, Card, Spacing and Layout Improvements */
        .form-control, .form-select {
            padding: 0.6rem 0.9rem !important;
            border-radius: 6px !important;
            border: 1px solid #E2E8F0 !important;
            font-size: 14px !important;
            transition: all 0.2s ease-in-out !important;
        }
        .form-select {
            padding-right: 2.25rem !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
        }
        .form-label {
            margin-bottom: 0.4rem !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #454646 !important;
            text-transform: capitalize !important;
        }
        .card {
            margin-bottom: 1.5rem !important;
            border-radius: 8px !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important;
        }
        .card-body {
            padding: 1.5rem !important;
        }
        .mb-3 {
            margin-bottom: 1.25rem !important;
        }
        .row {
            --bs-gutter-x: 1.5rem !important;
            --bs-gutter-y: 0.5rem !important;
        }
        .btn {
            padding: 0.55rem 1.25rem !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
        }
        .btn-icon {
            padding: 0.4rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 32px !important;
            height: 32px !important;
        }
        .table th {
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 12px !important;
            letter-spacing: 0.5px !important;
            background-color: #F8FAFB !important;
        }
        .table td {
            font-size: 13.5px !important;
            vertical-align: middle !important;
        }
        .badge {
            padding: 0.4em 0.7em !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            border-radius: 4px !important;
        }

        /* 1. Global Search Icon overlap fix */
        .header-search .input-icon {
            display: flex !important;
            align-items: center !important;
        }
        .header-search .form-control {
            padding-right: 2.8rem !important;
            padding-left: 1rem !important;
        }
        .header-search .input-icon-addon {
            position: absolute !important;
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 5 !important;
            pointer-events: none !important;
            color: #888 !important;
        }

        /* 2. Sidebar collapsibility / minimization responsiveness layout fix */
        @media (min-width: 768px) {
            body.mini-sidebar {
                --sidenav-width: 80px !important;
            }
            body.mini-sidebar .sidebar {
                width: 80px !important;
            }
            body.mini-sidebar .page-wrapper {
                margin-left: 80px !important;
            }
            body.mini-sidebar .sidebar .sidebar-inner {
                width: 80px !important;
            }
            body.mini-sidebar .sidebar .menu-title,
            body.mini-sidebar .sidebar span,
            body.mini-sidebar .sidebar .submenu {
                display: none !important;
            }
            body.mini-sidebar .sidebar i {
                margin-right: 0 !important;
                font-size: 20px !important;
                text-align: center !important;
                display: block !important;
            }
            body.mini-sidebar .logo .logo-lg {
                display: none !important;
            }
            body.mini-sidebar .logo .logo-sm {
                display: block !important;
            }
        }

        /* 3. Hide Theme Customizer Icon and panel completely */
        .sidebar-contact, 
        .sidebar-themesettings, 
        #theme-settings-offcanvas, 
        .toggle-theme {
            display: none !important;
            visibility: hidden !important;
        }

        /* 4. Phone Responsiveness layout overrides */
        @media (max-width: 767.98px) {
            .sidebar {
                margin-left: -260px !important;
                transition: all 0.3s ease-in-out !important;
                width: 260px !important;
                z-index: 1045 !important;
                position: fixed !important;
                top: 60px !important;
                bottom: 0 !important;
                display: block !important;
                background: #fff !important;
            }
            .main-wrapper.slide-nav .sidebar {
                margin-left: 0 !important;
            }
            .page-wrapper {
                margin-left: 0 !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
                transition: all 0.3s ease-in-out !important;
            }
            .sidebar-overlay {
                display: none;
            }
            .sidebar-overlay.opened {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.4);
                z-index: 1040;
            }
            .topbar-menu {
                padding: 0 10px !important;
            }
            .header-search {
                display: none !important;
            }
            .navbar-header {
                padding: 0 10px !important;
            }
        }
        @media (max-width: 575.98px) {
            .content {
                padding: 15px 8px !important;
            }
            .card-body {
                padding: 1rem !important;
            }
            .content .btn:not(.btn-icon):not(.btn-sm) {
                width: 100% !important;
                margin-bottom: 8px !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .d-flex.align-items-center.justify-content-between {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            .d-flex.align-items-center.justify-content-between > div {
                width: 100% !important;
            }
            .table-responsive {
                border: 0 !important;
            }
        }

        /* Sticky Bottom Navigation Bar for Mobile view (Premium Glassmorphism feel) */
        .mobile-bottom-nav {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 64px !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border-top: 1px solid #E2E8F0 !important;
            display: flex !important;
            justify-content: space-around !important;
            align-items: center !important;
            z-index: 1040 !important;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.03) !important;
            padding-bottom: env(safe-area-inset-bottom) !important;
        }
        .mobile-nav-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            color: #718096 !important;
            text-decoration: none !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            flex-grow: 1 !important;
            height: 100% !important;
            transition: all 0.2s ease !important;
        }
        .mobile-nav-item i {
            font-size: 22px !important;
            margin-bottom: 2px !important;
            transition: transform 0.2s ease !important;
        }
        .mobile-nav-item.active {
            color: var(--primary) !important;
        }
        .mobile-nav-item.active i {
            transform: scale(1.15) !important;
        }
        .mobile-nav-item:active {
            background-color: rgba(var(--primary-rgb), 0.05) !important;
        }

        /* Fixed Top Header for Mobile view (Minimalist Clean White style) */
        @media (max-width: 767.98px) {
            .navbar-header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1030 !important;
                height: 56px !important;
                background-color: #ffffff !important;
                border-bottom: 1px solid #E2E8F0 !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
            }
            /* Make header relative to allow absolute positioning of direct sub-elements */
            .navbar-header .topbar-menu {
                position: relative !important;
                width: 100% !important;
                height: 100% !important;
                display: block !important;
                padding: 0 !important;
            }
            #toggle_btn2, #light-dark-mode, .header-line, .header-search {
                display: none !important;
            }
            /* Left aligned menu button */
            #mobile_btn {
                position: absolute !important;
                left: 16px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                z-index: 10 !important;
                color: #1E293B !important; /* dark slate toggle */
                display: inline-flex !important;
                margin: 0 !important;
            }
            /* Dead center logo */
            .navbar-header .logo {
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                z-index: 5 !important;
                margin: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .navbar-header .logo span {
                font-size: 16px !important;
                font-weight: 700 !important;
                color: #1E293B !important; /* dark slate brand name */
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
            }
            .navbar-header .logo span i {
                color: var(--primary) !important; /* primary color delivery truck icon */
                font-size: 18px !important;
            }
            /* Hide the logo icon specifically on smaller phone screens to avoid overlap */
            @media (max-width: 575.98px) {
                .logo-icon {
                    display: none !important;
                }
            }
            /* Right aligned profile dropdown */
            .navbar-header .dropdown.profile-dropdown {
                position: absolute !important;
                right: 16px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                z-index: 10 !important;
                margin: 0 !important;
                display: inline-flex !important;
            }
            .profile-dropdown img {
                width: 32px !important;
                height: 32px !important;
                border: 1px solid #E2E8F0 !important;
            }
            /* Force profile dropdown menu alignment to the left on mobile to avoid cutting off */
            .navbar-header .profile-dropdown .dropdown-menu {
                right: 0 !important;
                left: auto !important;
                transform: none !important;
                top: 40px !important;
                margin-top: 0 !important;
            }
            .page-wrapper {
                margin-top: 56px !important;
                padding-bottom: 80px !important;
                transition: all 0.3s ease-in-out !important;
                background-color: #F8FAFC !important;
            }
            footer.footer {
                display: none !important;
            }
            
            /* Hide main page titles and subtexts to look like clean app layout */
            .page-wrapper .content > div:first-of-type h4,
            .page-wrapper .content > div:first-of-type p.text-muted,
            .page-wrapper .content > .d-flex.justify-content-between.mb-4 h4,
            .page-wrapper .content > .d-flex.justify-content-between.mb-4 p,
            .page-wrapper .content > .d-flex.align-items-center.justify-content-between.mb-4 h4,
            .page-wrapper .content > .d-flex.align-items-center.justify-content-between.mb-4 p {
                display: none !important;
            }
            .page-wrapper .content > div:first-of-type.d-flex,
            .page-wrapper .content > .d-flex.justify-content-between.mb-4,
            .page-wrapper .content > .d-flex.align-items-center.justify-content-between.mb-4 {
                margin-bottom: 0.5rem !important;
            }

            /* Make Tables look like modern iOS list cards instead of simple columns */
            .table-responsive {
                border: none !important;
            }
            .table {
                border-collapse: separate !important;
                border-spacing: 0 10px !important;
                background: transparent !important;
                margin-top: 0 !important;
            }
            .table tbody tr {
                background: #ffffff !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02), 0 2px 4px rgba(0, 0, 0, 0.02) !important;
                border-radius: 12px !important;
                border: 1px solid #E2E8F0 !important;
                transition: transform 0.2s ease !important;
            }
            .table tbody tr:active {
                transform: scale(0.98) !important;
            }
            .table th {
                border: none !important;
                font-size: 11px !important;
                color: #718096 !important;
                text-transform: uppercase !important;
                padding-bottom: 2px !important;
            }
            .table td {
                border: none !important;
                padding: 14px 12px !important;
                vertical-align: middle !important;
            }
            .table tr td:first-child {
                border-top-left-radius: 12px !important;
                border-bottom-left-radius: 12px !important;
            }
            .table tr td:last-child {
                border-top-right-radius: 12px !important;
                border-bottom-right-radius: 12px !important;
            }

            /* Premium rounded cards and form adjustments */
            .card {
                border-radius: 16px !important;
                border: 1px solid #F1F5F9 !important;
                box-shadow: 0 8px 30px rgba(0,0,0,0.02) !important;
                margin-bottom: 16px !important;
            }
            .card-body {
                padding: 16px !important;
            }
            .form-control, .form-select {
                height: 46px !important;
                border-radius: 10px !important;
                font-size: 15px !important;
                background-color: #F8FAFC !important;
                border-color: #E2E8F0 !important;
            }
            .form-control:focus, .form-select:focus {
                background-color: #ffffff !important;
            }
            .form-label {
                font-size: 12px !important;
                color: #4A5568 !important;
                margin-bottom: 6px !important;
            }
            .btn {
                height: 44px !important;
                border-radius: 10px !important;
                font-size: 15px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
        }

        /* Hide sidebar collapse/toggle buttons globally to keep sidebar open and remove collapse icons */
        #toggle_btn, #toggle_btn2 {
            display: none !important;
            visibility: hidden !important;
        }

        /* Premium Desktop Header Styling */
        @media (min-width: 768px) {
            .navbar-header {
                background-color: #ffffff !important; /* Premium white background */
                border-bottom: 1px solid #E2E8F0 !important; /* Clean divider line */
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02) !important;
                height: 60px !important;
            }
            /* Hide the duplicate logo inside the topbar header on desktop */
            .navbar-header .logo {
                display: none !important;
            }
            /* Style search bar to look extremely premium */
            .header-search input.form-control {
                background-color: #F8FAFC !important;
                border: 1px solid #E2E8F0 !important;
                border-radius: 8px !important;
                padding-left: 38px !important;
                height: 38px !important;
                font-size: 13px !important;
                width: 240px !important;
                transition: all 0.2s ease-in-out !important;
            }
            .header-search input.form-control:focus {
                background-color: #ffffff !important;
                width: 290px !important; /* Expand search bar on focus, very premium! */
                border-color: var(--primary) !important;
                box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1) !important;
            }
            .header-search-icon {
                color: #718096 !important;
                position: absolute !important;
                left: 12px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                z-index: 5 !important;
                font-size: 14px !important;
            }
            /* Clean up desktop side menu toggler and other header items */
            .sidenav-toggle-btn {
                color: #4A5568 !important;
            }
            .sidenav-toggle-btn:hover {
                color: var(--primary) !important;
                background-color: #F8FAFC !important;
            }
            .topbar-link {
                color: #4A5568 !important;
            }
            .topbar-link:hover {
                color: var(--primary) !important;
            }
            .header-line {
                background-color: #E2E8F0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Topbar Start -->
        <header class="navbar-header">
            <div class="page-container topbar-menu">
                <div class="d-flex align-items-center gap-2">
                    <!-- Logo -->
                    <a href="index.php" class="logo text-decoration-none d-inline-flex align-items-center">
                        <span class="fs-16 fw-bold text-white letter-spacing-05 d-inline-flex align-items-center">
                            <i class="ti ti-truck-delivery me-2 fs-18 logo-icon"></i>OM GUPTESWAR
                        </span>
                    </a>

                    <!-- Sidebar Mobile Button -->
                    <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                        <i class="ti ti-menu-deep fs-24"></i>
                    </a>

                    <button class="sidenav-toggle-btn btn border-0 p-0" id="toggle_btn2">
                        <i class="ti ti-arrow-bar-to-right"></i>
                    </button>
                    
                    <!-- Global Search -->
                    <form action="customers.php" method="GET" class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                        <div class="input-icon position-relative me-2">
                            <input type="text" name="search" class="form-control" placeholder="Search Customer, Mobile, Invoice...">
                            <span class="input-icon-addon d-inline-flex p-0 header-search-icon"><i class="ti ti-search"></i></span>
                        </div>
                    </form>
                </div>

                <div class="d-flex align-items-center">
                    <!-- Light/Dark Mode Button -->
                    <div class="header-item d-none d-sm-flex me-2">
                        <button class="topbar-link btn" id="light-dark-mode" type="button">
                            <i class="ti ti-moon fs-16"></i>
                        </button>
                    </div>

                    <div class="header-line"></div>

                    <!-- User Dropdown -->
                    <div class="dropdown profile-dropdown d-flex align-items-center justify-content-center">
                        <a href="javascript:void(0);" class="topbar-link dropdown-toggle drop-arrow-none position-relative" data-bs-toggle="dropdown">
                            <img src="assets/img/users/user-40.jpg" width="38" class="rounded-1 d-flex" alt="user-image">
                            <span class="online text-success"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                            <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                                <img src="assets/img/users/user-40.jpg" class="rounded-circle" width="42" height="42" alt="Img">
                                <div class="ms-2">
                                    <p class="fw-medium text-dark mb-0"><?php echo $_SESSION['user_name']; ?></p>
                                    <span class="d-block fs-13"><?php echo $_SESSION['user_role']; ?></span>
                                </div>
                            </div>
                            <a href="settings.php" class="dropdown-item">
                                <i class="ti ti-settings me-1 align-middle"></i>
                                <span class="align-middle">Company Settings</span>
                            </a>
                            <div class="pt-2 mt-2 border-top">
                                <a href="logout.php" class="dropdown-item text-danger">
                                    <i class="ti ti-logout me-1 align-middle"></i>
                                    <span class="align-middle">Sign Out</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Topbar End -->

        <!-- Mobile Sticky Bottom Navigation (Native App feel) -->
        <div class="mobile-bottom-nav d-md-none no-print">
            <a href="index.php" class="mobile-nav-item <?php echo isset($active_menu) && $active_menu == 'dashboard' ? 'active' : ''; ?>">
                <i class="ti ti-smart-home"></i>
                <span>Home</span>
            </a>
            <a href="customers.php" class="mobile-nav-item <?php echo isset($active_menu) && $active_menu == 'customers' ? 'active' : ''; ?>">
                <i class="ti ti-users"></i>
                <span>Customers</span>
            </a>
            <a href="quotations.php" class="mobile-nav-item <?php echo isset($active_menu) && $active_menu == 'quotations' ? 'active' : ''; ?>">
                <i class="ti ti-file-report"></i>
                <span>Quotes</span>
            </a>
            <a href="invoices.php" class="mobile-nav-item <?php echo isset($active_menu) && $active_menu == 'invoices' ? 'active' : ''; ?>">
                <i class="ti ti-file-invoice"></i>
                <span>Invoices</span>
            </a>
            <a href="lorry_receipts.php" class="mobile-nav-item <?php echo isset($active_menu) && $active_menu == 'lorry_receipt' ? 'active' : ''; ?>">
                <i class="ti ti-truck-delivery"></i>
                <span>Bilty</span>
            </a>
        </div>
