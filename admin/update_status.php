<?php
session_start();
// เช็ค Login เพื่อความปลอดภัย
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

require_once '../db_connect.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $req_id = $_GET['id'];
    $new_status = $_GET['status'];

    // ตรวจสอบค่า Status ที่ส่งมาว่าถูกต้องหรือไม่
    if (in_array($new_status, ['Approved', 'Rejected'])) {
        try {
            // เริ่มต้น Transaction (ถ้าพังให้ Rollback กลับทั้งหมด)
            $conn->beginTransaction();

            // 1. เช็คสถานะปัจจุบันก่อน (กันกดซ้ำแล้วตัดของเบิ้ล)
            $stmt_check = $conn->prepare("SELECT status FROM requests WHERE request_id = :id");
            $stmt_check->execute([':id' => $req_id]);
            $current_req = $stmt_check->fetch(PDO::FETCH_ASSOC);

            // ทำงานเฉพาะตอนที่สถานะเดิมเป็น 'Pending' (รออนุมัติ) เท่านั้น
            if ($current_req && $current_req['status'] == 'Pending') {

                // 2. อัปเดตสถานะเป็น Approved หรือ Rejected
                $sql_update = "UPDATE requests SET status = :status WHERE request_id = :id";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute(array(':status' => $new_status, ':id' => $req_id));

                // 3. [สำคัญ] ถ้ากด "อนุมัติ" ให้ไปตัดสต็อก
                if ($new_status == 'Approved') {
                    // ดึงรายการของในใบเบิกนี้
                    $sql_items = "SELECT item_id, qty_requested FROM request_details WHERE request_id = :id";
                    $stmt_items = $conn->prepare($sql_items);
                    $stmt_items->execute([':id' => $req_id]);
                    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                    // วนลูปตัดของทีละชิ้น
                    $sql_deduct = "UPDATE items SET stock = stock - :qty WHERE item_id = :item_id";
                    $stmt_deduct = $conn->prepare($sql_deduct);

                    foreach ($items as $item) {
                        $stmt_deduct->execute([
                            ':qty' => $item['qty_requested'],
                            ':item_id' => $item['item_id']
                        ]);
                    }
                }

                // ยืนยันการทำงานทั้งหมด
                $conn->commit();
            } else {
                // ถ้าสถานะไม่ใช่ Pending (เช่น อนุมัติไปแล้ว) จะไม่ทำอะไร
                $conn->rollBack();
            }
            
            // กลับไปหน้า Dashboard
            header("Location: dashboard.php");
            
        } catch (PDOException $e) {
            // ถ้ามี Error ให้ยกเลิกทั้งหมด
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    } else {
        header("Location: dashboard.php");
    }
} else {
    header("Location: dashboard.php");
}
?>