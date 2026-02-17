<?php
session_start();
require_once '../db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// ถ้า Login อยู่แล้ว ให้เด้งไปหน้า Dashboard เลย
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM souvenir_admins WHERE username = :user");
        $stmt->execute(array(':user' => $username));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // ตรวจสอบรหัสผ่าน (ใช้ password_verify สำหรับ Hash)
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_name'] = $row['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ไม่พบชื่อผู้ใช้งานนี้';
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบผู้ดูแล - ระบบเบิกของที่ระลึก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="../img/Husoc_MSU_Logo.png" type="image/png">
    <style>
        body { background-color: #f4f6f9; font-family: 'Sarabun', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 10px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .logo-login { width: 80px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <img src="../img/Husoc_MSU_Logo.png" class="logo-login" onerror="this.style.display='none'">
        <h4 class="mb-4 fw-bold">ผู้ดูแลระบบ</h4>
        
        <?php if($error): ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">เข้าสู่ระบบ</button>
            <a href="../form_request.php" class="btn btn-link mt-3 text-decoration-none text-muted btn-sm">← กลับหน้าหลัก</a>
        </form>
    </div>
</body>
</html>