<?php
session_start();
include 'config.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Attendance Data for THIS user only
$sql = "SELECT * FROM attendance WHERE member_id = $user_id ORDER BY scan_date DESC, scan_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - MELE FITNESS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #121212; color: white; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 600px; background: #1e1e1e; padding: 20px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        h2 { margin: 0; font-size: 20px; color: #ff4500; }
        
        .back-btn { text-decoration: none; color: #aaa; font-size: 14px; border: 1px solid #444; padding: 5px 15px; border-radius: 5px; transition: 0.3s; }
        .back-btn:hover { background: #333; color: white; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; color: #888; font-size: 14px; border-bottom: 1px solid #333; }
        td { padding: 15px 12px; border-bottom: 1px solid #2a2a2a; color: #ddd; font-size: 15px; }
        
        .status-icon { color: #00e676; margin-right: 5px; }
        .date-col { font-weight: bold; }
        .time-col { color: #aaa; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2><i class="fas fa-history"></i> My Attendance</h2>
            <a href="user_dashboard.php" class="back-btn">Back</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="date-col"><?php echo date('d M Y', strtotime($row['scan_date'])); ?></td>
                            <td class="time-col"><?php echo date('h:i A', strtotime($row['scan_time'])); ?></td>
                            <td><i class="fas fa-check-circle status-icon"></i> Present</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding:30px; color:#555;">
                            No attendance records yet.<br>Scan the QR code to start!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>