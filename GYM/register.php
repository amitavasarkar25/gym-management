<?php
// --- 1. DATABASE CONNECTION & LOGIC ---
session_start();
include 'config.php';

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $username = $conn->real_escape_string($_POST['username']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $plan = $_POST['plan']; 

    // --- VALIDATION ---
    // 1. Validate Phone
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $msg = "Invalid Phone Number! Enter 10 digits.";
        $msg_type = "error";
    }
    // 2. Validate Password Match
    elseif ($password !== $confirm_pass) {
        $msg = "Passwords do not match!";
        $msg_type = "error";
    } 
    else {
        // 3. Check Username
        $check = $conn->query("SELECT id FROM members WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $msg = "Username already taken!";
            $msg_type = "error";
        } else {
            // 4. Insert
            $stmt = $conn->prepare("INSERT INTO members (full_name, username, password, phone, plan_name, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssss", $fullname, $username, $password, $phone, $plan);

            if ($stmt->execute()) {
                $msg = "Registration Successful! Please visit the desk for verification.";
                $msg_type = "success";
            } else {
                $msg = "Database Error: " . $conn->error;
                $msg_type = "error";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Join MELE FITNESS Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Orbitron:wght@500;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            /* Background Matches Login Page */
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), 
                        url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Glassmorphism Card */
        .reg-card {
            background: rgba(30, 30, 30, 0.85);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #ff4500;
            color: white;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }
        .brand i { color: #ff4500; margin-right: 10px; }

        /* --- PLAN SELECTION --- */
        .plan-label { font-size: 13px; color: #bbb; margin-bottom: 10px; display: block; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
        
        .plan-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .plan-container input[type="radio"] { display: none; }

        .plan-box {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #aaa;
        }
        
        .plan-box h4 { font-size: 13px; margin: 5px 0; color: white; font-weight: 600; }
        .plan-box span { font-size: 11px; display: block; color: #777; }
        .plan-box .price { font-size: 14px; color: #ff4500; font-weight: bold; display: block; margin-top: 5px; }
        .plan-box i { font-size: 18px; margin-bottom: 5px; color: #555; transition: 0.3s; }

        /* Selected State */
        .plan-container input[type="radio"]:checked + .plan-box {
            border-color: #ff4500;
            background: rgba(255, 69, 0, 0.15);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 69, 0, 0.2);
            transform: translateY(-2px);
        }
        .plan-container input[type="radio"]:checked + .plan-box i { color: #ff4500; }

        /* --- INFO BOX (Simple Note) --- */
        .info-box {
            background: rgba(255, 193, 7, 0.1); /* Yellow tint */
            border: 1px solid #ffc107;
            color: #ffc107;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }
        .info-box i { font-size: 16px; }

        /* --- INPUTS --- */
        .input-group { position: relative; margin-bottom: 15px; }

        .input-group input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            background: #252525;
            border: 1px solid #333;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .input-group i.icon-left {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff4500;
            font-size: 14px;
        }

        .input-group input:focus { border-color: #ff4500; background: #2a2a2a; }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
        }
        .toggle-password:hover { color: white; }

        /* --- BUTTONS & ALERTS --- */
        .btn-register {
            width: 100%;
            padding: 14px;
            background: #ff4500;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-register:hover { background: #e03e00; box-shadow: 0 5px 15px rgba(255, 69, 0, 0.3); }

        .login-link { display: block; margin-top: 20px; color: #888; text-decoration: none; font-size: 13px; text-align: center; }
        .login-link b { color: #ff4500; }
        .login-link:hover { color: white; }

        .msg { padding: 12px; margin-bottom: 20px; border-radius: 5px; font-size: 13px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .msg.error { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #dc3545; }
        .msg.success { background: rgba(40, 167, 69, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }

        @media (max-width: 480px) {
            .reg-card { padding: 30px 20px; }
            .plan-container { gap: 5px; }
            .plan-box { padding: 10px 2px; }
            .plan-box h4 { font-size: 11px; }
        }
    </style>
</head>
<body>

    <div class="reg-card">
        <div class="brand"><i class="fas fa-dumbbell"></i> New Member</div>

        <?php if($msg): ?>
            <div class="msg <?php echo $msg_type; ?>">
                <?php if($msg_type == 'error'): ?><i class="fas fa-exclamation-circle"></i><?php endif; ?>
                <?php if($msg_type == 'success'): ?><i class="fas fa-check-circle"></i><?php endif; ?>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="startLoading()">
            
            <span class="plan-label">Choose Your Plan</span>
            <div class="plan-container">
                <label>
                    <input type="radio" name="plan" value="Silver" checked>
                    <div class="plan-box">
                        <i class="fas fa-medal"></i>
                        <h4>Silver</h4>
                        <span class="price">₹1500/mo</span>
                    </div>
                </label>
                <label>
                    <input type="radio" name="plan" value="Gold">
                    <div class="plan-box">
                        <i class="fas fa-crown"></i>
                        <h4>Gold</h4>
                        <span class="price">₹8000/6mo</span>
                    </div>
                </label>
                <label>
                    <input type="radio" name="plan" value="Platinum">
                    <div class="plan-box">
                        <i class="fas fa-gem"></i>
                        <h4>Platinum</h4>
                        <span class="price">₹16000/yr</span>
                    </div>
                </label>
            </div>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Note:</strong> Registration Fee ₹500
                </div>
            </div>

            <div class="input-group">
                <i class="fas fa-user icon-left"></i>
                <input type="text" name="fullname" placeholder="Full Name" required autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-id-badge icon-left"></i>
                <input type="text" name="username" placeholder="Choose Username" required autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-phone icon-left"></i>
                <input type="tel" name="phone" placeholder="Phone Number" pattern="[6-9][0-9]{9}" maxlength="10" required autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-lock icon-left"></i>
                <input type="password" name="password" id="pass1" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('pass1', this)"></i>
            </div>

            <div class="input-group">
                <i class="fas fa-check-circle icon-left"></i>
                <input type="password" name="confirm_password" id="pass2" placeholder="Confirm Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePass('pass2', this)"></i>
            </div>

            <button type="submit" class="btn-register" id="regBtn">Register</button>

            <a href="login.php" class="login-link">Already a member? <b>Login here</b></a>
        </form>
    </div>

    <script>
        // Password Visibility Toggle
        function togglePass(inputId, icon) {
            var input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Loading Animation
        function startLoading() {
            var btn = document.getElementById("regBtn");
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.style.opacity = "0.7";
        }
    </script>

</body>
</html>