<?php
date_default_timezone_set('Asia/Bangkok');

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "pst_erp_datacenter"; 

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET time_zone = '+07:00'");
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

if (!function_exists('DateThai')) {
    function DateThai($strDate) {
        if(!$strDate || $strDate == '0000-00-00 00:00:00' || $strDate == '0000-00-00') return "........................";
        $strYear = date("Y",strtotime($strDate))+543;
        $strMonth= date("n",strtotime($strDate));
        $strDay= date("j",strtotime($strDate));
        $strHour= date("H",strtotime($strDate));
        $strMinute= date("i",strtotime($strDate));
        $strSeconds= date("s",strtotime($strDate));
        $strMonthCut = Array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
        
        return "$strDay " . $strMonthCut[$strMonth] . " $strYear";
    }
}
?>