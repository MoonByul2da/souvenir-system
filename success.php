<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกสำเร็จ - ระบบเบิกของที่ระลึก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --msu-yellow: #F2CD00; --msu-gray: #4D4D4F; --husoc-bg: #f4f6f9; }
        body { background-color: var(--husoc-bg); font-family: 'Sarabun', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .success-card { background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; text-align: center; max-width: 500px; width: 100%; border-top: 5px solid #198754; }
        .success-icon { font-size: 80px; color: #198754; margin-bottom: 20px; }
        .btn-msu { background-color: var(--msu-yellow); color: #333; font-weight: bold; border: none; }
        .btn-msu:hover { background-color: #dcb300; }
    </style>
</head>
<body>

    <div class="success-card">
        <i class="bi bi-check-circle-fill success-icon"></i>
        <h2 class="mb-3" style="color: var(--msu-gray); font-weight: 600;">บันทึกคำร้องสำเร็จ!</h2>
        <p class="text-muted mb-4 fs-5">
            ระบบได้รับข้อมูลของคุณเรียบร้อยแล้ว<br>
            <strong class="text-danger">กรุณารออีเมลแจ้งเตือน</strong><br>
            เพื่อนำหลักฐานไปติดต่อขอรับของที่ระลึก
        </p>
        
        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mt-4">
            <a href="form_request.php" class="btn btn-msu px-4 py-2">
                <i class="bi bi-file-earmark-plus"></i> กรอกฟอร์มเพิ่ม
            </a>
            
            <button onclick="closeWindowOrRedirect()" class="btn btn-outline-secondary px-4 py-2">
                <i class="bi bi-x-lg"></i> ปิดหน้าต่าง
            </button>
        </div>
    </div>

    <script>
        function closeWindowOrRedirect() {
            // คำสั่งปิดหน้าต่าง (บางเบราว์เซอร์อาจบล็อกถ้าไม่ได้เปิดหน้านี้ด้วยสคริปต์)
            window.close();
            // ถ้าปิดไม่ได้ ให้ย้อนกลับไปหน้าแรกแทน (แก้ index.php เป็นหน้าหลักของเว็บคุณได้เลย)
            setTimeout(() => { window.location.href = 'index.php'; }, 300);
        }
    </script>
</body>
</html>