<?php

$req_id = isset($_GET['id']) ? $_GET['id'] : 0;

// ดึงข้อมูล Join ตาราง Users
$sql = "SELECT r.*, u.full_name, u.department 
        FROM souvenir_requests r 
        JOIN souvenir_users u ON r.user_id = u.user_id 
        WHERE r.request_id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(array(':id' => $req_id));
$header = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$header) { die("ไม่พบข้อมูลเอกสาร ID: $req_id"); }

// ดึงรายการ
$sql_items = "SELECT d.*, i.item_name 
              FROM souvenir_request_details d 
              JOIN souvenir_items i ON d.item_id = i.item_id 
              WHERE d.request_id = :id";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->execute(array(':id' => $req_id));
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);