<?php
session_start();
include 'config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$trainersResult = $conn->query("SELECT * FROM trainers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MELE FITNESS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Orbitron:wght@700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { 
            background: #121212; 
            color: #eee;
            padding: 20px;
            min-height: 100vh;
        }

        .container { max-width: 900px; margin: 0 auto; }

        /* HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        .brand { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; color: #ff4500; }
        .back-btn {
            background: transparent;
            border: 1px solid #ff4500;
            color: #ff4500;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .back-btn:hover { background: #ff4500; color: white; }

        h2 { color: white; margin-bottom: 20px; border-left: 4px solid #ff4500; padding-left: 10px; font-size: 1.5rem; }

        /* RULES CARD */
        .rules-card {
            background: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 40px;
            border-left: 5px solid #ff4500;
        }
        .rules-card h3 { color: #ff4500; margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px; }
        
        .rule-block { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #333; }
        .rule-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        
        .rule-title { font-weight: 600; color: #fff; display: block; margin-bottom: 5px; }
        .rule-desc { color: #ccc; font-size: 0.95rem; line-height: 1.5; }
        
        .sticker-alert {
            background: rgba(255, 69, 0, 0.15);
            color: #ff4500;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            border: 1px dashed #ff4500;
            margin: 15px 0;
        }

        /* TRAINERS GRID */
        .trainers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .trainer-card {
            background: #1e1e1e;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transition: 0.3s;
            border-top: 3px solid #ff4500;
            display: flex;
            flex-direction: column;
        }
        .trainer-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(255, 69, 0, 0.15); }

        .profile-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            object-position: top;
            filter: brightness(0.9);
            border-bottom: 1px solid #333;
        }

        .trainer-info { padding: 20px; text-align: center; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .trainer-info h4 { font-size: 1.2rem; color: white; margin-bottom: 5px; }
        .trainer-info p { color: #888; font-size: 0.9rem; margin-bottom: 20px; font-weight: 300; }
        
        .action-btns {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap; 
        }

        .contact-btn, .cert-btn {
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            color: white;
            font-weight: 600;
            flex: 1;
            min-width: 110px;
            cursor: pointer;
            border: none;
        }
        
        .contact-btn { background: #007bff; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3); }
        .contact-btn:hover { background: #0056b3; transform: scale(1.05); }
        .contact-btn i { margin-right: 8px; font-size: 1rem; }
        
        .cert-btn { background: #333; border: 1px solid #555; }
        .cert-btn:hover { background: #ff4500; border-color: #ff4500; color: white; transform: scale(1.05); }
        .cert-btn i { margin-right: 8px; }

        /* --- MODAL (POPUP) STYLES --- */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.95);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
        }

        .modal-content {
            max-width: 90%;
            max-height: 80vh;
            border: 2px solid #ff4500;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 69, 0, 0.5);
            object-fit: contain;
        }

        .close-btn {
            margin-top: 20px;
            background: #ff4500;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .close-btn:hover { background: #e03e00; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .back-btn { width: 100%; text-align: center; display: block; }
            .trainers-grid { grid-template-columns: 1fr; } 
            .trainer-card { margin-bottom: 15px; }
            .profile-img { height: 300px; } 
            .action-btns { flex-direction: row; } 
        }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="page-header">
            <div class="brand"><i class="fas fa-dumbbell"></i> MELE FITNESS</div>
            <a href="user_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <h2>Gym Rules & Policy</h2>
        <div class="rules-card">
            <h3><i class="fas fa-exclamation-circle"></i> Important Notice</h3>
            
            <div class="rule-block">
                <span class="rule-title"><i class="fas fa-shoe-prints" style="color:#ff4500;"></i> Shoe Policy</span>
                <p class="rule-desc">Outside shoes are strictly <b>NOT ALLOWED</b> inside the gym floor. Please carry a separate pair of clean indoor/gym shoes.</p>
                <p class="rule-desc" style="color:#28a745; margin-top:5px;"><i class="fas fa-check"></i> Clean Gym = Healthy Workout</p>
            </div>

            <div class="sticker-alert">
                <i class="fas fa-ban"></i> NO OUTSIDE SHOES • GYM SHOES ONLY
            </div>

            <div class="rule-block">
                <span class="rule-title"><i class="fas fa-smile" style="color:#ffcc00;"></i> Message to Members</span>
                <p class="rule-desc">
                    Dear Members,<br>
                    For cleanliness and safety, please do not wear outside shoes inside the gym. Let's keep <b>Mele Fitness</b> clean together!
                </p>
            </div>
        </div>

        <h2>Certified Trainers</h2>
        <div class="trainers-grid">
            
            <?php if($trainersResult->num_rows > 0): ?>
                <?php while($t = $trainersResult->fetch_assoc()): ?>
                    <div class="trainer-card">
                        <img src="<?php echo $t['photo_url']; ?>" class="profile-img" alt="Trainer Profile">
                        
                        <div class="trainer-info">
                            <div>
                                <h4><?php echo $t['full_name']; ?></h4>
                                <p><?php echo $t['specialty']; ?></p>
                            </div>
                            
                            <div class="action-btns">
                                <a href="tel:<?php echo $t['phone']; ?>" class="contact-btn">
                                    <i class="fas fa-phone-alt"></i> Contact
                                </a>

                                <?php if(!empty($t['certificate_url'])): ?>
                                    <button onclick="openModal('<?php echo $t['certificate_url']; ?>')" class="cert-btn">
                                        <i class="fas fa-certificate"></i> View Cert
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#888; text-align:center; grid-column: 1/-1; padding: 20px; background: #1e1e1e; border-radius: 10px;">
                    <i class="fas fa-info-circle"></i> No trainers listed yet.
                </p>
            <?php endif; ?>

        </div>

        <br><br>
    </div>

    <div id="certModal" class="modal">
        <img id="modalImg" class="modal-content" src="">
        <button class="close-btn" onclick="closeModal()">
            <i class="fas fa-arrow-left"></i> Back
        </button>
    </div>

    <script>
        function openModal(imgUrl) {
            var modal = document.getElementById("certModal");
            var modalImg = document.getElementById("modalImg");
            
            modal.style.display = "flex";
            modalImg.src = imgUrl;
        }

        function closeModal() {
            document.getElementById("certModal").style.display = "none";
        }
    </script>

</body>
</html>