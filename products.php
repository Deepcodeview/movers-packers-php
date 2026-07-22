<?php
$page_title = 'Item Catalog';
$active_menu = 'products'; // Keep under transit & billing navigation
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';


$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $default_rate = (float)$_POST['default_rate'];

        if (empty($name)) {
            $error = 'Item Name cannot be empty!';
            $action = empty($id) ? 'new' : 'edit';
        } else {
            $item_data = [
                'name' => $name,
                'default_rate' => $default_rate
            ];

            if (empty($id)) {
                db_insert('products', $item_data);
                log_audit('Item Catalog Add', 'Added new moving item: ' . $name);
                $success = 'Moving item added successfully!';
            } else {
                db_update('products', $id, $item_data);
                log_audit('Item Catalog Update', 'Updated item details: ' . $name);
                $success = 'Moving item details updated!';
            }
            $action = 'list';
        }
    }
} elseif ($action === 'delete' && !empty($id)) {
    $item = db_find('products', $id);
    if ($item) {
        db_delete('products', $id);
        log_audit('Item Catalog Delete', 'Deleted moving item: ' . $item['name']);
        $success = 'Item deleted successfully!';
    }
    $action = 'list';
}

$products = db_get_table('products');
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

        <!-- ACTION: LIST ITEMS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">Item Catalog (Moving Products)</h4>
                    <p class="text-muted fs-14 mb-0">Configure the predefined checklist items selection for generating transit quotations.</p>
                </div>
                <div>
                    <a href="products.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-plus me-2"></i>Add New Item
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Default Valuation Rate (₹)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($p['name']); ?></span></td>
                                        <td>₹<?php echo number_format($p['default_rate'], 2); ?></td>
                                        <td>
                                            <a href="products.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-edit"></i></a>
                                            <a href="products.php?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this catalog item?')"><i class="ti ti-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: CREATE/EDIT ITEM -->
        <?php elseif ($action === 'new' || $action === 'edit'):
            $item = [];
            if ($action === 'edit') {
                $item = db_find('products', $id);
            }
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0"><?php echo $action === 'new' ? 'Add Item' : 'Edit Item'; ?></h4>
                    <p class="text-muted fs-14 mb-0">Define catalog item characteristics.</p>
                </div>
                <div>
                    <a href="products.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="products.php?action=save<?php echo !empty($id) ? '&id=' . $id : ''; ?>" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Item / Product Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Dining Table (Glass)" value="<?php echo htmlspecialchars(isset($item['name']) ? $item['name'] : ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Default Valuation Rate (₹)</label>
                                <input type="number" name="default_rate" step="0.01" class="form-control" value="<?php echo htmlspecialchars(isset($item['default_rate']) ? $item['default_rate'] : '0.00'); ?>">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save Item</button>
                            <a href="products.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
