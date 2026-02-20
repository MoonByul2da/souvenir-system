<?php
session_start();
if (!isset($_SESSION['admin_id'])) { exit("Unauthorized"); }
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        $req_id = $_POST['request_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status']; // รับค่า 'Approved' หรือ 'Rejected'

        // 1. อัปเดตข้อมูลผู้ใช้และหัวข้อใบเบิก
        $stmt_u = $conn->prepare("UPDATE souvenir_users SET full_name = ?, prefix = ?, department = ? WHERE user_id = ?");
        $stmt_u->execute(array($_POST['full_name'], $_POST['requester_prefix'], $_POST['department'], $user_id));

        $stmt_r = $conn->prepare("UPDATE souvenir_requests SET requester_prefix = ?, purpose = ?, date_required = ?, status = ? WHERE request_id = ?");
        $stmt_r->execute(array($_POST['requester_prefix'], $_POST['purpose'], $_POST['date_required'], $status, $req_id));

        // 2. จัดการรายการของ (อัปเดตตามที่เจ้าหน้าที่แก้ไขใน Modal)
        $conn->prepare("DELETE FROM souvenir_request_details WHERE request_id = ?")->execute(array($req_id));
        if (!empty($_POST['items'])) {
            $stmt_d = $conn->prepare("INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['items'] as $item) {
                if ($item['qty'] > 0) {
                    $stmt_d->execute(array($req_id, $item['id'], $item['qty'], $item['unit'], $item['remark']));
                }
            }
        }

        // 3. ส่งอีเมลแจ้งผลตามสถานะที่เลือก
        $stmt_m = $conn->prepare("SELECT email, full_name FROM souvenir_users WHERE user_id = ?");
        $stmt_m->execute(array($user_id));
        $u_info = $stmt_m->fetch(PDO::FETCH_ASSOC);

        if ($u_info && !empty($u_info['email'])) {
            $to = $u_info['email'];
            
            if ($status == 'Approved') {
                $subject = "อนุมัติ: รายการเบิกของที่ระลึก (ID: $req_id)";
                $msg_status = "ได้รับการอนุมัติเรียบร้อยแล้ว";
                $color = "#198754";
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/souvenir-system/print_request.php?id=" . $req_id;
                $action = "<p><a href='$url' style='padding:10px; background:#0d6efd; color:white; text-decoration:none; border-radius:5px;'>คลิกพิมพ์ใบเบิก</a></p>";
            } else {
                $subject = "แจ้งผล: รายการเบิกของที่ระลึก (ID: $req_id)";
                $msg_status = "ไม่ได้รับการอนุมัติ";
                $color = "#dc3545";
                $action = "<p>หากท่านมีข้อสงสัย กรุณาติดต่อสอบถามเจ้าหน้าที่งานประชาสัมพันธ์โดยตรง</p>";
            }

            $message = "<html><body style='font-family: Sarabun, sans-serif;'>
                <h3 style='color: $color;'>รายการคำขอเบิกของท่าน $msg_status</h3>
                <p>เรียน คุณ {$u_info['full_name']},</p>
                $action
                <br><small style='color:#888;'>ระบบแจ้งเตือนอัตโนมัติ - คณะมนุษยศาสตร์และสังคมศาสตร์</small>
                </body></html>";

            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: ระบบเบิกของ <no-reply@husoc.msu.ac.th>\r\n";
            @mail($to, $subject, $message, $headers);
        }

        $conn->commit();
        
        // เมื่อบันทึกและส่งอีเมลเสร็จสิ้น ให้ส่งกลับมาที่หน้า Dashboard เพื่อรีเฟรชตารางใหม่
        header("Location: dashboard.php?msg=success");
        exit();

    } catch (Exception $e) { $conn->rollBack(); echo $e->getMessage(); }
}
?>