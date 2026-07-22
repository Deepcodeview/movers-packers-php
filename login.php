<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $users = db_get_table('users');
    $authenticated = false;

    foreach ($users as $u) {
        if ($u['username'] === $username && $u['password'] === $password) {
            $_SESSION['user_role'] = $u['role'];
            $_SESSION['user_name'] = $u['name'];
            $_SESSION['user_id'] = $u['id'];
            
            log_audit('User Logged In', 'User ' . $username . ' logged in successfully');
            $authenticated = true;
            header("Location: index.php");
            exit;
        }
    }

    if (!$authenticated) {
        $error = 'Invalid Username or Password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login | OM GUPTESWAR CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/img/favicon.png">
    
    <!-- Google Fonts - Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    
    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
    
    <!-- Custom Premium Login Styles -->
    <style>
        :root {
            --primary: #FF5E3A;
            --primary-rgb: 255, 94, 58;
            --dark-slate: #0F172A;
            --bg-color: #F8FAFC;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(241, 245, 249, 0.6) 0%, rgba(255, 255, 255, 1) 90%);
            color: var(--dark-slate);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            overflow-x: hidden;
            position: relative;
        }

        /* Decorative background shapes for high-end look */
        .bg-shape-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(var(--primary-rgb), 0.03);
            filter: blur(80px);
            z-index: 1;
        }
        .bg-shape-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(14, 165, 233, 0.03);
            filter: blur(80px);
            z-index: 1;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #E2E8F0;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.02);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            z-index: 10;
            position: relative;
        }

        .login-logo {
            background: rgba(var(--primary-rgb), 0.08);
            color: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 16px -4px rgba(var(--primary-rgb), 0.2);
        }

        .login-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--dark-slate);
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13.5px;
            color: #64748B;
            margin-bottom: 2rem;
        }

        .form-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-group {
            border-radius: 10px;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            transition: all 0.2s ease-in-out;
            overflow: hidden;
        }
        .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
            background-color: #ffffff;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #94A3B8;
            padding-left: 14px;
            padding-right: 8px;
        }

        .form-control {
            height: 46px;
            border: none;
            background: transparent;
            font-size: 14px;
            color: var(--dark-slate);
            font-weight: 500;
            padding-left: 6px;
        }
        .form-control:focus {
            background: transparent;
            box-shadow: none;
            outline: none;
        }
        .form-control::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }

        .btn-login {
            height: 48px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            background: var(--primary);
            border: none;
            color: #ffffff;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px -6px rgba(var(--primary-rgb), 0.4);
        }
        .btn-login:hover {
            background: #e04f2e;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -4px rgba(var(--primary-rgb), 0.5);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        .alert-danger {
            background-color: #FEF2F2;
            border-color: #FEE2E2;
            color: #991B1B;
            border-radius: 10px;
            font-size: 13px;
            padding: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Decorative background elements -->
    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>

    <div class="login-card">
        <div class="text-center">
            <div class="login-logo">
                <i class="ti ti-truck-delivery fs-32"></i>
            </div>
            <h4 class="login-title">OM GUPTESWAR</h4>
            <p class="login-subtitle">Packers & Movers CRM Portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-triangle me-2 fs-16"></i>
                <div><?php echo $error; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="padding: 12px;"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-user fs-18"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autocomplete="username">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-lock fs-18"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
                </div>
            </div>
            
            <div class="mb-2">
                <button type="submit" class="btn btn-login">Sign In</button>
            </div>
        </form>
    </div>
    
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
