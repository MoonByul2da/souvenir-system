<?php
require_once '../db_connect.php'; 

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$stmt = $conn->prepare("SELECT r.*, u.full_name, u.department FROM souvenir_requests r 
                        JOIN souvenir_users u ON r.user_id = u.user_id 
                        WHERE r.request_id = ?");
$stmt->execute(array($id));
$header = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM souvenir_request_details WHERE request_id = ?");
$stmt2->execute(array($id));
$details = $stmt2->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(array('header' => $header, 'details' => $details));