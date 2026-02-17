<?php
// เริ่ม Session และเช็ค Login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// เช็คชื่อไฟล์ปัจจุบันเพื่อทำไฮไลท์เมนู
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการหลังบ้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../img/Husoc_MSU_Logo.png" type="image/png">

    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .nav-admin { background-color: #343a40; color: white; padding: 15px; }
        .nav-admin a { color: #cfd2d6; text-decoration: none; margin-right: 20px; transition: 0.3s; }
        .nav-admin a:hover, .nav-admin a.active { color: #fff; font-weight: bold; }
        
        /* Status Badge */
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; }
        .bg-pending { background-color: #ffc107; color: #000; }
        .bg-approved { background-color: #198754; color: #fff; }
        .bg-rejected { background-color: #dc3545; color: #fff; }
        
        /* Card Stat */
        .card-stat { border: none; border-radius: 10px; color: white; }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    </style>
</head>
<body>

    <div class="nav-admin d-flex align-items-center justify-content-between sticky-top shadow-sm">
        <div class="d-flex align-items-center">
            <h4 class="m-0 me-4 ms-2"><i class="bi bi-shield-check"></i> ระบบเบิกของที่ระลึก</h4>
            
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-list-check"></i> รายการเบิก
            </a>
            
            <a href="items.php" class="<?php echo ($current_page == 'items.php') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> จัดการของ
            </a>
            
            <a href="report_summary.php" class="<?php echo ($current_page == 'report_summary.php') ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart-line"></i> รายงานสรุป
            </a>
        </div>

        <div class="d-flex align-items-center me-3">
            <span class="me-3 text-light d-none d-md-block">
                <i class="bi bi-person-circle"></i> <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?>
            </span>
            <a href="../form_request.php" target="_blank" class="btn btn-outline-light btn-sm me-2">หน้าฟอร์ม</a>
            <button onclick="confirmLogout()" class="btn btn-danger btn-sm">ออกจากระบบ</button>
        </div>
    </div>

    <script>
    function confirmLogout() {
        Swal.fire({
            title: 'ต้องการออกจากระบบ?',
            text: "คุณต้องเข้าสู่ระบบใหม่เพื่อใช้งานอีกครั้ง",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ออกจากระบบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        })
    }
    </script>