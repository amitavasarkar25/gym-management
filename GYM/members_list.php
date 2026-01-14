<?php
session_start();
include 'config.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// 2. HANDLE ACTIONS (Delete / Block)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM members WHERE id=$id");
        $conn->query("DELETE FROM attendance WHERE member_id=$id"); // Clean up attendance too
        $conn->query("DELETE FROM payments WHERE member_id=$id");   // Clean up payments
        header("Location: members_list.php");
    }
    if ($_GET['action'] == 'block') {
        $conn->query("UPDATE members SET status='blocked' WHERE id=$id");
        header("Location: members_list.php");
    }
    if ($_GET['action'] == 'unblock') {
        $conn->query("UPDATE members SET status='active' WHERE id=$id");
        header("Location: members_list.php");
    }
}

// 3. SEARCH LOGIC
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM members WHERE full_name LIKE '%$search%' OR username LIKE '%$search%' OR phone LIKE '%$search%' ORDER BY id DESC";
} else {
    $sql = "SELECT * FROM members ORDER BY id DESC";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Member List - MELE FITNESS</title>
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
        .main-content { margin-left: var(--sidebar-width); padding: 20px; width: 100%; }

        /* Search Bar Area */
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            flex-wrap: wrap; 
            gap: 10px;
        }

        .search-box { display: flex; gap: 10px; flex-wrap: wrap; }
        .search-box input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 300px; outline: none; }
        .search-box button { padding: 10px 20px; background: var(--main-color); color: white; border: none; border-radius: 5px; cursor: pointer; }

        /* Table Styling */
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #555; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #333; }
        
        /* Status Badges */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .active-badge { background: #d4edda; color: #155724; }
        .pending-badge { background: #fff3cd; color: #856404; }
        .blocked-badge { background: #f8d7da; color: #721c24; }
        .expired-badge { background: #e2e3e5; color: #383d41; }

        /* Action Buttons */
        .action-btn { margin-right: 5px; color: #555; cursor: pointer; transition: 0.3s; }
        .action-btn:hover { color: var(--main-color); }
        .delete-btn:hover { color: red; }

        /* Password Styling */
        .pass-text { 
            font-family: monospace; 
            background: #f0f0f0; 
            padding: 4px 8px; 
            border-radius: 4px; 
            color: #e83e8c; 
            font-size: 14px;
            border: 1px solid #ddd;
        }

        /* --- MOBILE RESPONSIVE FIX --- */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: relative; padding: 10px; }
            .menu { display: flex; overflow-x: auto; padding-bottom: 10px; }
            .menu a { margin-right: 10px; margin-bottom: 0; white-space: nowrap; font-size: 14px; padding: 10px; }
            .main-content { margin-left: 0; padding: 15px; }
            
            .top-bar { flex-direction: column; align-items: stretch; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
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
            <a href="manage_trainers.php"><i class="fas fa-user-ninja"></i> Manage Trainers</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" style="margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="top-bar">
            <h2>All Members</h2>
            
            <form class="search-box" method="GET">
                <input type="text" name="search" placeholder="Search by Name, Username or Phone..." value="<?php echo $search; ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if($search): ?>
                    <a href="members_list.php" style="padding:10px; color:red; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Password</th> <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                // Determine Badge Style
                                $statusClass = 'active-badge';
                                if($row['status'] == 'pending') $statusClass = 'pending-badge';
                                if($row['status'] == 'blocked') $statusClass = 'blocked-badge';
                                
                                // Check for Expiry
                                if($row['status'] == 'active' && $row['plan_end_date'] < date('Y-m-d')) {
                                    $row['status'] = 'Expired';
                                    $statusClass = 'expired-badge';
                                }
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:35px; height:35px; background:#eee; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#888;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php echo $row['full_name']; ?>
                                    </div>
                                </td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td>
                                    <b><?php echo $row['plan_name']; ?></b>
                                    <?php if(!empty($row['pending_plan'])): ?>
                                        <br>
                                        <small style="color:#ff4500; font-size:11px; font-weight:bold;">
                                            <i class="fas fa-arrow-up"></i> Request: <?php echo $row['pending_plan']; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                
                                <td><span class="pass-text"><?php echo $row['password']; ?></span></td>

                                <td>
                                    <a href="view_attendance.php?id=<?php echo $row['id']; ?>" class="action-btn" title="View History" style="color:#007bff; margin-right:8px;">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    
                                    <?php if($row['status'] == 'blocked'): ?>
                                        <a href="members_list.php?action=unblock&id=<?php echo $row['id']; ?>" class="action-btn" title="Unblock"><i class="fas fa-unlock"></i></a>
                                    <?php else: ?>
                                        <a href="members_list.php?action=block&id=<?php echo $row['id']; ?>" class="action-btn" title="Block Access"><i class="fas fa-ban"></i></a>
                                    <?php endif; ?>
                                    
                                    <a href="members_list.php?action=delete&id=<?php echo $row['id']; ?>" class="action-btn delete-btn" title="Delete User" onclick="return confirm('Delete this user permanently?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:#888;">No members found matching your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>