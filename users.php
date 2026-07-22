<?php
$page_title = 'User Management';
$active_menu = 'settings'; // Placed under settings
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

// Access Control: Only Administrator can manage users
if ($_SESSION['user_role'] !== 'Administrator') {
    echo '<div class="page-wrapper"><div class="content"><div class="alert alert-danger"><i class="ti ti-alert-triangle me-2"></i>Access Denied: You do not have permissions to view this page.</div></div></div>';
    require_once __DIR__ . '/layout/footer.php';
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $name = trim($_POST['name']);
        $role = $_POST['role'];

        if (empty($username) || empty($password) || empty($name)) {
            $error = 'All fields are required!';
            $action = empty($id) ? 'new' : 'edit';
        } else {
            // Check if username already exists to prevent SQL Unique Constraint violation
            $all_users = db_get_table('users');
            $username_exists = false;
            foreach ($all_users as $usr) {
                if ($usr['username'] === $username && $usr['id'] !== $id) {
                    $username_exists = true;
                    break;
                }
            }

            if ($username_exists) {
                $error = 'The username "' . htmlspecialchars($username) . '" is already taken! Please choose a different username.';
                $action = empty($id) ? 'new' : 'edit';
            } else {
                $user_data = [
                    'username' => $username,
                    'password' => $password,
                    'name' => $name,
                    'role' => $role
                ];

                if (empty($id)) {
                    db_insert('users', $user_data);
                    log_audit('User Accounts Created', 'Created user account: ' . $username . ' (' . $role . ')');
                    $success = 'User account created successfully!';
                } else {
                    db_update('users', $id, $user_data);
                    log_audit('User Accounts Updated', 'Updated user account: ' . $username . ' (' . $role . ')');
                    $success = 'User account details updated!';
                }
                $action = 'list';
            }
        }
    }
} elseif ($action === 'delete' && !empty($id)) {
    if ($id === $_SESSION['user_id']) {
        $error = 'You cannot delete your own logged-in user account!';
    } else {
        $u = db_find('users', $id);
        if ($u) {
            db_delete('users', $id);
            log_audit('User Accounts Deleted', 'Deleted user account: ' . $u['username']);
            $success = 'User account deleted successfully!';
        }
    }
    $action = 'list';
}

$users = db_get_table('users');
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

        <!-- ACTION: LIST USERS -->
        <?php if ($action === 'list'): ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0">User & Role Management</h4>
                    <p class="text-muted fs-14 mb-0">Manage credentials and roles (Administrator vs. Staff permissions) for system users.</p>
                </div>
                <div>
                    <a href="users.php?action=new" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-user-plus me-2"></i>Create New User
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover dataTable table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Password (Plain text)</th>
                                    <th>Role Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($u['name']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                                        <td><code><?php echo htmlspecialchars($u['password']); ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['role'] === 'Administrator' ? 'danger' : 'info'; ?>">
                                                <?php echo htmlspecialchars($u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="tooltip" title="Edit"><i class="ti ti-edit"></i></a>
                                            <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')"><i class="ti ti-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ACTION: CREATE/EDIT USER -->
        <?php elseif ($action === 'new' || $action === 'edit'):
            $u = [];
            if ($action === 'edit') {
                $u = db_find('users', $id);
            }
            ?>
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                <div>
                    <h4 class="mb-0"><?php echo $action === 'new' ? 'Create User' : 'Edit User'; ?></h4>
                    <p class="text-muted fs-14 mb-0">Set up login credentials and configure system roles.</p>
                </div>
                <div>
                    <a href="users.php" class="btn btn-light">Back to List</a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="users.php?action=save<?php echo !empty($id) ? '&id=' . $id : ''; ?>" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Full Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Rakesh Kumar" value="<?php echo htmlspecialchars(isset($u['name']) ? $u['name'] : ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Role *</label>
                                <select name="role" class="form-select" required>
                                    <option value="Staff" <?php echo (isset($u['role']) && $u['role'] === 'Staff') ? 'selected' : ''; ?>>Staff (Limited Access)</option>
                                    <option value="Administrator" <?php echo (isset($u['role']) && $u['role'] === 'Administrator') ? 'selected' : ''; ?>>Administrator (Full Access)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Username *</label>
                                <input type="text" name="username" class="form-control" placeholder="Login username" value="<?php echo htmlspecialchars(isset($u['username']) ? $u['username'] : ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Password *</label>
                                <input type="text" name="password" class="form-control" placeholder="Login password" value="<?php echo htmlspecialchars(isset($u['password']) ? $u['password'] : ''); ?>" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save User Details</button>
                            <a href="users.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
