<?php
require_once 'db_connect.php';
require_once 'ajax/get_print_ajax.php';

// ฟังก์ชันเส้นประ (บังคับชิดซ้ายเพื่อให้ชื่อต่อท้ายหัวข้อทันที)
function textWithDots($text, $minWidth = '100px') {
    $content = ($text && $text != '') ? $text : '&nbsp;';
    return '<span class="dotted-line" style="min-width: ' . $minWidth . ';">' . $content . '</span>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บันทึกข้อความ - <?php echo $req_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="img/Husoc_MSU_Logo.png" type="image/png">
    <style>
        /* ตั้งค่าหน้ากระดาษ A4 */
        @page { size: A4 portrait; margin: 1.5cm 2cm 1.5cm 2.5cm; }
        
        body { 
            font-family: 'Sarabun', sans-serif; 
            font-size: 16pt; 
            line-height: 1.6; 
            color: #000; 
        }

        @media print { 
            .no-print { display: none !important; } 
            body { -webkit-print-color-adjust: exact; } 
        }

        .header-container { position: relative; height: 100px; margin-bottom: 10px; }
        .garuda { position: absolute; top: 0; left: 0; width: 60px; height: auto; }
        .title { text-align: center; font-weight: bold; font-size: 24pt; padding-top: 15px; }

        .line-item { 
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: left; 
        }

        .indent-body {
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: left;
            text-indent: 2.5cm; 
        }

        .dotted-line { 
            border-bottom: 1px dotted #333; 
            display: inline-block; 
            text-align: left;       
            padding-left: 5px;      
            padding-right: 5px;     
            vertical-align: bottom; 
            white-space: nowrap;
            text-indent: 0;         
        }

        /* ตาราง */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; font-size: 15pt; vertical-align: middle; }
        tbody tr { height: 30px; }
        th { font-weight: bold; background-color: #f0f0f0 !important; }

        /* ส่วนลงชื่อ */
        .signature-section {
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            width: 100%;
            margin-top: 30px;
        }

        .sign-box { width: 45%; text-align: center; line-height: 1.6; }
        .date-container { display: block; margin-top: 5px; text-align: left; padding-left: 10%; }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; margin: 20px; padding: 20px; background: #eee;">
        <button onclick="window.print()" style="padding:10px 25px; cursor:pointer;">🖨️ พิมพ์หน้านี้</button>
        <a href="./" style="margin-left: 20px;">← กลับหน้าหลัก</a>
    </div>

    <div class="header-container">
        <img src="img/garuda.png" class="garuda" alt="ครุฑ" onerror="this.style.display='none'">
        <div class="title">บันทึกข้อความ</div>
    </div>

    <div style="margin-bottom: 5px; white-space: nowrap;">
        <b>ส่วนราชการ</b> งานประชาสัมพันธ์ คณะมนุษยศาสตร์และสังคมศาสตร์ &nbsp;&nbsp;&nbsp; โทร. 4703
    </div>
    
    <div style="margin-bottom: 5px;">
        <div style="float: left; width: 50%;">
            <b>ที่</b> อว 0605.3/<?php echo textWithDots(isset($header['doc_no']) ? $header['doc_no'] : '', '120px'); ?>
        </div>
        <div style="float: left; width: 50%;">
            <?php 
                $ts = strtotime($header['request_date']);
                $d = date('j', $ts); 
                $m = date('n', $ts); 
                $y = date('Y', $ts) + 543; 
                $months = array("","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม");
                $gap = str_repeat("&nbsp;", 5);
                $full_date = $d . $gap . $months[$m] . $gap . $y;
            ?>
            <div class="date-container">
                <b>วันที่</b> <?php echo textWithDots($full_date, '200px'); ?>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div style="margin-bottom: 5px;">
        <b>เรื่อง</b> ขอเบิกของที่ระลึกคณะมนุษยศาสตร์และสังคมศาสตร์
    </div>
    
    <div style="margin-bottom: 10px;">
        <b>เรียน</b> รองคณบดีฝ่ายกิจการต่างประเทศและประชาสัมพันธ์
    </div>

    <div class="indent-body">
        ข้าพเจ้า<?php echo textWithDots($header['requester_prefix'] . '. ' . $header['full_name'], '210px'); ?>ตำแหน่ง<?php echo textWithDots($header['requester_position'], '160px'); ?>
    </div>

    <div class="line-item">
        ฝ่าย/แผนก<?php echo textWithDots($header['department'], '220px'); ?>เบอร์โทรศัพท์<?php echo textWithDots($header['requester_phone'], '150px'); ?>
    </div>

    <div class="line-item">
        มีความประสงค์ขอเบิกของที่ระลึก เพื่อนำไปใช้วัตถุประสงค์: <?php echo textWithDots($header['purpose'], 'auto'); ?>
    </div>

    <div class="line-item">
        <?php 
            $ts_use = strtotime($header['date_required']);
            $d_use = date('j', $ts_use); 
            $m_use = date('n', $ts_use); 
            $y_use = date('Y', $ts_use) + 543; 
        ?>
        กำหนดวันที่ต้องการใช้งาน: <?php echo textWithDots($d_use, '20px'); ?> / <?php echo textWithDots($months[$m_use], '70px'); ?> / <?php echo textWithDots($y_use, '30px'); ?>
    </div>

    <div style="margin-top: 15px;">โดยมีรายการดังต่อไปนี้</div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ลำดับที่</th>
                <th style="width: 45%;">รายการของที่ระลึก</th>
                <th style="width: 20%;">จำนวนที่อนุมัติ</th>
                <th style="width: 25%;">หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            if (count($items) > 0) {
                foreach ($items as $item) { ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td style="text-align: left;"><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['qty_requested'] . ' ' . $item['unit']; ?></td>
                        <td><?php echo $item['remark']; ?></td>
                    </tr>
                <?php } 
            }
            $rows_to_fill = 4 - count($items);
            if ($rows_to_fill < 0) $rows_to_fill = 0;
            for($j=0; $j < $rows_to_fill; $j++) { ?>
                <tr><td><?php echo $i++; ?></td><td></td><td></td><td></td></tr>
            <?php } ?>
        </tbody>
    </table>

    <p style="text-align: center; margin-top: 20px; margin-bottom: 30px;">จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ</p>

    <div class="signature-section">
        <div class="sign-box">
            ลงชื่อ......................................................(ผู้ขอเบิก)<br>
            ( <?php echo ($header['full_name']) ? $header['full_name'] : '......................................................'; ?> )<br>
            <div class="date-container">
                <b>วันที่</b> <?php echo textWithDots($d, '20px'); ?> / <?php echo textWithDots($months[$m], '70px'); ?> / <?php echo textWithDots($y, '30px'); ?>
            </div>
        </div>

        <div class="sign-box">
            ลงชื่อ......................................................(ผู้อนุมัติ)<br>
            ( .................................... )<br>
            <div class="date-container">
                <b>วันที่</b> <?php echo textWithDots($d, '20px'); ?> / <?php echo textWithDots($months[$m], '70px'); ?> / <?php echo textWithDots($y, '30px'); ?>
            </div>
        </div>
    </div>
</body>
</html>