<?php
require_once 'db_connect.php';

// นำเข้า PHPMailer ของระบบของที่ระลึก
require_once 'vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // รับค่าจากฟอร์ม
        $fullName = $_POST['full_name'];
        $prefix = isset($_POST['requester_prefix']) ? $_POST['requester_prefix'] : '';
        $position = isset($_POST['requester_position']) ? $_POST['requester_position'] : '';
        $phone = isset($_POST['requester_phone']) ? $_POST['requester_phone'] : '';
        $email = isset($_POST['requester_email']) ? $_POST['requester_email'] : ''; // รับค่าอีเมล
        $dept = isset($_POST['department']) ? $_POST['department'] : '';
        
        $requestDate = isset($_POST['request_date']) ? $_POST['request_date'] . ' ' . date('H:i:s') : date('Y-m-d H:i:s');

        // 1. ตรวจสอบ/บันทึกข้อมูล User
        $stmt_check = $conn->prepare("SELECT user_id FROM souvenir_users WHERE full_name = :name");
        $stmt_check->execute(array(':name' => $fullName));
        $existingUser = $stmt_check->fetch(PDO::FETCH_ASSOC);

        $user_id = 0;

        if ($existingUser) {
            // ถ้ามีชื่อแล้ว -> อัปเดตข้อมูล (รวมอีเมล)
            $user_id = $existingUser['user_id'];
            $stmt_update = $conn->prepare("UPDATE souvenir_users SET prefix=:pf, position=:pos, phone=:ph, email=:em, department=:dept WHERE user_id=:id");
            $stmt_update->execute(array(':pf'=>$prefix, ':pos'=>$position, ':ph'=>$phone, ':em'=>$email, ':dept'=>$dept, ':id'=>$user_id));
        } else {
            // ถ้ายังไม่มี -> เพิ่มใหม่ (รวมอีเมล)
            $stmt_new = $conn->prepare("INSERT INTO souvenir_users (full_name, prefix, position, department, phone, email) VALUES (:name, :pf, :pos, :dept, :ph, :em)");
            $stmt_new->execute(array(':name'=>$fullName, ':pf'=>$prefix, ':pos'=>$position, ':dept'=>$dept, ':ph'=>$phone, ':em'=>$email));
            $user_id = $conn->lastInsertId();
        }

        // 2. บันทึกใบเบิก (Requests)
        $sql_header = "INSERT INTO souvenir_requests (user_id, doc_no, requester_prefix, requester_position, requester_phone, purpose, request_date, date_required, status) 
                       VALUES (:user_id, :doc_no, :pf, :pos, :ph, :purpose, :req_date, :date_required, 'Pending')";
        
        $stmt = $conn->prepare($sql_header);
        $stmt->execute(array(
            ':user_id' => $user_id,
            ':doc_no' => isset($_POST['doc_no']) ? $_POST['doc_no'] : '',
            ':pf' => $prefix,
            ':pos' => $position,
            ':ph' => $phone,
            ':purpose' => $_POST['purpose'],
            ':req_date' => $requestDate,
            ':date_required' => $_POST['date_required']
        ));
        
        $last_req_id = $conn->lastInsertId();

        // 3. บันทึกรายการของ (Request Details)
        $sql_detail = "INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) 
                       VALUES (:req_id, :item_id, :qty, :unit, :remark)";
        $stmt_detail = $conn->prepare($sql_detail);

        if (!empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (isset($item['qty']) && $item['qty'] > 0) {
                    $stmt_detail->execute(array(
                        ':req_id' => $last_req_id,
                        ':item_id' => $item['id'],
                        ':qty' => $item['qty'],
                        ':unit' => $item['unit'],
                        ':remark' => isset($item['remark']) ? $item['remark'] : ''
                    ));
                }
            }
        }

        $conn->commit();

        // ============================================================
        // ✨ เริ่มส่วนส่งอีเมลแจ้งเตือนแอดมิน (Souvenir System) ✨
        // ============================================================
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'husoc.system@msu.ac.th';
            $mail->Password   = 'htch vllf igcu wxjm';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

            $mail->setFrom('husoc.system@msu.ac.th', 'System Notification');
            
            // ใส่อีเมลแอดมินที่ต้องการให้แจ้งเตือน
            $mail->addAddress('65011210037@msu.ac.th', 'Admin Husoc'); 

            $mail->isHTML(true);
            $mail->Subject = "แจ้งเตือน: มีรายการขอเบิกของที่ระลึกใหม่จาก คุณ " . htmlspecialchars($fullName);
            
            $mailBody = "<b>แจ้งเตือนจากระบบเบิกของที่ระลึก</b><br><br>";
            $mailBody .= "มีผู้ส่งคำร้องขอเบิกของที่ระลึกเข้ามาใหม่ กรุณาเข้าสู่ระบบเพื่อตรวจสอบและอนุมัติ<br><br>";
            $mailBody .= "<b>ผู้ขอเบิก:</b> " . htmlspecialchars($prefix) . " " . htmlspecialchars($fullName) . "<br>";
            $mailBody .= "<b>หน่วยงาน:</b> " . htmlspecialchars($dept) . "<br>";
            $mailBody .= "<b>วันที่ต้องการใช้:</b> " . htmlspecialchars($_POST['date_required']) . "<br>";
            $mailBody .= "<b>วัตถุประสงค์:</b> " . htmlspecialchars($_POST['purpose']) . "<br><br>";
            $admin_link = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') . "/login.php";
            $mailBody .= "👉 <a href='" . $admin_link . "'>คลิกที่นี่เพื่อเข้าสู่ระบบจัดการ</a>";
            
            $mail->Body = $mailBody;
            $mail->send();
        } catch (Exception $e) {
            // ปล่อยผ่านไป กรณีอีเมลแจ้งเตือนมีปัญหา
        }
        // ============================================================

        // โค้ดหลังจากบันทึกข้อมูลลง Database เรียบร้อยแล้ว (สำหรับระบบ Ajax/SweetAlert)
        header("Location: success.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>