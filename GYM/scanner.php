<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MELE Scanner</title>
    <script src="jsQR.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            color: #ff4500;
            margin-bottom: 20px;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
            z-index: 10;
        }

        /* The container for the video stream */
        #canvas-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            aspect-ratio: 1/1; 
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            border: 4px solid #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- ENHANCED BOUNDARY (The "Darken Outside" Effect) --- */
        .scan-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 65%; /* Size of the scanning box */
            height: 65%;
            
            /* This shadow creates the dark dimming effect around the box */
            box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.6); 
            
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            pointer-events: none; 
            z-index: 5;
        }

        /* Animated corners (Green/Orange) */
        .scan-overlay::before, .scan-overlay::after {
            content: '';
            position: absolute;
            width: 30px; height: 30px;
            border-color: #ff4500;
            border-style: solid;
            transition: 0.3s;
        }
        .scan-overlay::before { top: -2px; left: -2px; border-width: 5px 0 0 5px; }
        .scan-overlay::after { bottom: -2px; right: -2px; border-width: 0 5px 5px 0; }
        
        /* FLASH BUTTON */
        #flashBtn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            z-index: 20;
            display: none; /* Hidden by default, shown via JS if supported */
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        #flashBtn:hover { background: rgba(255, 255, 255, 0.4); }
        #flashBtn.active { background: #ff4500; color: white; box-shadow: 0 0 15px #ff4500; }

        #loadingMessage {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #aaa;
            font-size: 14px;
            z-index: 1;
        }

        .back-btn {
            margin-top: 30px;
            background: #333;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10;
        }
        .back-btn:hover { background: #ff4500; color: white; }

        .status-msg {
            margin-top: 15px;
            font-size: 16px;
            color: #ccc;
            height: 25px; 
            font-weight: bold;
            text-align: center;
            z-index: 10;
        }
    </style>
</head>
<body>

    <h1>Scan Attendance</h1>

    <div id="canvas-container">
        <div id="loadingMessage">🎥 Accessing Camera...</div>
        <canvas id="canvas" hidden></canvas>
        
        <div class="scan-overlay"></div>

        <button id="flashBtn"><i class="fas fa-bolt"></i></button>
    </div>

    <div class="status-msg" id="outputMessage">Point camera at QR Code</div>

    <a href="user_dashboard.php" class="back-btn">
        <span>&larr;</span> Back to Dashboard
    </a>

    <script>
        var video = document.createElement("video");
        var canvasElement = document.getElementById("canvas");
        var canvas = canvasElement.getContext("2d");
        var loadingMessage = document.getElementById("loadingMessage");
        var outputMessage = document.getElementById("outputMessage");
        var flashBtn = document.getElementById("flashBtn");
        
        var scanningActive = true;
        var isFrozen = false; 
        var videoTrack; // To control the torch

        // 1. Start Camera
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(function(stream) {
            video.srcObject = stream;
            video.setAttribute("playsinline", true); 
            video.play();
            
            // --- FLASHLIGHT LOGIC ---
            videoTrack = stream.getVideoTracks()[0];
            
            // Check if device supports torch
            const capabilities = videoTrack.getCapabilities();
            if (capabilities.torch) {
                flashBtn.style.display = "flex"; // Show button
                
                let flashOn = false;
                flashBtn.addEventListener('click', function() {
                    flashOn = !flashOn;
                    videoTrack.applyConstraints({
                        advanced: [{ torch: flashOn }]
                    });
                    
                    // Toggle Icon Style
                    if(flashOn) {
                        flashBtn.classList.add("active");
                    } else {
                        flashBtn.classList.remove("active");
                    }
                });
            }

            requestAnimationFrame(tick);
        }).catch(function(err) {
            console.error("Camera Error:", err);
            loadingMessage.innerText = "❌ Camera Blocked. Please enable permissions.";
            loadingMessage.style.color = "#ff4500";
        });

        function tick() {
            if (!scanningActive) return;

            if (isFrozen) {
                requestAnimationFrame(tick);
                return; 
            }

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                loadingMessage.hidden = true;
                canvasElement.hidden = false;

                canvasElement.height = video.videoHeight;
                canvasElement.width = video.videoWidth;
                canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);

                var imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
                
                var code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });

                if (code) {
                    if (code.data.includes("mark_attendance.php")) {
                        // SUCCESS
                        drawBox(code.location, "#00FF00");
                        outputMessage.innerText = "✅ QR Verified! Redirecting...";
                        outputMessage.style.color = "#00FF00";
                        scanningActive = false;
                        
                        setTimeout(function() {
                            window.location.href = "mark_attendance.php";
                        }, 300);

                    } else {
                        // ERROR
                        drawBox(code.location, "#FF4500");
                        outputMessage.innerText = "⚠️ Invalid QR Code";
                        outputMessage.style.color = "#FF4500";
                        
                        isFrozen = true;
                        setTimeout(function() {
                            isFrozen = false;
                            outputMessage.innerText = "Scanning...";
                            outputMessage.style.color = "#ccc";
                        }, 2500);
                    }
                } else {
                    outputMessage.innerText = "Scanning...";
                    outputMessage.style.color = "#ccc";
                }
            }
            requestAnimationFrame(tick);
        }

        function drawBox(location, color) {
            drawLine(location.topLeftCorner, location.topRightCorner, color);
            drawLine(location.topRightCorner, location.bottomRightCorner, color);
            drawLine(location.bottomRightCorner, location.bottomLeftCorner, color);
            drawLine(location.bottomLeftCorner, location.topLeftCorner, color);
        }

        function drawLine(begin, end, color) {
            canvas.beginPath();
            canvas.moveTo(begin.x, begin.y);
            canvas.lineTo(end.x, end.y);
            canvas.lineWidth = 5;
            canvas.strokeStyle = color;
            canvas.stroke();
        }
    </script>
</body>
</html>