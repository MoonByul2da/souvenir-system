<?php
require_once 'header.php'; 
require_once '../db_connect.php'; 

$sql = "SELECT r.*, u.full_name, u.department, u.phone, u.email, 
        (SELECT GROUP_CONCAT(CONCAT(i.item_name, ' (', rd.qty_requested, ' ', rd.unit, ')') SEPARATOR ', ') 
         FROM souvenir_request_details rd 
         JOIN souvenir_items i ON rd.item_id = i.item_id 
         WHERE rd.request_id = r.request_id) as item_list
        FROM souvenir_requests r 
        JOIN souvenir_users u ON r.user_id = u.user_id 
        ORDER BY r.date_required ASC, r.request_id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="m-0"><i class="bi bi-table"></i> รายการขอเบิก</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="15%">อีเมล</th> 
                            <th width="10%">วันที่ใช้</th>
                            <th width="15%">ผู้ขอเบิก</th>
                            <th width="10%">หน่วยงาน</th>
                            <th width="20%">รายการของ</th>
                            <th width="10%">วันที่ทำรายการ</th>
                            <th width="10%">สถานะ</th>
                            <th width="10%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                            <tr>
                                <td class="text-primary small">
                                    <?php echo ($req['email']) ? $req['email'] : '-'; ?>
                                </td>
                                
                                <?php 
                                    $isUrgent = (strtotime($req['date_required']) <= time() + (3*24*60*60) && $req['status']=='Pending');
                                    $dateStyle = $isUrgent ? 'color: red; font-weight: bold;' : 'color: #0d6efd; font-weight: 500;';
                                ?>
                                <td style="<?php echo $dateStyle; ?>">
                                    <?php echo DateThai($req['date_required']); ?>
                                </td>

                                <td>
                                    <?php echo $req['full_name']; ?><br>
                                    <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo $req['phone']; ?></small>
                                </td>
                                <td><?php echo $req['department']; ?></td>
                                <td>
                                    <small><?php echo $req['item_list']; ?></small>
                                    <div class="text-muted" style="font-size: 0.8em; margin-top:2px;">
                                        เหตุผล: <?php echo $req['purpose']; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($req['request_date'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($req['request_date'])); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'bg-secondary';
                                        $statusText = $req['status'];
                                        if($req['status'] == 'Pending') { $statusClass = 'bg-pending'; $statusText = 'รออนุมัติ'; }
                                        elseif($req['status'] == 'Approved') { $statusClass = 'bg-approved'; $statusText = 'อนุมัติแล้ว'; }
                                        elseif($req['status'] == 'Rejected') { $statusClass = 'bg-rejected'; $statusText = 'ไม่อนุมัติ'; }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../print_request.php?id=<?php echo $req['request_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="พิมพ์">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if($req['status'] == 'Pending'): ?>
                                            <button onclick="updateStatus(<?php echo $req['request_id']; ?>, 'Approved')" class="btn btn-sm btn-success" title="อนุมัติ"><i class="bi bi-check-lg"></i></button>
                                            <button onclick="updateStatus(<?php echo $req['request_id']; ?>, 'Rejected')" class="btn btn-sm btn-danger" title="ไม่อนุมัติ"><i class="bi bi-x-lg"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    let textConfirm = status === 'Approved' ? "ยืนยันการอนุมัติ?" : "ยืนยันการไม่อนุมัติ?";
    let colorConfirm = status === 'Approved' ? "#198754" : "#dc3545";
    let subText = status === 'Approved' ? "ระบบจะทำการตัดสต็อกสินค้าทันที" : "";

    Swal.fire({
        title: textConfirm,
        text: subText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: colorConfirm,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `update_status.php?id=${id}&status=${status}`;
        }
    })
}
</script>
</body>
</html>