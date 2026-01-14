<?php
session_start();
include 'config.php';

// --- FIX: SET TIMEZONE TO INDIA ---
date_default_timezone_set('Asia/Kolkata'); 

// --- CHECK 1: LOGIN ---
// If user scans without logging in, force login, then bring them back here.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=mark_attendance.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today_str = date('Y-m-d'); // Current Date (e.g., 2025-12-26)
$now = date('H:i:s');       // Current Time (Indian Time)

// Default Visuals (Empty)
$msg = "";
$icon = "";
$color = "";

// Fetch User Data from Database
$userQuery = $conn->query("SELECT * FROM members WHERE id=$user_id");
$user = $userQuery->fetch_assoc();

// --- CHECK 2: AUTO-BLOCKER (1 Day Grace Period Over) ---
// Logic: If Today is GREATER than Plan End Date, they are late.
if ($user['status'] == 'active' && $today_str > $user['plan_end_date']) {
    
    // 1. Block them in the database permanently
    $conn->query("UPDATE members SET status='expired' WHERE id=$user_id");
    
    // 2. Update local variable so the next check fails immediately
    $user['status'] = 'expired';
}

// --- CHECK 3: STATUS VERIFICATION ---
if ($user['status'] != 'active') {
    // If status is Expired, Pending, or Blocked
    $msg = "ACCESS BLOCKED<br>Your Plan Expired on " . date('d M Y', strtotime($user['plan_end_date']));
    $icon = "fa-ban";
    $color = "#ff4d4d"; // Red
} 
else {
    // --- CHECK 4: DUPLICATE ENTRY ---
    $check = $conn->query("SELECT * FROM attendance WHERE member_id=$user_id AND scan_date='$today_str'");

    if ($check->num_rows > 0) {
        $msg = "Already Checked In<br>See you tomorrow!";
        $icon = "fa-check-double";
        $color = "#f39c12"; // Orange
    } else {
        // --- CHECK 5: SUCCESS (Record Data) ---
        // Insert 'Present' status as well
        $insert = "INSERT INTO attendance (member_id, scan_date, scan_time, status) VALUES ('$user_id', '$today_str', '$now', 'Present')";
        
        if ($conn->query($insert)) {
            $firstName = explode(" ", $user['full_name'])[0];
            $msg = "Welcome, $firstName!<br>Entry Approved.";
            $icon = "fa-check-circle";
            $color = "#2ecc71"; // Green
        } else {
            $msg = "System Error!";
            $icon = "fa-exclamation";
            $color = "red";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Result</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Poppins', sans-serif; }

        body {
            background: #121212;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
        }

        .result-card {
            background: #1e1e1e;
            width: 100%;
            max-width: 400px;
            padding: 50px 20px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
            /* Dynamic Top Border Color based on Result */
            border-top: 6px solid <?php echo $color; ?>;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .icon-box {
            font-size: 80px;
            color: <?php echo $color; ?>;
            margin-bottom: 25px;
        }

        h2 { font-size: 1.4rem; margin-bottom: 10px; line-height: 1.4; }
        
        .timestamp {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 30px;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            display: inline-block;
            padding: 5px 20px;
            margin-top: 10px;
        }

        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            transition: 0.3s;
            font-size: 0.9rem;
        }
        .btn-home:hover { background: #555; }
    </style>
</head>
<body>

    <div class="result-card">
        <div class="icon-box">
            <i class="fas <?php echo $icon; ?>"></i>
        </div>
        
        <h2><?php echo $msg; ?></h2>
        
        <div class="timestamp">
            <?php echo date('d M Y • h:i A'); ?>
        </div>
        <br>

        <a href="user_dashboard.php" class="btn-home">Back to Dashboard</a>
    </div>

</body>
</html>