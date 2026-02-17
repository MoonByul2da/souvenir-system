<?php
require_once 'db_connect.php';

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
        // ส่วนส่งอีเมลแจ้งเตือน
        // ============================================================
        
        // 1. อีเมลปลายทาง (Admin)
        $to = "65011210037@msu.ac.th"; 
        
        // 2. หัวข้ออีเมล
        $subject = "แจ้งเตือน: มีรายการขอเบิกของที่ระลึกใหม่ (คุณ $fullName)";

        // 3. เนื้อหาอีเมล (HTML)
        // สร้างลิงก์สำหรับกดไปดู (เปลี่ยน localhost เป็นชื่อเว็บจริงถ้าออนแอร์แล้ว)
        $link_dashboard = "http://localhost/request/admin/dashboard.php";

        $message = "
        <html>
        <head>
            <title>รายการขอเบิกใหม่</title>
        </head>
        <body style='font-family: Sarabun, sans-serif;'>
            <h3 style='color: #0056b3;'>มีรายการขอเบิกของที่ระลึกเข้ามาใหม่</h3>
            <p><b>ผู้ขอเบิก:</b> $fullName</p>
            <p><b>อีเมลผู้เบิก:</b> $email</p>
            <p><b>หน่วยงาน:</b> $dept</p>
            <p><b>วันที่ต้องการใช้:</b> " . DateThai($_POST['date_required']) . "</p>
            <p><b>วัตถุประสงค์:</b> {$_POST['purpose']}</p>
            <br>
            <p>กรุณาตรวจสอบและอนุมัติที่ลิงก์ด้านล่าง:</p>
            <p>
                <a href='$link_dashboard' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display:inline-block;'>
                    เข้าสู่ระบบหลังบ้าน
                </a>
            </p>
            <br>
            <small style='color: #888;'>ระบบแจ้งเตือนอัตโนมัติ - คณะมนุษยศาสตร์และสังคมศาสตร์</small>
        </body>
        </html>
        ";

        // 4. ตั้งค่า Header
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: ระบบเบิกของ <no-reply@husoc.msu.ac.th>" . "\r\n";

        // 5. ส่งอีเมล (ใส่ @ ดักไว้กัน Error ในเครื่อง Localhost)
        @mail($to, $subject, $message, $headers);

        // ============================================================

        // บันทึกเสร็จแล้วส่งไปหน้าพิมพ์เอกสาร
        header("Location: print_request.php?id=" . $last_req_id);
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>