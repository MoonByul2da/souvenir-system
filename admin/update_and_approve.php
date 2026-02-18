<?php
session_start();
if (!isset($_SESSION['admin_id'])) { exit("Unauthorized"); }
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        $req_id = $_POST['request_id'];
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];

        $stmt_u = $conn->prepare("UPDATE souvenir_users SET full_name = ?, prefix = ?, department = ? WHERE user_id = ?");
        $stmt_u->execute(array($_POST['full_name'], $_POST['requester_prefix'], $_POST['department'], $user_id));

        $stmt_r = $conn->prepare("UPDATE souvenir_requests SET requester_prefix = ?, purpose = ?, date_required = ?, status = ? WHERE request_id = ?");
        $stmt_r->execute(array($_POST['requester_prefix'], $_POST['purpose'], $_POST['date_required'], $status, $req_id));

        $conn->prepare("DELETE FROM souvenir_request_details WHERE request_id = ?")->execute(array($req_id));
        
        $items_msg = "";
        if (!empty($_POST['items'])) {
            $stmt_d = $conn->prepare("INSERT INTO souvenir_request_details (request_id, item_id, qty_requested, unit, remark) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['items'] as $item) {
                if ($item['qty'] > 0) {
                    $stmt_d->execute(array($req_id, $item['id'], $item['qty'], $item['unit'], $item['remark']));
                    
                    $st_in = $conn->prepare("SELECT item_name FROM souvenir_items WHERE item_id = ?");
                    $st_in->execute(array($item['id']));
                    $name = $st_in->fetchColumn();
                    $items_msg .= "- $name จำนวน {$item['qty']} {$item['unit']}<br>";
                }
            }
        }

        $stmt_m = $conn->prepare("SELECT email, full_name FROM souvenir_users WHERE user_id = ?");
        $stmt_m->execute(array($user_id));
        $u_info = $stmt_m->fetch(PDO::FETCH_ASSOC);

        if ($u_info && !empty($u_info['email'])) {
            $to = $u_info['email'];
            $subject = "อนุมัติรายการเบิกของที่ระลึก (ID: $req_id)";
            $url = "http://" . $_SERVER['HTTP_HOST'] . "/souvenir-system/print_request.php?id=" . $req_id;
            $msg = "<html><body><h3>คำขอของคุณได้รับการอนุมัติแล้ว</h3><p>รายการที่อนุมัติ:<br>$items_msg</p><p><a href='$url' style='padding:10px; background:#0d6efd; color:white; text-decoration:none;'>คลิกพิมพ์ใบเบิก</a></p></body></html>";
            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: ระบบเบิกของ <no-reply@husoc.msu.ac.th>\r\n";
            @mail($to, $subject, $msg, $headers);
        }

        $conn->commit();
        header("Location: dashboard.php?msg=success");
        exit();

    } catch (Exception $e) { $conn->rollBack(); echo $e->getMessage(); }
}