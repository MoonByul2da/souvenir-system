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

        // เช็คสถานะปัจจุบันก่อน เพื่อป้องกันการตัดสต็อกซ้ำ
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
        $conn->prepare("DELETE FROM souvenir_request_details WHERE request_id = ?")->execute(array($req_id));
        
        $item_names_arr = []; // สำหรับเก็บชื่อสินค้าไปใส่ในอีเมล

        if (!empty($_POST['items'])) {
            $stmt_d = $conn->prepare("INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) VALUES (?, ?, ?, ?, ?)");
            $stmt_deduct = $conn->prepare("UPDATE souvenir_items SET stock = stock - ? WHERE item_id = ?");
            
            // เตรียมคำสั่งดึงชื่อสินค้า
            $stmt_get_name = $conn->prepare("SELECT * FROM souvenir_items WHERE item_id = ?");

            foreach ($_POST['items'] as $item) {
                if ($item['qty'] > 0) {
                    // บันทึกรายการใหม่
                    $stmt_d->execute(array($req_id, $item['id'], $item['qty'], $item['unit'], $item['remark']));
                    
                    // ตัดสต็อก
                    if ($status == 'Approved' && $current_status != 'Approved') {
                        $stmt_deduct->execute(array($item['qty'], $item['id']));
                    }

                    // ดึงชื่อสินค้ามาเก็บไว้สำหรับอีเมล
                    $stmt_get_name->execute(array($item['id']));
                    $itm = $stmt_get_name->fetch(PDO::FETCH_ASSOC);
                    if ($itm) {
                        // ดึงชื่อมารวมไว้ (เช็คทั้ง item_name และ name เผื่อไว้)
                        $item_names_arr[] = isset($itm['item_name']) ? $itm['item_name'] : (isset($itm['name']) ? $itm['name'] : "รหัสสินค้า ".$item['id']);
                    }
                }
            }
        }

        // นำชื่อสินค้ามาต่อกันด้วยลูกน้ำ หรือถ้าไม่มีให้ระบุเลข ID แทน
        $item_text = !empty($item_names_arr) ? implode(', ', $item_names_arr) : "ตามคำขอเลขที่ $req_id";

        // 3. ส่งอีเมลแจ้งผล
        $stmt_m = $conn->prepare("SELECT email, full_name FROM souvenir_users WHERE user_id = ?");
        $stmt_m->execute(array($user_id));
        $u_info = $stmt_m->fetch(PDO::FETCH_ASSOC);
        $mail = new PHPMailer(true);

        if ($u_info && !empty($u_info['email'])) {
            $to = $u_info['email'];

            // จัดรูปแบบข้อความอีเมล
            if ($status == 'Approved') {
                $emailSubject = "แจ้งอนุมัติ: รายการเบิกของที่ระลึก " . $item_text;
                $url = "http://e-human.msu.ac.th/souvenir-system/print_request.php?id=" . $req_id;

                $emailBodyHTML = "เรียน คุณ" . htmlspecialchars($u_info['full_name']) . "<br><br>";
                $emailBodyHTML .= "งานประชาสัมพันธ์ขอแจ้งให้ทราบว่า รายการเบิกของที่ระลึก <b>" . htmlspecialchars($item_text) . "</b> ได้รับการอนุมัติเรียบร้อยแล้ว<br><br>";
                $emailBodyHTML .= "โปรดพิมพ์เอกสารฉบับนี้เพื่อยื่นเป็นหลักฐานในการเบิกของที่ระลึก ณ งานประชาสัมพันธ์ ชั้น 1 คณะมนุษยศาสตร์และสังคมศาสตร์ ท่านสามารถดาวน์โหลดหลักฐานได้ที่ <a href='" . $url . "'>" . $url . "</a><br><br>";
                $emailBodyHTML .= "หากท่านมีข้อสงสัย กรุณาติดต่อสอบถามเจ้าหน้าที่งานประชาสัมพันธ์โดยตรง<br><br>";
                $emailBodyHTML .= "ขอแสดงความนับถือ<br>";
                $emailBodyHTML .= "งานประชาสัมพันธ์ คณะมนุษยศาสตร์และสังคมศาสตร์<br>";
                $emailBodyHTML .= "มหาวิทยาลัยมหาสารคาม เบอร์ภายใน 4703<br>";
                $emailBodyHTML .= "อีเมล: husoc.system@msu.ac.th<br>";
                $emailBodyHTML .= "หมายเหตุ: นี่เป็นข้อความอัตโนมัติจากระบบ กรุณาอย่าตอบกลับอีเมลนี้";

                $emailBodyPlain = strip_tags(str_replace(['<br>', '<br><br>'], "\n", $emailBodyHTML));
            } else {
                $emailSubject = "แจ้งผล: รายการเบิกของที่ระลึก " . $item_text;
                
                $emailBodyHTML = "เรียน คุณ" . htmlspecialchars($u_info['full_name']) . "<br><br>";
                $emailBodyHTML .= "งานประชาสัมพันธ์ขอแจ้งให้ทราบว่า รายการเบิกของที่ระลึก <b>" . htmlspecialchars($item_text) . "</b> <span style='color:red;'>ไม่ได้รับการอนุมัติ</span><br><br>";
                $emailBodyHTML .= "หากท่านมีข้อสงสัย กรุณาติดต่อสอบถามเจ้าหน้าที่งานประชาสัมพันธ์โดยตรง<br><br>";
                $emailBodyHTML .= "ขอแสดงความนับถือ<br>";
                $emailBodyHTML .= "งานประชาสัมพันธ์ คณะมนุษยศาสตร์และสังคมศาสตร์<br>";
                $emailBodyHTML .= "มหาวิทยาลัยมหาสารคาม เบอร์ภายใน 4703<br>";
                $emailBodyHTML .= "อีเมล: husoc.system@msu.ac.th<br>";
                $emailBodyHTML .= "หมายเหตุ: นี่เป็นข้อความอัตโนมัติจากระบบ กรุณาอย่าตอบกลับอีเมลนี้";

                $emailBodyPlain = strip_tags(str_replace(['<br>', '<br><br>'], "\n", $emailBodyHTML));
            }

            // ตั้งค่า PHPMailer
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'husoc.system@msu.ac.th';
            $mail->Password = 'htch vllf igcu wxjm';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom('husoc.system@msu.ac.th', 'Husoc System');
            $mail->addAddress($to, $u_info['full_name']);

            $mail->Subject = $emailSubject;
            
            // เปิดให้ใช้ HTML และยัดข้อมูลเข้าไป
            $mail->isHTML(true);
            $mail->Body = $emailBodyHTML;
            $mail->AltBody = $emailBodyPlain; 

            $mail->send();
        }

        $conn->commit();
        header("Location: dashboard.php?msg=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>