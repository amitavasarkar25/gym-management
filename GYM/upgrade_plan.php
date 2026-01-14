<?php
session_start();
include 'config.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// 2. Handle Form Submission
if (isset($_POST['upgrade_btn'])) {
    $requested_plan = $_POST['plan'];
    
    // --- UPDATED LOGIC: NON-BLOCKING REQUEST ---
    $sql = "UPDATE members SET pending_plan='$requested_plan' WHERE id=$user_id";
    
    if ($conn->query($sql)) {
        echo "<script>
            alert('Upgrade Request Sent! Please pay at the desk to activate.'); 
            window.location.href='user_dashboard.php';
        </script>";
    } else {
        $msg = "Error submitting request: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Upgrade Plan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Orbitron:wght@500;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { 
            /* Same Background as Login/Register */
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), 
                        url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: white;
        }

        .card { 
            background: rgba(30, 30, 30, 0.85);
            backdrop-filter: blur(10px);
            padding: 40px 30px; 
            border-radius: 15px; 
            width: 100%;
            max-width: 450px; 
            text-align: center; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #ff4500; 
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 { 
            font-family: 'Orbitron', sans-serif; 
            margin-bottom: 10px; 
            font-size: 24px;
        }

        p { color: #aaa; font-size: 14px; margin-bottom: 25px; }

        /* --- PLAN SELECTION CARDS --- */
        .plan-container {
            display: flex;
            flex-direction: column; /* Stack vertically on mobile */
            gap: 12px;
            margin-bottom: 25px;
        }

        .plan-container label { cursor: pointer; }
        .plan-container input[type="radio"] { display: none; }

        .plan-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }

        .plan-info { display: flex; align-items: center; gap: 15px; }
        .plan-icon { font-size: 24px; color: #555; width: 30px; text-align: center; }
        .plan-text { text-align: left; }
        .plan-text h4 { margin: 0; font-size: 16px; color: white; }
        .plan-text span { font-size: 12px; color: #888; }
        
        .plan-price { font-weight: bold; color: #ff4500; font-size: 14px; }
        
        .check-circle { 
            width: 20px; height: 20px; 
            border: 2px solid #555; border-radius: 50%; 
            position: relative; 
            display: none; /* Hidden by default, replaced by price */
        }

        /* Hover Effect */
        .plan-box:hover { background: rgba(255,255,255,0.1); }

        /* Selected State */
        .plan-container input[type="radio"]:checked + .plan-box {
            border-color: #ff4500;
            background: rgba(255, 69, 0, 0.15);
            box-shadow: 0 5px 15px rgba(255, 69, 0, 0.2);
        }
        
        .plan-container input[type="radio"]:checked + .plan-box .plan-icon { color: #ff4500; }
        
        /* --- BUTTONS --- */
        button { 
            width: 100%; 
            padding: 15px; 
            background: #ff4500; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        button:hover { background: #e03e00; box-shadow: 0 5px 15px rgba(255, 69, 0, 0.3); }

        .back-link { 
            display: inline-block; 
            margin-top: 20px; 
            color: #888; 
            text-decoration: none; 
            font-size: 14px; 
            transition: 0.3s;
        }
        .back-link:hover { color: white; }

    </style>
</head>
<body>

    <div class="card">
        <h2>Upgrade Membership</h2>
        <p>Select a new plan. <b>No registration fee applied.</b></p>
        
        <?php if($msg): ?>
            <p style="color:#ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px; margin-bottom:15px; font-size: 13px;">
                <?php echo $msg; ?>
            </p>
        <?php endif; ?>

        <form method="POST" onsubmit="startLoading()">
            <div class="plan-container">
                
                <label>
                    <input type="radio" name="plan" value="Silver">
                    <div class="plan-box">
                        <div class="plan-info">
                            <i class="fas fa-medal plan-icon"></i>
                            <div class="plan-text">
                                <h4>Silver Plan</h4>
                                <span>1 Month Access</span>
                            </div>
                        </div>
                        <div class="plan-price">₹1500</div>
                    </div>
                </label>

                <label>
                    <input type="radio" name="plan" value="Gold" checked>
                    <div class="plan-box">
                        <div class="plan-info">
                            <i class="fas fa-crown plan-icon"></i>
                            <div class="plan-text">
                                <h4>Gold Plan</h4>
                                <span>6 Months Access</span>
                            </div>
                        </div>
                        <div class="plan-price">₹8000</div>
                    </div>
                </label>

                <label>
                    <input type="radio" name="plan" value="Platinum">
                    <div class="plan-box">
                        <div class="plan-info">
                            <i class="fas fa-gem plan-icon"></i>
                            <div class="plan-text">
                                <h4>Platinum Plan</h4>
                                <span>1 Year Access</span>
                            </div>
                        </div>
                        <div class="plan-price">₹16000</div>
                    </div>
                </label>

            </div>

            <button type="submit" name="upgrade_btn" id="upBtn">Request Upgrade</button>
        </form>

        <a href="user_dashboard.php" class="back-link">Cancel & Go Back</a>
    </div>

    <script>
        function startLoading() {
            var btn = document.getElementById("upBtn");
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.style.opacity = "0.7";
        }
    </script>

</body>
</html>