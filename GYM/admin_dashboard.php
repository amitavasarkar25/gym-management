<?php
session_start();
include 'config.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// --- 2. HANDLE ACTIONS ---

// A. QUICK UPGRADE / RENEW (Simple Approval)
if (isset($_POST['quick_upgrade'])) {
    $id = intval($_POST['user_id']);
    
    // Fetch info
    $user = $conn->query("SELECT * FROM members WHERE id=$id")->fetch_assoc();
    $new_plan = $user['pending_plan']; 
    
    if(!$new_plan) {
        header("Location: admin_dashboard.php?msg=error");
        exit();
    }

    // 1. Calculate Price & Duration
    $amount = 0;
    $days = 30;
    
    if ($new_plan == 'Silver') { $amount = 1500; $days = 30; }
    if ($new_plan == 'Gold') { $amount = 8000; $days = 180; }
    if ($new_plan == 'Platinum') { $amount = 16000; $days = 365; }

    // 2. Calculate Dates (Smart Extension)
    $start_date = date('Y-m-d');
    $expiry_date = "";

    // If user is already active and not expired, extend from their CURRENT end date
    if($user['status'] == 'active' && $user['plan_end_date'] > $start_date) {
        $start_date = $user['plan_end_date']; 
        $expiry_date = date('Y-m-d', strtotime("+$days days", strtotime($start_date)));
    } else {
        $expiry_date = date('Y-m-d', strtotime("+$days days"));
    }

    // 3. Update DB
    $sql = "UPDATE members SET status='active', plan_name='$new_plan', pending_plan=NULL, plan_start_date='$start_date', plan_end_date='$expiry_date' WHERE id=$id";
    $conn->query($sql);

    // 4. Record Revenue
    $conn->query("INSERT INTO payments (member_id, amount) VALUES ($id, $amount)");

    header("Location: admin_dashboard.php?msg=upgraded");
    exit();
}

// B. REGISTRATION APPROVAL
if (isset($_POST['approve_registration'])) {
    $id = intval($_POST['user_id']);
    $type = $_POST['approve_type']; 
    
    $user = $conn->query("SELECT * FROM members WHERE id=$id")->fetch_assoc();
    $plan = $user['plan_name']; 
    
    $amount = 0;
    $expiry_date = "";
    $start_date = date('Y-m-d');
    $days = 30; 

    $plan_fee = 0;
    if ($plan == 'Silver') { $plan_fee = 1500; $days = 30; }
    if ($plan == 'Gold') { $plan_fee = 8000; $days = 180; }
    if ($plan == 'Platinum') { $plan_fee = 16000; $days = 365; }

    if ($type == 'new') {
        $amount = $plan_fee + 500; 
        $expiry_date = date('Y-m-d', strtotime("+$days days")); 
    } 
    elseif ($type == 'existing') {
        $amount = 0; 
        $expiry_date = $_POST['manual_expiry']; 
        if(empty($expiry_date)) $expiry_date = date('Y-m-d', strtotime("+30 days"));
    }

    $sql = "UPDATE members SET status='active', plan_name='$plan', pending_plan=NULL, plan_start_date='$start_date', plan_end_date='$expiry_date' WHERE id=$id";
    $conn->query($sql);

    if ($amount > 0) {
        $conn->query("INSERT INTO payments (member_id, amount) VALUES ($id, $amount)");
    }

    header("Location: admin_dashboard.php?msg=approved");
    exit();
}

// C. REJECT
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $check = $conn->query("SELECT status FROM members WHERE id=$id")->fetch_assoc();
    if ($check['status'] == 'pending') {
        $conn->query("DELETE FROM members WHERE id=$id");
    } else {
        $conn->query("UPDATE members SET pending_plan=NULL WHERE id=$id");
    }
    header("Location: admin_dashboard.php?msg=rejected");
    exit();
}

// --- DATA FETCHING ---
$totalMembers = $conn->query("SELECT * FROM members WHERE status='active'")->num_rows;
$pendingResult = $conn->query("SELECT * FROM members WHERE status='pending' OR pending_plan IS NOT NULL");
$pendingReq = $pendingResult->num_rows;
$revRow = $conn->query("SELECT SUM(amount) as total FROM payments")->fetch_assoc();
$totalRevenue = $revRow['total'] ? $revRow['total'] : 0;
$activeResult = $conn->query("SELECT * FROM members WHERE status='active' ORDER BY id DESC LIMIT 10");
$todayDate = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Admin Portal - MELE FITNESS</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- BASE CSS --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        :root { --sidebar-width: 250px; --main-color: #ff4500; --dark-bg: #1d2231; --light-bg: #f1f5f9; }
        
        body { display: flex; background: var(--light-bg); min-height: 100vh; }
        
        /* --- SIDEBAR & NAVIGATION --- */
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--dark-bg); color: white; position: fixed; padding: 20px; z-index: 100; transition: all 0.3s ease; }
        .logo { font-size: 22px; font-weight: bold; color: var(--main-color); margin-bottom: 40px; display: flex; align-items: center; justify-content: space-between; }
        
        .menu a { display: block; color: #ccc; padding: 15px; text-decoration: none; margin-bottom: 5px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background: var(--main-color); color: white; }
        .menu i { margin-right: 10px; width: 20px; }
        
        /* Toggle Button (Hidden on Desktop) */
        .menu-toggle { display: none; font-size: 24px; cursor: pointer; color: white; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); padding: 20px; width: 100%; transition: margin-left 0.3s ease; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--main-color); }
        .card h3 { font-size: 32px; color: #333; }
        
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px; overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; min-width: 600px; }
        th { text-align: left; padding: 10px; color: #555; border-bottom: 1px solid #eee; }
        td { padding: 12px 10px; color: #333; border-bottom: 1px solid #eee; }
        
        /* Buttons */
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; font-weight: bold; margin-right: 5px; transition: 0.3s; }
        .btn-review { background: #007bff; color: white; }
        .btn-review:hover { background: #0056b3; }
        .btn-upgrade { background: #28a745; color: white; }
        .btn-upgrade:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
        .btn-qr { background: #cce5ff; color: #004085; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 200; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 450px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.3); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity:0; transform: translateY(-20px);} to {opacity:1; transform: translateY(0);} }
        .modal-btn { width: 100%; padding: 15px; margin-top: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; text-align: left; }
        
        .btn-new { background: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; } 
        .btn-new:hover { background: #bbdefb; }
        .btn-existing { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .btn-existing:hover { background: #ffeeba; }
        .date-input { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .divider { margin: 20px 0; font-size: 12px; color: #888; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .divider::before, .divider::after { content: ""; height: 1px; background: #eee; flex: 1; }
        
        /* --- RESPONSIVE MOBILE VIEW --- */
        @media (max-width: 768px) { 
            body { flex-direction: column; } 
            
            /* Shrink Sidebar */
            .sidebar { 
                width: 100%; 
                height: auto; 
                position: relative; 
                padding: 15px 20px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            
            /* Reset Content Margin */
            .main-content { margin-left: 0; padding: 15px; } 
            
            /* Logo Adjustment */
            .logo { margin-bottom: 0; }
            
            /* Show Hamburger */
            .menu-toggle { display: block; }
            
            /* Hide Menu Links Default */
            .menu { 
                display: none; 
                flex-direction: column; 
                margin-top: 20px; 
                border-top: 1px solid rgba(255,255,255,0.1);
                padding-top: 10px;
            }
            
            /* Show Menu Class (Toggled via JS) */
            .menu.show { display: flex; animation: slideDown 0.3s ease-out; }
            
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .cards { grid-template-columns: 1fr; } 
            .table-container { overflow-x: scroll; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <span><i class="fas fa-dumbbell"></i> MELE FITNESS</span>
            <i class="fas fa-bars menu-toggle" onclick="toggleMenu()"></i>
        </div>
        
        <div class="menu" id="mobileMenu">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_qr_station.php"><i class="fas fa-qrcode"></i> QR Station</a>
            <a href="members_list.php"><i class="fas fa-users"></i> Members List</a>
            <a href="manage_trainers.php"><i class="fas fa-user-ninja"></i> Manage Trainers</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" style="margin-top: 20px; border-top:1px solid rgba(255,255,255,0.1);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Admin Dashboard</h2>
            <div class="user-info"><span>Welcome, <b>Admin</b></span></div>
        </div>

        <div class="cards">
            <div class="card">
                <div><h3><?php echo $totalMembers; ?></h3><span>Active Members</span></div>
                <i class="fas fa-users"></i>
            </div>
            <div class="card">
                <div><h3><?php echo $pendingReq; ?></h3><span>Pending Requests</span></div>
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="card">
                <div><h3>₹<?php echo number_format($totalRevenue); ?></h3><span>Total Revenue</span></div>
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header"><h3>Approval Requests</h3></div>
            <table>
                <thead>
                    <tr><th>Name</th><th>Request Type</th><th>Plan</th><th>Phone</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if($pendingResult->num_rows > 0): ?>
                        <?php while($row = $pendingResult->fetch_assoc()): ?>
                        <?php 
                            $isUpgrade = (!empty($row['pending_plan']));
                            $reqPlan = $isUpgrade ? $row['pending_plan'] : $row['plan_name'];
                            
                            $price = 0;
                            if($reqPlan=='Silver') $price=1500;
                            if($reqPlan=='Gold') $price=8000;
                            if($reqPlan=='Platinum') $price=16000;
                        ?>
                        <tr>
                            <td><strong><?php echo $row['full_name']; ?></strong></td>
                            <td>
                                <?php if($isUpgrade): ?>
                                    <span style="background:#d4edda; color:#155724; padding:3px 8px; border-radius:4px; font-size:12px;">Renew / Upgrade</span>
                                <?php else: ?>
                                    <span style="background:#e3f2fd; color:#0d47a1; padding:3px 8px; border-radius:4px; font-size:12px;">New Registration</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $reqPlan; ?> 
                                <span style="color:#777; font-size:12px;">(₹<?php echo $price; ?>)</span>
                            </td>
                            <td><?php echo $row['phone']; ?></td>
                            <td>
                                <?php if($isUpgrade): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Approve Upgrade to <?php echo $reqPlan; ?>? \nCost: ₹<?php echo $price; ?> will be added to revenue.');">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="quick_upgrade" class="btn btn-upgrade">
                                            <i class="fas fa-bolt"></i> Approve Upgrade
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button onclick="openModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['full_name']); ?>', '<?php echo $reqPlan; ?>')" class="btn btn-review">
                                        <i class="fas fa-search"></i> Review & Approve
                                    </button>
                                <?php endif; ?>

                                <a href="admin_dashboard.php?reject=<?php echo $row['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject request?');"><i class="fas fa-times"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px; color:#888;">No pending requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3>Recent Active Members</h3>
                <a href="admin_qr_station.php" class="btn-qr"><i class="fas fa-tv"></i> Open QR Station</a>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Plan</th><th>Expiry Date</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($row = $activeResult->fetch_assoc()): ?>
                    <tr>
                        <td>#IG-<?php echo $row['id']; ?></td>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['plan_name']; ?></td>
                        <td><?php echo date('d M Y', strtotime($row['plan_end_date'])); ?></td>
                        <td><span style="color:green; font-weight:bold;">Active</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="approveModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom:5px;">Registration Approval</h3>
            <p id="modalUserName" style="color:#666; margin-bottom:20px;"></p>
            
            <form method="POST" action="admin_dashboard.php">
                <input type="hidden" name="user_id" id="modalUserId">
                
                <button type="submit" name="approve_registration" name="approve_type" value="new" class="modal-btn btn-new">
                    <span><i class="fas fa-user-plus"></i> New Joiner</span>
                    <span id="priceNew">...</span>
                </button>
                <input type="hidden" name="approve_type" id="hiddenApproveType" value="new">

                <div class="divider">OR</div>

                <div style="text-align:left;">
                    <label style="font-size:12px; font-weight:bold; color:#856404;">Manual Migration (Paid Offline):</label>
                    <input type="date" name="manual_expiry" class="date-input" min="<?php echo $todayDate; ?>">
                    
                    <button type="submit" name="approve_registration" onclick="document.getElementById('hiddenApproveType').value='existing'" class="modal-btn btn-existing" style="margin-top:5px;">
                        <span><i class="fas fa-check-circle"></i> Approve Manually</span>
                        <span>₹0</span>
                    </button>
                </div>

                <div style="margin-top:20px; cursor:pointer; color:#888; font-size:13px;" onclick="closeModal()">Cancel</div>
            </form>
        </div>
    </div>

    <script>
        // Toggle Mobile Menu
        function toggleMenu() {
            var menu = document.getElementById('mobileMenu');
            if (menu.classList.contains('show')) {
                menu.classList.remove('show');
            } else {
                menu.classList.add('show');
            }
        }

        // Modal Logic
        function openModal(id, name, plan) {
            document.getElementById('approveModal').style.display = 'flex';
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalUserName').innerText = name + " (" + plan + ")";
            let price = 0;
            if(plan === 'Silver') price = 1500;
            if(plan === 'Gold') price = 8000;
            if(plan === 'Platinum') price = 16000;
            document.getElementById('priceNew').innerText = "₹" + (price + 500); 
            document.getElementById('hiddenApproveType').value = 'new';
        }

        function closeModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('approveModal')) {
                closeModal();
            }
        }
    </script>

</body>
</html>