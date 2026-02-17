<?php
require_once 'db_connect.php'; 

$users = $conn->query("SELECT * FROM souvenir_users ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users_json = json_encode($users);

$items = $conn->query("SELECT * FROM souvenir_items")->fetchAll(PDO::FETCH_ASSOC);
$items_json = json_encode($items); 


