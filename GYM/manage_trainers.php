<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// --- 1. HANDLE ADD TRAINER ---
if (isset($_POST['add_trainer'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $role = $conn->real_escape_string($_POST['role']);
    $phone = $conn->real_escape_string($_POST['phone']);
    
    // Default Placeholders
    $photo_path = "https://cdn-icons-png.flaticon.com/512/8847/8847419.png"; 
    $cert_path = "";

    // Fix: Auto-create folder
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    // A. UPLOAD PROFILE PHOTO
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] == 0) {
        $ext = pathinfo($_FILES["photo_file"]["name"], PATHINFO_EXTENSION);
        $new_name = "trainer_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES["photo_file"]["tmp_name"], $target_dir . $new_name)) {
            $photo_path = $target_dir . $new_name;
        }
    }

    // B. UPLOAD CERTIFICATE
    if (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] == 0) {
        $ext = pathinfo($_FILES["cert_file"]["name"], PATHINFO_EXTENSION);
        $new_name = "cert_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES["cert_file"]["tmp_name"], $target_dir . $new_name)) {
            $cert_path = $target_dir . $new_name;
        }
    }

    $conn->query("INSERT INTO trainers (full_name, specialty, phone, photo_url, certificate_url) VALUES ('$name', '$role', '$phone', '$photo_path', '$cert_path')");
    header("Location: manage_trainers.php");
    exit();
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = $conn->query("SELECT photo_url, certificate_url FROM trainers WHERE id=$id")->fetch_assoc();
    if ($row) {
        if (file_exists($row['photo_url']) && strpos($row['photo_url'], 'uploads/') !== false) unlink($row['photo_url']);
        if (file_exists($row['certificate_url']) && strpos($row['certificate_url'], 'uploads/') !== false) unlink($row['certificate_url']);
    }
    $conn->query("DELETE FROM trainers WHERE id=$id");
    header("Location: manage_trainers.php");
    exit();
}

$trainers = $conn->query("SELECT * FROM trainers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trainers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        :root {
            --sidebar-width: 250px;
            --main-color: #ff4500;
            --dark-bg: #1d2231;
            --light-bg: #f1f5f9;
        }

        body { background: var(--light-bg); display: flex; margin: 0; min-height: 100vh; }
        
        /* --- ORIGINAL SIDEBAR DESIGN --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--dark-bg);
            color: white;
            position: fixed;
            padding: 20px;
            z-index: 100;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: var(--main-color);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu a {
            display: block;
            color: #ccc;
            padding: 15px;
            text-decoration: none;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .menu a:hover, .menu a.active {
            background: var(--main-color);
            color: white;
        }
        .menu i { margin-right: 10px; width: 20px; }
        
        /* --- CONTENT AREA --- */
        .content { margin-left: var(--sidebar-width); padding: 30px; width: 100%; transition: 0.3s; }

        /* FORM CARD */
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h2 { margin-top: 0; color: #333; }
        h3 { margin-top: 0; color: #555; font-size: 1.2rem; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        
        .input-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #666; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; background: #f9f9f9; transition: 0.3s; }
        input[type="text"]:focus { border-color: #ff4500; background: white; outline: none; }

        /* GRAPHICAL FILE INPUT */
        .file-upload-box {
            position: relative;
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: 0.3s;
        }
        .file-upload-box:hover { border-color: #ff4500; background: #fff5f2; }
        
        .file-upload-box input[type="file"] {
            position: absolute;
            width: 100%; height: 100%;
            top: 0; left: 0;
            opacity: 0; /* Hidden but clickable */
            cursor: pointer;
        }
        .file-upload-box i { font-size: 30px; color: #888; margin-bottom: 8px; display: block; }
        .file-upload-box span { font-size: 12px; color: #666; font-weight: 600; }

        .btn-add { 
            background: #ff4500; color: white; border: none; padding: 14px; width: 100%; 
            border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 10px;
            transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-add:hover { background: #e03e00; box-shadow: 0 5px 15px rgba(255, 69, 0, 0.2); }

        /* TABLE STYLING */
        .table-container { overflow-x: auto; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #555; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        td { color: #333; font-size: 14px; vertical-align: middle; }
        
        .preview-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #eee; }
        .cert-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; transition: 0.3s; }
        .cert-thumb:hover { transform: scale(1.1); }

        .btn-del { 
            color: #dc3545; background: #fff5f5; padding: 6px 12px; border-radius: 5px; 
            text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.2s; 
        }
        .btn-del:hover { background: #dc3545; color: white; }

        /* RESPONSIVE CSS (MOBILE) */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { 
                width: 100%; 
                height: auto; 
                position: relative; 
                padding: 10px; 
            }
            .menu { 
                display: flex; 
                overflow-x: auto; 
                padding-bottom: 10px; 
            }
            .menu a { 
                margin-right: 10px; 
                margin-bottom: 0; 
                white-space: nowrap; 
                font-size: 14px; 
                padding: 10px; 
                display: inline-block;
            }
            .content { margin-left: 0; padding: 15px; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            .btn-add { font-size: 14px; padding: 12px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-dumbbell"></i> MELE FITNESS
        </div>
        <div class="menu">
            <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin_qr_station.php"><i class="fas fa-qrcode"></i> QR Station</a>
            <a href="members_list.php"><i class="fas fa-users"></i> Members List</a>
            
            <a href="manage_trainers.php" class="active"><i class="fas fa-user-ninja"></i> Manage Trainers</a>
            
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            
            <a href="logout.php" style="margin-top: 50px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="content">
        <h2>Manage Trainers</h2>

        <div class="form-card">
            <h3><i class="fas fa-user-plus"></i> Add New Trainer</h3>
            <form method="POST" enctype="multipart/form-data">
                
                <div class="form-grid">
                    <div class="left-col">
                        <div class="input-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required placeholder="Ex: Rohit Sharma">
                        </div>
                        <div class="input-group">
                            <label>Specialty</label>
                            <input type="text" name="role" required placeholder="Ex: Bodybuilding & Cardio">
                        </div>
                        <div class="input-group">
                            <label>Phone Number (WhatsApp)</label>
                            <input type="text" name="phone" required placeholder="Ex: 9876543210">
                        </div>
                    </div>

                    <div class="right-col">
                        <label>Profile Photo</label>
                        <div class="file-upload-box input-group">
                            <i class="fas fa-user-circle"></i>
                            <span>Tap to Upload Photo</span>
                            <input type="file" name="photo_file" accept="image/*" required>
                        </div>

                        <label>Certificate Image</label>
                        <div class="file-upload-box input-group">
                            <i class="fas fa-certificate"></i>
                            <span>Tap to Upload Certificate</span>
                            <input type="file" name="cert_file" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <button type="submit" name="add_trainer" class="btn-add">
                    <i class="fas fa-check-circle"></i> Add Trainer to Team
                </button>
            </form>
        </div>

        <h3><i class="fas fa-users"></i> Current Team</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Cert</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($trainers->num_rows > 0): ?>
                        <?php while($row = $trainers->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo $row['photo_url']; ?>" class="preview-img"></td>
                            <td>
                                <?php if(!empty($row['certificate_url'])): ?>
                                    <a href="<?php echo $row['certificate_url']; ?>" target="_blank">
                                        <img src="<?php echo $row['certificate_url']; ?>" class="cert-thumb" title="View Certificate">
                                    </a>
                                <?php else: ?>
                                    <span style="color:#ccc; font-size:12px;">--</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $row['full_name']; ?></strong></td>
                            <td><?php echo $row['specialty']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td>
                                <a href="manage_trainers.php?delete=<?php echo $row['id']; ?>" class="btn-del" onclick="return confirm('Remove this trainer permanently?');"><i class="fas fa-trash"></i> Remove</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#999;">No trainers added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>