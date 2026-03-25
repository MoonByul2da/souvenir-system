<?php
session_start();
require_once 'db_connect.php';

// ถ้า Login อยู่แล้ว ให้เด้งไปหน้า Dashboard เลย
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_login'])) {
    header('Content-Type: application/json; charset=utf-8');

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        echo json_encode([
            'success' => false,
            'message' => 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน'
        ]);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM souvenir_admins WHERE username = :user LIMIT 1");
        $stmt->execute([':user' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบชื่อผู้ใช้งานนี้'
            ]);
            exit();
        }

        $inputHash = sha1(sha1($password));
        if (hash_equals($row['password'], $inputHash)) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['username'];

            echo json_encode([
                'success' => true,
                'redirect' => 'admin/dashboard.php'
            ]);
            exit();
        }

        echo json_encode([
            'success' => false,
            'message' => 'รหัสผ่านไม่ถูกต้อง'
        ]);
        exit();
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ]);
        exit();
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
    <link rel="icon" href="img/Husoc_MSU_Logo.png" type="image/png">
    <style>
        body { background-color: #f4f6f9; font-family: 'Sarabun', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 10px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .logo-login { width: 80px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <img src="img/Husoc_MSU_Logo.png" class="logo-login" onerror="this.style.display='none'">
        <h4 class="mb-4 fw-bold">ผู้ดูแลระบบ</h4>

        <div id="loginAlert" class="alert alert-danger py-2 d-none"></div>

        <form id="loginForm" method="POST" novalidate>
            <input type="hidden" name="login" value="1">
            <div class="mb-3 text-start">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" id="loginBtn" class="btn btn-primary w-100 py-2">เข้าสู่ระบบ</button>
            <a href="form_request.php" class="btn btn-link mt-3 text-decoration-none text-muted btn-sm">← กลับหน้าหลัก</a>
        </form>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loginAlert = document.getElementById('loginAlert');

        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            loginAlert.classList.add('d-none');
            loginAlert.textContent = '';
            loginBtn.disabled = true;

            const formData = new FormData(loginForm);

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect || 'admin/dashboard.php';
                    return;
                }

                loginAlert.textContent = result.message || 'ไม่สามารถเข้าสู่ระบบได้';
                loginAlert.classList.remove('d-none');
            } catch (error) {
                loginAlert.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                loginAlert.classList.remove('d-none');
            } finally {
                loginBtn.disabled = false;
            }
        });
    </script>
</body>
</html>