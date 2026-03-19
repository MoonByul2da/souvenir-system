<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    exit("Unauthorized");
}
require_once '../db_connect.php';
require_once '../vendor/autoload.php'; // เพิ่มการโหลด PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        $req_id = $_POST['request_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status']; // รับค่า 'Approved' หรือ 'Rejected'

        // เช็คสถานะปัจจุบันก่อน เพื่อป้องกันการตัดสต็อกซ้ำ (กรณีที่เคยอนุมัติไปแล้ว)
        $stmt_check = $conn->prepare("SELECT status FROM souvenir_requests WHERE request_id = ?");
        $stmt_check->execute([$req_id]);
        $current_req = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $current_status = $current_req ? $current_req['status'] : '';

        // 1. อัปเดตข้อมูลผู้ใช้และหัวข้อใบเบิก
        $stmt_u = $conn->prepare("UPDATE souvenir_users SET full_name = ?, prefix = ?, department = ? WHERE user_id = ?");
        $stmt_u->execute(array($_POST['full_name'], $_POST['requester_prefix'], $_POST['department'], $user_id));

        $stmt_r = $conn->prepare("UPDATE souvenir_requests SET requester_prefix = ?, purpose = ?, date_required = ?, status = ? WHERE request_id = ?");
        $stmt_r->execute(array($_POST['requester_prefix'], $_POST['purpose'], $_POST['date_required'], $status, $req_id));

        // 2. จัดการรายการของ และ ตัดสต็อก
        // ลบรายการเก่าออกก่อนเพื่อลงรายการใหม่ตามที่เจ้าหน้าที่อาจจะแก้ไข
        $conn->prepare("DELETE FROM souvenir_request_details WHERE request_id = ?")->execute(array($req_id));
        
        if (!empty($_POST['items'])) {
            // เตรียมคำสั่งเพิ่มรายการใหม่
            $stmt_d = $conn->prepare("INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) VALUES (?, ?, ?, ?, ?)");
            
            // แก้ไขตรงนี้: เปลี่ยนจาก items เป็น souvenir_items ตาม Database ของคุณ
            $stmt_deduct = $conn->prepare("UPDATE souvenir_items SET stock = stock - ? WHERE item_id = ?");

            foreach ($_POST['items'] as $item) {
                if ($item['qty'] > 0) {
                    // 2.1 บันทึกรายการใหม่ลงฐานข้อมูล
                    $stmt_d->execute(array($req_id, $item['id'], $item['qty'], $item['unit'], $item['remark']));
                    
                    // 2.2 ตัดสต็อก เฉพาะกรณีที่เปลี่ยนสถานะเป็น Approved และสถานะเดิมยังไม่ใช่ Approved
                    if ($status == 'Approved' && $current_status != 'Approved') {
                        $stmt_deduct->execute(array($item['qty'], $item['id']));
                    }
                }
            }
        }

        // 3. ส่งอีเมลแจ้งผลตามสถานะที่เลือก
        $stmt_m = $conn->prepare("SELECT email, full_name FROM souvenir_users WHERE user_id = ?");
        $stmt_m->execute(array($user_id));
        $u_info = $stmt_m->fetch(PDO::FETCH_ASSOC);
        $mail = new PHPMailer(true);

        if ($u_info && !empty($u_info['email'])) {
            $to = $u_info['email'];

            if ($status == 'Approved') {
                $subject = "อนุมัติ: รายการเบิกของที่ระลึก (ID: $req_id)";
                $msg_status = "ได้รับการอนุมัติเรียบร้อยแล้ว";
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/souvenir-system/print_request.php?id=" . $req_id;
            } else {
                $subject = "แจ้งผล: รายการเบิกของที่ระลึก (ID: $req_id)";
                $msg_status = "ไม่ได้รับการอนุมัติ";
            }
            $emailSubject = $subject;
            $emailBody = "เรียน คุณ" . $u_info['full_name'] . "\n\n";
            $emailBody .= $subject."\n\n";
            $emailBody .= "ขอแจ้งให้ทราบว่า รายการเบิกของที่ระลึก (ID: $req_id) $msg_status\n\n";
            if($status == 'Approved') {
                $emailBody .= "ท่านสามารถพิมพ์ใบคำขอได้ที่ <a href='$url'>$url</a>\n\n";
            }
            $emailBody .= "หากท่านมีข้อสงสัย กรุณาติดต่อสอบถามเจ้าหน้าที่งานประชาสัมพันธ์โดยตรง\n\n";
            $emailBody .= "ขอแสดงความนับถือ\n\n";
            $emailBody .= "คณะมนุษยศาสตร์และสังคมศาสตร์\n";
            $emailBody .= "อีเมล: husoc.system@msu.ac.th\n\n";
            $emailBody .= "หมายเหตุ: นี่เป็นข้อความอัตโนมัติจากระบบ กรุณาอย่าตอบกลับอีเมลนี้\n\n\n";

            // Set charset for Thai language support
            $mail->CharSet = 'UTF-8';

            // ตั้งค่าเซิร์ฟเวอร์ SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'husoc.system@msu.ac.th';
            $mail->Password = 'htch vllf igcu wxjm';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // แก้ไขตรงนี้: เพิ่ม SMTPOptions เพื่อข้ามการตรวจสอบ SSL/TLS ใน Localhost (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // ข้อมูลอีเมล
            $mail->setFrom('husoc.system@msu.ac.th', 'Husoc System');
            $mail->addAddress($to, $u_info['email']);

            $mail->Subject = $emailSubject;

            // Convert plain text body to HTML format
            $mail->isHTML(true);
            $mail->Body = nl2br(htmlspecialchars($emailBody));
            $mail->AltBody = $emailBody; 

            $mail->send();
        }

        $conn->commit();

        // เมื่อบันทึก ตัดสต็อก และส่งอีเมลเสร็จสิ้น ให้ส่งกลับมาที่หน้า Dashboard
        header("Location: dashboard.php?msg=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>