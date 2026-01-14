<?php
session_start();
include 'config.php';

// 1. SECURITY: Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. FETCH USER DATA
$user_id = $_SESSION['user_id'];
$userQuery = $conn->query("SELECT * FROM members WHERE id=$user_id");
$user = $userQuery->fetch_assoc();

// 3. LOGIC: Calculate Days Remaining
$today_ts = time(); 
$expiry_ts = strtotime($user['plan_end_date']);
$diff = $expiry_ts - $today_ts;
$days_left = floor($diff / (60 * 60 * 24)) + 1; 

// 4. DETERMINE STATUS MESSAGE & COLOR
$status_msg = "";
$status_color = "";
$text_color = "white"; 

if ($user['status'] == 'active') {
    if ($days_left <= 3 && $days_left >= 0) {
        $status_msg = "⚠️ WARNING: Your plan expires in $days_left days! Renew soon.";
        $status_color = "#ffcc00"; // Yellow
        $text_color = "black";
    } else {
        $status_msg = "✅ Status: Active Membership";
        $status_color = "#28a745"; // Green
    }
} 
elseif ($user['status'] == 'expired') {
    $status_msg = "❌ PLAN EXPIRED: Access Blocked. Please contact Admin.";
    $status_color = "#dc3545"; // Red
} 
else {
    $status_msg = "⏳ Status: " . ucfirst($user['status']);
    $status_color = "#fd7e14"; // Orange
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - MELE FITNESS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background: #121212; 
            color: white;
            padding: 20px;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }
        
        .welcome-header h2 { font-size: 1.5rem; }
        .welcome-header span { color: #ff4500; } 

        .header-btns { display: flex; gap: 10px; }

        .btn-outline {
            text-decoration: none;
            font-size: 0.9rem;
            border: 1px solid;
            padding: 5px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .logout-btn { color: #ff4d4d; border-color: #ff4d4d; }
        .logout-btn:hover { background: #ff4d4d; color: white; }

        .about-btn { color: #28a745; border-color: #28a745; }
        .about-btn:hover { background: #28a745; color: white; }

        /* Notification Box */
        .alert-box {
            background: <?php echo $status_color; ?>;
            color: <?php echo $text_color; ?>;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* Cards Grid */
        .grid-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            flex: 1;
            min-width: 300px;
            background: #1e1e1e;
            padding: 25px;
            border-radius: 12px;
            border-top: 4px solid #ff4500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .card h3 { margin-bottom: 15px; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
        .card p { color: #aaa; margin-bottom: 8px; font-size: 0.95rem; }
        .card b { color: white; }

        /* Scan Button */
        .qr-btn {
            display: block;
            background: #ff4500;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            transition: 0.3s;
            border: none;
            width: 100%;
            cursor: pointer;
        }
        .qr-btn:hover { background: #e03e00; transform: translateY(-2px); }
        
        .disabled-btn {
            background: #444;
            color: #888;
            cursor: not-allowed;
        }
        .disabled-btn:hover { background: #444; transform: none; }

    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <div class="welcome-header">
            <h2>Welcome, <span><?php echo explode(" ", $user['full_name'])[0]; ?></span></h2>
            
            <div class="header-btns">
                <a href="about_us.php" class="btn-outline about-btn"><i class="fas fa-info-circle"></i> About Us</a>
                
                <a href="logout.php" class="btn-outline logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="alert-box">
            <?php echo $status_msg; ?>
        </div>

        <div class="grid-cards">
            
            <div class="card">
                <h3><i class="fas fa-dumbbell"></i> Membership Plan</h3>
                <p>Current Plan: <b><?php echo $user['plan_name']; ?></b></p>
                <p>Expiry Date: <b><?php echo date('d M Y', strtotime($user['plan_end_date'])); ?></b></p>
                
                <div style="margin: 20px 0; border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 15px 0;">
                    <a href="my_attendance.php" style="color: white; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                      <span><i class="fas fa-calendar-check" style="color:#aaa; margin-right:10px;"></i> View Attendance History</span>
                        <i class="fas fa-chevron-right" style="color:#555;"></i>
                    </a>
                </div>

                <?php if($user['status'] != 'pending'): ?>
                    <a href="upgrade_plan.php" style="display:inline-block; margin:10px 0; color:#ff4500; text-decoration:none; font-size:0.9rem;">
                        <i class="fas fa-arrow-circle-up"></i> Upgrade / Renew Plan
                    </a>
                <?php endif; ?>
                
                <?php if($user['status'] == 'active'): ?>
                    <button class="qr-btn" onclick="location.href='scanner.php'">
                        <i class="fas fa-qrcode"></i> Scan QR to Enter
                    </button>
                    <p style="text-align:center; font-size:12px; margin-top:10px; color:#666;">
                        (Click this to simulate scanning the Wall QR)
                    </p>
                <?php else: ?>
                    <button class="qr-btn disabled-btn" disabled>
                        <i class="fas fa-ban"></i> Access Blocked
                    </button>
                    <p style="text-align:center; font-size:12px; margin-top:10px; color:#ff4d4d;">
                        Please renew your subscription at the desk.
                    </p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3><i class="fas fa-id-badge"></i> My Profile</h3>
                
                <p>Full Name: <b><?php echo $user['full_name']; ?></b></p>
                <p>Username: <b><?php echo $user['username']; ?></b></p>
                <p>Phone: <b><?php echo $user['phone']; ?></b></p>
                <p>Member ID: <b>#IG-<?php echo $user['id']; ?></b></p>
                
                <hr style="border: 0; border-top: 1px solid #333; margin: 15px 0;">
                
                <p style="color:#666; font-size:12px;">
                    Need to change details? Contact Admin.
                </p>
            </div>

        </div>

    </div>

</body>
</html>