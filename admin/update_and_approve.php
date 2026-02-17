<?php
session_start();
if (!isset($_SESSION['admin_id'])) { exit("Unauthorized"); }
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        $req_id = $_POST['request_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status']; // รับค่า 'Approved'

        // 1. อัปเดตข้อมูลผู้ขอเบิกในตาราง souvenir_users
        $sql_user = "UPDATE souvenir_users SET full_name = :name, prefix = :pf, position = :pos, department = :dept WHERE user_id = :uid";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([
            ':name' => $_POST['full_name'],
            ':pf'   => $_POST['requester_prefix'],
            ':pos'  => $_POST['requester_position'],
            ':dept' => $_POST['department'],
            ':uid'  => $user_id
        ]);

        // 2. อัปเดตข้อมูลการเบิกในตาราง souvenir_requests
        $sql_req = "UPDATE souvenir_requests SET 
                    requester_prefix = :pf, requester_position = :pos, 
                    purpose = :purpose, date_required = :dr, status = :status 
                    WHERE request_id = :rid";
        $stmt_req = $conn->prepare($sql_req);
        $stmt_req->execute([
            ':pf'      => $_POST['requester_prefix'],
            ':pos'     => $_POST['requester_position'],
            ':purpose' => $_POST['purpose'],
            ':dr'      => $_POST['date_required'],
            ':status'  => $status,
            ':rid'     => $req_id
        ]);

        // 3. จัดการรายการของ (ลบของเดิมแล้วเพิ่มใหม่ตามที่แก้ไข) ในตาราง souvenir_request_details
        $conn->prepare("DELETE FROM souvenir_request_details WHERE request_id = :rid")->execute([':rid' => $req_id]);

        $items_html = ""; 
        if (!empty($_POST['items'])) {
            $sql_detail = "INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) 
                           VALUES (:rid, :item_id, :qty, :unit, :remark)";
            $stmt_detail = $conn->prepare($sql_detail);
            foreach ($_POST['items'] as $item) {
                if ($item['qty'] > 0) {
                    $stmt_detail->execute([
                        ':rid'     => $req_id,
                        ':item_id' => $item['id'],
                        ':qty'     => $item['qty'],
                        ':unit'    => $item['unit'],
                        ':remark'  => $item['remark']
                    ]);
                    
                    // ดึงชื่อสินค้ามาแสดงในอีเมล
                    $st_item = $conn->prepare("SELECT item_name FROM souvenir_items WHERE item_id = ?");
                    $st_item->execute([$item['id']]);
                    $iname = $st_item->fetchColumn();
                    $items_html .= "<li>$iname จำนวน {$item['qty']} {$item['unit']}</li>";
                }
            }
        }

        // 4. ส่วนการส่งอีเมลแจ้งเตือนไปยังผู้เบิก (Requester)
        $stmt_mail = $conn->prepare("SELECT email, full_name FROM souvenir_users WHERE user_id = ?");
        $stmt_mail->execute([$user_id]);
        $user_info = $stmt_mail->fetch(PDO::FETCH_ASSOC);

        if ($user_info && !empty($user_info['email'])) {
            $to = $user_info['email']; // ส่งไปยังอีเมลที่ผู้ส่งกรอกมา
            $subject = "แจ้งผลการอนุมัติรายการเบิกของที่ระลึก (ID: $req_id)";
            
            // ลิงก์สำหรับพิมพ์ใบเบิก (ปรับ URL ตามจริงบน Server)
            $print_url = "http://" . $_SERVER['HTTP_HOST'] . "/souvenir-system/print_request.php?id=" . $req_id;

            $message = "
            <html>
            <head><title>แจ้งผลการอนุมัติ</title></head>
            <body style='font-family: Sarabun, sans-serif;'>
                <h3 style='color: #198754;'>คำขอเบิกของที่ระลึกของคุณได้รับการอนุมัติแล้ว</h3>
                <p>เรียน คุณ {$user_info['full_name']},</p>
                <p>รายการที่คุณขอเบิกได้รับการตรวจสอบและอนุมัติโดยเจ้าหน้าที่เรียบร้อยแล้ว โดยมีรายละเอียดดังนี้:</p>
                <ul>$items_html</ul>
                <p>ท่านสามารถคลิกที่ปุ่มด้านล่างเพื่อพิมพ์ใบเบิกและนำไปติดต่อรับของได้ที่งานประชาสัมพันธ์:</p>
                <p><a href='$print_url' style='background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display:inline-block;'>พิมพ์ใบขอเบิก</a></p>
                <br>
                <small style='color: #888;'>ระบบแจ้งเตือนอัตโนมัติ - คณะมนุษยศาสตร์และสังคมศาสตร์ มหาวิทยาลัยมหาสารคาม</small>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: ระบบเบิกของที่ระลึก <no-reply@husoc.msu.ac.th>" . "\r\n";

            @mail($to, $subject, $message, $headers);
        }

        $conn->commit();
        
        // เมื่อบันทึกสำเร็จ ส่ง Admin ไปที่หน้าพิมพ์เอกสาร
        header("Location: ../print_request.php?id=" . $req_id);
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>