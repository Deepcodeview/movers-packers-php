        <!-- Start Footer -->
        <footer class="footer text-center no-print py-3 border-top bg-white">
            <p class="mb-1 text-muted">Copyright &copy; <?php echo date('Y'); ?> <strong class="text-primary"><?php echo htmlspecialchars($settings['company_name']); ?></strong>. All rights reserved.</p>
            <div class="d-flex align-items-center gap-3 justify-content-center footer-links mt-1">
                <a href="settings.php" class="text-secondary"><i class="ti ti-settings me-1"></i>Settings</a>
                <span class="text-muted">|</span>
                <a href="backup.php" class="text-secondary"><i class="ti ti-database me-1"></i>Backup</a>
            </div>
        </footer>
        <!-- End Footer -->
    </div>
    <!-- End Wrapper -->

    <!-- jQuery -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Core JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <!-- Simplebar JS -->
    <script src="assets/plugins/simplebar/simplebar.min.js"></script>

    <!-- Datatable JS -->
    <script src="assets/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="assets/plugins/datatables/js/dataTables.bootstrap5.min.js"></script>

    <!-- Daterangepicker JS -->
    <script src="assets/js/moment.min.js"></script>
    <script src="assets/plugins/daterangepicker/daterangepicker.js"></script>

    <!-- Main JS -->
    <script src="assets/js/script.js"></script>

    <!-- Custom general scripts for search/filters/utilities -->
    <script>
        $(document).ready(function() {
            // Apply Datatables on elements with .dataTable class
            if ($('.dataTable').length > 0) {
                $('.dataTable').DataTable({
                    "bFilter": true,
                    "order": [],
                    "language": {
                        search: ' ',
                        searchPlaceholder: "Search records...",
                        paginate: {
                            previous: '<i class="ti ti-chevron-left"></i>',
                            next: '<i class="ti ti-chevron-right"></i>'
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
