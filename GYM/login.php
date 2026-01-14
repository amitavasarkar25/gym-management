<?php
// --- 1. PHP LOGIN LOGIC ---
session_start();
include 'config.php';

$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role']; // Hidden input (admin or user)
    $user = $conn->real_escape_string($_POST['username']);
    $pass = $_POST['password'];

    // --- ADMIN LOGIN ---
    if ($role == 'admin') {
        $sql = "SELECT * FROM admins WHERE username = '$user' AND password = '$pass'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $msg = "Invalid Admin Credentials!";
            $msg_type = "error";
        }
    } 
    // --- USER LOGIN ---
    else {
        // We select status too, to check if they are blocked
        $sql = "SELECT * FROM members WHERE username = '$user' AND password = '$pass'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            if($row['status'] == 'pending') {
                $msg = "Account pending approval. Please pay at the desk.";
                $msg_type = "warning";
            } elseif($row['status'] == 'blocked') {
                $msg = "🚫 Access Denied. Your account is blocked.";
                $msg_type = "error";
            } else {
                // Success
                $_SESSION['user_id'] = $row['id'];
                header("Location: user_dashboard.php");
                exit();
            }
        } else {
            $msg = "User not found or incorrect password.";
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MELE FITNESS Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Orbitron:wght@500;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            /* High Quality Gym Background */
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), 
                        url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Glassmorphism Card */
        .login-card {
            background: rgba(30, 30, 30, 0.85);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #ff4500;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Logo */
        .brand {
            font-family: 'Orbitron', sans-serif;
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }
        .brand span { color: #ff4500; }

        /* Toggle Switch */
        .toggle-container {
            background: #111;
            border-radius: 50px;
            padding: 5px;
            display: flex;
            margin-bottom: 25px;
            position: relative;
            border: 1px solid #333;
        }

        .toggle-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: #777;
            font-weight: 600;
            cursor: pointer;
            border-radius: 50px;
            transition: 0.3s;
            z-index: 2;
        }

        .toggle-btn.active {
            background: #ff4500;
            color: white;
            box-shadow: 0 4px 15px rgba(255, 69, 0, 0.4);
        }

        /* Inputs */
        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ff4500;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px; /* Space for icon */
            background: #252525;
            border: 1px solid #333;
            color: white;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: #ff4500;
            background: #2a2a2a;
        }

        /* Password Eye Icon */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            left: auto !important; /* Override left icon style */
        }
        .toggle-password:hover { color: white; }

        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 15px;
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

        .btn-login:hover { 
            background: #e03e00; 
            box-shadow: 0 0 15px rgba(255, 69, 0, 0.4);
        }

        /* Alerts */
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .error { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid #dc3545; }
        .warning { background: rgba(255, 193, 7, 0.2); color: #ffca2c; border: 1px solid #ffc107; }

        /* Links */
        .links {
            margin-top: 20px;
            font-size: 13px;
        }
        .register-link {
            color: #aaa;
            text-decoration: none;
            transition: 0.3s;
        }
        .register-link:hover { color: #ff4500; }

    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand">MELE<span>FITNESS</span></div>

        <?php if($msg): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <i class="fas fa-exclamation-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="startLoading()">
            <input type="hidden" name="role" id="roleInput" value="user">

            <div class="toggle-container">
                <button type="button" class="toggle-btn active" id="userBtn" onclick="setRole('user')">Member</button>
                <button type="button" class="toggle-btn" id="adminBtn" onclick="setRole('admin')">Admin</button>
            </div>

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="passInput" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Login</button>

            <div class="links">
                <p style="color:#666; margin-bottom:5px;">Not a member yet?</p>
                <a href="register.php" class="register-link">Create New Account &rarr;</a>
            </div>
        </form>
    </div>

    <script>
        // 1. Handle Role Toggle
        function setRole(role) {
            document.getElementById('roleInput').value = role;
            
            const userBtn = document.getElementById('userBtn');
            const adminBtn = document.getElementById('adminBtn');

            if (role === 'user') {
                userBtn.classList.add('active');
                adminBtn.classList.remove('active');
            } else {
                adminBtn.classList.add('active');
                userBtn.classList.remove('active');
            }
        }

        // 2. Handle Password Visibility
        function togglePassword() {
            var input = document.getElementById("passInput");
            var icon = document.querySelector(".toggle-password");
            
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

        // 3. Loading Animation
        function startLoading() {
            var btn = document.getElementById("loginBtn");
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            btn.style.opacity = "0.7";
        }
    </script>

</body>
</html>