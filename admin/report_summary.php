<?php
require_once 'header.php';
require_once '../db_connect.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'email';

if ($mode == 'name') {
    $groupBy = "u.full_name";
    $selectField = "u.full_name";
    $colTitle = "ชื่อ-นามสกุล";
    $condition = "u.full_name != ''";
    $activeCard = 'name'; 
} else {
    $groupBy = "u.email";
    $selectField = "u.email";
    $colTitle = "อีเมล";
    $condition = "u.email != ''";
    $activeCard = 'email';
}

$sql = "SELECT $selectField as identify_val, 
        MAX(u.department) as department, 
        MAX(u.position) as position,
        MAX(u.full_name) as full_name,
        MAX(u.email) as email,
        COUNT(DISTINCT r.request_id) as total_times, 
        SUM(rd.qty_requested) as total_qty
        FROM souvenir_users u
        JOIN souvenir_requests r ON u.user_id = r.user_id
        JOIN souvenir_request_details rd ON r.request_id = rd.request_id
        WHERE r.status = 'Approved' AND $condition
        GROUP BY $groupBy
        ORDER BY total_qty DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_all_items = 0;
foreach($reports as $rep) { $total_all_items += $rep['total_qty']; }
?>

<style>
    .card-hover { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important; }
    .card-active { border: 2px solid #fff; outline: 3px solid #ffc107; }
</style>

<div class="container mt-4">
    
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <a href="?mode=email" class="text-decoration-none">
                <div class="card card-stat bg-gradient-primary shadow-sm h-100 card-hover <?php echo ($activeCard == 'email') ? 'card-active' : ''; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-envelope-at"></i> ยอดเบิก (แยกตามอีเมล)</h5>
                        <h2 class="fw-bold"><?php echo ($activeCard == 'email') ? number_format($total_all_items) : '-'; ?> <span class="fs-6">ชิ้น</span></h2>
                        <p class="mb-0 small opacity-75">
                            <?php if($activeCard == 'email'): ?>
                                จากทั้งหมด <?php echo count($reports); ?> อีเมล
                            <?php else: ?>
                                คลิกเพื่อดูรายงานแบบรวมอีเมล
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4 mb-3">
            <a href="?mode=name" class="text-decoration-none">
                <div class="card card-stat bg-success text-white shadow-sm h-100 card-hover <?php echo ($activeCard == 'name') ? 'card-active' : ''; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person-lines-fill"></i> ยอดเบิก (แยกตามชื่อ)</h5>
                        <h2 class="fw-bold"><?php echo ($activeCard == 'name') ? number_format($total_all_items) : '-'; ?> <span class="fs-6">ชิ้น</span></h2>
                        <p class="mb-0 small opacity-75">
                            <?php if($activeCard == 'name'): ?>
                                จากทั้งหมด <?php echo count($reports); ?> รายชื่อ
                            <?php else: ?>
                                คลิกเพื่อดูรายงานแบบรวมชื่อ
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 text-primary">
                <i class="bi bi-table"></i> สรุปการเบิก (<?php echo ($mode == 'name') ? 'แยกตามรายชื่อ' : 'แยกตามอีเมล'; ?>)
            </h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> พิมพ์รายงาน</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">อันดับ</th>
                            
                            <th width="<?php echo ($mode == 'name') ? '25%' : '45%'; ?>">
                                <?php echo $colTitle; ?>
                            </th>
                            
                            <?php if ($mode == 'name'): ?>
                                <th width="20%">อีเมล</th>
                            <?php endif; ?>
                            
                            <th width="15%">แผนก/หน่วยงาน</th>
                            <th width="15%">ตำแหน่ง</th>
                            <th width="10%" class="text-center">จำนวนครั้ง</th>
                            <th width="10%" class="text-center">รวมยอด (ชิ้น)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach($reports as $row): 
                        ?>
                            <tr>
                                <td class="text-center fw-bold"><?php echo $rank++; ?></td>
                                
                                <td class="fw-bold text-primary">
                                    <?php echo ($mode == 'name') ? $row['full_name'] : $row['email']; ?>
                                </td>
                                
                                <?php if ($mode == 'name'): ?>
                                    <td class="text-muted small"><?php echo ($row['email']) ? $row['email'] : '-'; ?></td>
                                <?php endif; ?>
                                
                                <td><?php echo $row['department']; ?></td>
                                <td><?php echo $row['position']; ?></td>
                                <td class="text-center"><?php echo number_format($row['total_times']); ?></td>
                                <td class="text-center fw-bold text-success" style="font-size: 1.1em;">
                                    <?php echo number_format($row['total_qty']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($reports) == 0): ?>
                            <tr>
                                <td colspan="<?php echo ($mode == 'name') ? '7' : '6'; ?>" class="text-center py-4 text-muted">
                                    ยังไม่มีข้อมูลการเบิกที่อนุมัติแล้ว
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>