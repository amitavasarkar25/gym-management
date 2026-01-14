<?php
session_start();
include 'config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$msg = "";
$msg_type = "";

// 2. HANDLE ACTIONS

// A. Change Password Logic
if (isset($_POST['change_pass'])) {
    $curr_pass = $_POST['curr_pass'];
    $new_pass = $_POST['new_pass'];
    $conf_pass = $_POST['conf_pass'];
    
    // Check current admin (assuming single admin for simplicity, or fetch by ID)
    // For this project, we check the generic 'admin' user or the logged in session user
    $sql = "SELECT * FROM admins WHERE username='admin'"; 
    $result = $conn->query($sql);
    
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if ($curr_pass == $row['password']) {
            if ($new_pass == $conf_pass) {
                $conn->query("UPDATE admins SET password='$new_pass' WHERE username='admin'");
                $msg = "Password Updated Successfully!";
                $msg_type = "success";
            } else {
                $msg = "New passwords do not match!";
                $msg_type = "error";
            }
        } else {
            $msg = "Current password is incorrect!";
            $msg_type = "error";
        }
    }
}

// B. Add New Admin Logic
if (isset($_POST['add_admin'])) {
    $new_user = $_POST['admin_user'];
    $new_pwd = $_POST['admin_pass'];

    // Check if exists
    $check = $conn->query("SELECT * FROM admins WHERE username='$new_user'");
    if($check->num_rows > 0) {
        $msg = "Username already exists!";
        $msg_type = "error";
    } else {
        $conn->query("INSERT INTO admins (username, password) VALUES ('$new_user', '$new_pwd')");
        $msg = "New Admin Added Successfully!";
        $msg_type = "success";
    }
}

// C. Get Server IP (For QR Code Helper)
$serverIP = getHostByName(getHostName());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Settings - MELE FITNESS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse Admin Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        :root { --sidebar-width: 250px; --main-color: #ff4500; --dark-bg: #1d2231; --light-bg: #f1f5f9; }
        body { display: flex; background: var(--light-bg); }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--dark-bg); color: white; position: fixed; padding: 20px; z-index: 100; }
        .logo { font-size: 22px; font-weight: bold; color: var(--main-color); margin-bottom: 40px; display: flex; align-items: center; gap: 10px; }
        .menu a { display: block; color: #ccc; padding: 15px; text-decoration: none; margin-bottom: 5px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background: var(--main-color); color: white; }
        .menu i { margin-right: 10px; width: 20px; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 30px; width: 100%; }
        
        h2 { margin-bottom: 20px; color: #333; }

        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Auto-responsive columns */
            gap: 30px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 4px solid var(--main-color);
        }

        .card h3 { margin-bottom: 20px; color: #444; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        /* Forms */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; font-size: 0.9rem; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        input:focus { border-color: var(--main-color); }

        button {
            padding: 10px 20px;
            background: var(--main-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 10px;
            width: 100%;
        }
        button:hover { background: #e03e00; }

        /* Messages */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; color: white; }
        .success { background: #28a745; }
        .error { background: #dc3545; }

        /* Info Badge */
        .ip-badge { background: #e3f2fd; color: #0d47a1; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-family: monospace; }

        /* --- MOBILE RESPONSIVE FIX --- */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding: 10px; }
            .menu { display: flex; overflow-x: auto; padding-bottom: 10px; }
            .menu a { margin-right: 10px; margin-bottom: 0; white-space: nowrap; font-size: 14px; padding: 10px; }
            .main-content { margin-left: 0; padding: 20px; }
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-dumbbell"></i> MELE FITNESS</div>
        <div class="menu">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_qr_station.php"><i class="fas fa-qrcode"></i> QR Station</a>
            <a href="members_list.php"><i class="fas fa-users"></i> Members List</a>
            <a href="manage_trainers.php"><i class="fas fa-user-ninja"></i> Manage Trainers</a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" style="margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h2>System Settings</h2>

        <?php if($msg): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">
            
            <div class="card">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="curr_pass" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_pass" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="conf_pass" required>
                    </div>
                    <button type="submit" name="change_pass">Update Password</button>
                </form>
            </div>

            <div class="card">
                <h3><i class="fas fa-user-plus"></i> Add New Admin</h3>
                <p style="color:#777; font-size:0.9rem; margin-bottom:15px;">Create a new login for your staff member.</p>
                <form method="POST">
                    <div class="form-group">
                        <label>New Admin Username</label>
                        <input type="text" name="admin_user" placeholder="e.g. staff01" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="admin_pass" required>
                    </div>
                    <button type="submit" name="add_admin">Create Admin</button>
                </form>
            </div>

            <div class="card" style="border-top-color: #007bff;">
                <h3><i class="fas fa-server"></i> System Info</h3>
                <p style="color:#666; font-size:0.9rem; margin-bottom:15px;">Use this IP address for testing the QR code on mobile.</p>
                
                <div style="margin-bottom: 15px;">
                    <label>Local IP Address:</label>
                    <span class="ip-badge"><?php echo $serverIP; ?></span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>PHP Version:</label>
                    <span class="ip-badge"><?php echo phpversion(); ?></span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Server Software:</label>
                    <span style="font-size:12px; color:#555;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                </div>
            </div>

            <div class="card" style="border-top-color: red;">
                <h3><i class="fas fa-exclamation-triangle"></i> Maintenance</h3>
                <p style="color:#666; font-size:0.9rem;">Clean up old data or reset system.</p>
                <br>
                <button style="background: #555; cursor: not-allowed;" disabled>Clear All Attendance Logs</button>
                <br><small style="color:#aaa; display:block; text-align:center; margin-top:5px;">(Disabled for safety)</small>
            </div>

        </div>
    </div>

</body>
</html>