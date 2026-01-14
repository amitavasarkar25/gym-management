<?php
session_start();

// 1. SECURITY CHECK
// Only Admins can access this page
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// 2. AUTO-DETECT IP ADDRESS
// This finds your computer's local IP automatically
$serverIP = getHostByName(getHostName());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permanent Wall QR Generator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;700&family=Orbitron:wght@500;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: #000;
            background-image: 
                linear-gradient(rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.9)), 
                url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            color: white;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* --- SETUP BOX --- */
        .setup-box {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #333;
            margin-bottom: 30px;
            text-align: center;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .setup-box input {
            padding: 12px;
            width: 100%;
            max-width: 250px;
            border-radius: 5px;
            border: none;
            margin-top: 10px;
            margin-bottom: 10px;
            background: #333;
            color: white;
            outline: none;
            text-align: center;
            font-size: 1rem;
            font-family: monospace;
        }
        .setup-box button {
            padding: 12px 20px;
            background: #ff4500;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        .setup-box button:hover { background: #e03e00; }

        /* --- THE PRINTABLE QR CARD --- */
        .qr-card {
            background: white;
            color: black;
            width: 100%; 
            max-width: 380px;
            padding: 30px 20px;
            text-align: center;
            border-radius: 20px;
            border: 10px solid #000;
            box-shadow: 0 0 50px rgba(255, 69, 0, 0.2);
            position: relative;
        }

        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .qr-image {
            width: 100%;
            max-width: 280px;
            margin: 20px auto;
            display: block;
        }

        .scan-text {
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .instruction {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #555;
        }

        /* --- BUTTONS --- */
        .btn-group { 
            margin-top: 30px; 
            display: flex; 
            gap: 15px; 
            flex-wrap: wrap; 
            justify-content: center;
            z-index: 10;
        }

        .print-btn, .back-btn {
            background: rgba(0,0,0,0.5);
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 50px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
        }
        .print-btn:hover, .back-btn:hover { background: white; color: black; }

        /* --- PRINT MODE --- */
        @media print {
            body { background: white; padding: 0; display: block; }
            .setup-box, .btn-group { display: none !important; }
            .qr-card { 
                box-shadow: none; 
                margin: 50px auto; 
                transform: scale(1.0); 
                border: 10px solid black;
                width: 380px;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body onload="generateQR()">

    <div class="setup-box">
        <h3><i class="fas fa-wifi"></i> Setup Your Wall QR</h3>
        <p style="color:#aaa; font-size:0.9rem; margin-top:5px;">
            Detected IP: <b><?php echo $serverIP; ?></b><br>
            <span style="font-size:12px; color:#666;">(If incorrect, type manually below)</span>
        </p>
        <div style="display:flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <input type="text" id="ipInput" placeholder="192.168.x.x" value="<?php echo $serverIP; ?>">
            <button onclick="generateQR()">Update</button>
        </div>
    </div>

    <div class="qr-card" id="printableArea">
        <div class="brand">MELE<span style="color:#ff4500;">FITNESS</span></div>
        <div style="font-size: 0.8rem; letter-spacing: 2px; margin-bottom: 10px;">OFFICIAL MEMBER ENTRY</div>
        
        <img id="qrImage" class="qr-image" src="" alt="Wall QR">

        <div class="scan-text">Scan to Mark Attendance</div>
        <div class="instruction">
            <i class="fas fa-camera"></i> Open Camera & Scan
        </div>
    </div>

    <div class="btn-group">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print Card
        </button>
    </div>

    <script>
        function generateQR() {
            var input = document.getElementById('ipInput').value;
    
            if(input.trim() === "") {
                input = "localhost"; // Fallback
            }

            var finalUrl = "";

            if (input.includes("http") || input.includes("www")) {
                finalUrl = input; 
            } else {
                finalUrl = "http://" + input + "/gym_system/mark_attendance.php";
            }

            var qrApi = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" + encodeURIComponent(finalUrl);
            document.getElementById('qrImage').src = qrApi;
        }
    </script>

</body>
</html>