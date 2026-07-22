<!-- Sidenav Menu Start -->
<div class="sidebar" id="sidebar">
    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <!-- Logo Normal -->
            <a href="index.php" class="logo logo-normal text-decoration-none">
                <span class="fs-15 fw-bold text-dark"><i class="ti ti-truck-delivery me-1 fs-18"></i>OM GUPTESWAR</span>
            </a>
            <!-- Logo Small -->
            <a href="index.php" class="logo-small text-decoration-none">
                <span class="fs-15 fw-bold text-dark"><i class="ti ti-truck-delivery fs-18"></i></span>
            </a>
            <!-- Logo Dark -->
            <a href="index.php" class="dark-logo text-decoration-none">
                <span class="fs-15 fw-bold text-white"><i class="ti ti-truck-delivery me-1 fs-18"></i>OM GUPTESWAR</span>
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-bar-to-left"></i>
        </button>
        <!-- Sidebar Menu Close -->
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main Menu</span></li>
                <li>
                    <ul>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'dashboard' ? 'active' : ''; ?>">
                            <a href="index.php"><i class="ti ti-dashboard"></i><span>Dashboard</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'customers' ? 'active' : ''; ?>">
                            <a href="customers.php"><i class="ti ti-users"></i><span>Customer Master</span></a>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>Transit & Billing</span></li>
                <li>
                    <ul>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'quotations' ? 'active' : ''; ?>">
                            <a href="quotations.php"><i class="ti ti-file-report"></i><span>Quotations & Items</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'products' ? 'active' : ''; ?>">
                            <a href="products.php"><i class="ti ti-box"></i><span>Item Catalog</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'invoices' ? 'active' : ''; ?>">
                            <a href="invoices.php"><i class="ti ti-file-invoice"></i><span>GST Invoices</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'lorry_receipt' ? 'active' : ''; ?>">
                            <a href="lorry_receipts.php"><i class="ti ti-truck-delivery"></i><span>Lorry Receipts (LR)</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'payments' ? 'active' : ''; ?>">
                            <a href="payments.php"><i class="ti ti-report-money"></i><span>Payments Ledger</span></a>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>Analytics & Reports</span></li>
                <li>
                    <ul>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'reports' ? 'active' : ''; ?>">
                            <a href="reports.php"><i class="ti ti-chart-bar"></i><span>Business Reports</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'gst_audit' ? 'active' : ''; ?>">
                            <a href="gst_audit.php"><i class="ti ti-receipt-tax"></i><span>GST Return Audit</span></a>
                        </li>

                    </ul>
                </li>

                <li class="menu-title"><span>Settings & Admin</span></li>
                <li>
                    <ul>
                        <?php if ($_SESSION['user_role'] === 'Administrator'): ?>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'settings' ? 'active' : ''; ?>">
                            <a href="settings.php"><i class="ti ti-settings-cog"></i><span>Company Settings</span></a>
                        </li>
                        <li class="<?php echo isset($active_menu) && $active_menu == 'users' ? 'active' : ''; ?>">
                            <a href="users.php"><i class="ti ti-users-group"></i><span>User Management</span></a>
                        </li>

                        <?php endif; ?>
                        <li>
                            <a href="logout.php" class="text-danger"><i class="ti ti-logout"></i><span>Sign Out</span></a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Sidenav Menu End -->
