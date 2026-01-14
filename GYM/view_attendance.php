<?php
session_start();
include 'config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// 2. GET MEMBER ID
if (!isset($_GET['id'])) {
    header("Location: members_list.php");
    exit();
}

$member_id = intval($_GET['id']);

// 3. FETCH MEMBER DETAILS
$memberQuery = $conn->query("SELECT full_name, username FROM members WHERE id=$member_id");
$member = $memberQuery->fetch_assoc();

// 4. FETCH ATTENDANCE LOGS
$logsQuery = $conn->query("SELECT * FROM attendance WHERE member_id=$member_id ORDER BY scan_date DESC, scan_time DESC");
$total_present = $logsQuery->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Attendance History - MELE FITNESS</title>
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

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .back-btn { background: #555; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; transition: 0.3s; }
        .back-btn:hover { background: #333; }

        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* Table Styling */
        .table-container { overflow-x: auto; } /* Scrollable on Mobile */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #555; border-bottom: 2px solid #eee; white-space: nowrap; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #333; white-space: nowrap; }
        
        .status-present { color: green; font-weight: bold; }
        .count-badge { background: #e3f2fd; color: #0d47a1; padding: 5px 10px; border-radius: 15px; font-size: 0.9rem; font-weight: bold; margin-left: 10px; }

        /* --- MOBILE RESPONSIVE FIX --- */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding: 10px; }
            .menu { display: flex; overflow-x: auto; padding-bottom: 10px; }
            .menu a { margin-right: 10px; margin-bottom: 0; white-space: nowrap; font-size: 14px; padding: 10px; }
            .main-content { margin-left: 0; padding: 20px; }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-dumbbell"></i> MELE FITNESS</div>
        <div class="menu">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_qr_station.php"><i class="fas fa-qrcode"></i> QR Station</a>
            <a href="members_list.php" class="active"><i class="fas fa-users"></i> Members List</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" style="margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h2>
                    Attendance Log
                    <span class="count-badge"><?php echo $total_present; ?> Days Present</span>
                </h2>
                <p style="color:#666; margin-top:5px;">Member: <b><?php echo $member['full_name']; ?></b> (<?php echo $member['username']; ?>)</p>
            </div>
            <a href="members_list.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>

        <div class="card">
            <div class="table-container">
                <?php if($logsQuery->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Day</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $logsQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['scan_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['scan_time'])); ?></td>
                                <td><?php echo date('l', strtotime($row['scan_date'])); ?></td>
                                <td class="status-present"><i class="fas fa-check-circle"></i> Present</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:#999;">
                        <i class="fas fa-history" style="font-size:40px; margin-bottom:10px;"></i><br>
                        No attendance records found for this user.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>