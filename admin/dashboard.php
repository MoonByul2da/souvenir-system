<?php
require_once 'header.php'; 
require_once '../db_connect.php'; 

$items_all = $conn->query("SELECT * FROM souvenir_items")->fetchAll(PDO::FETCH_ASSOC);
$items_json = json_encode($items_all);

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
                            <th width="10%">สถานะ</th>
                            <th width="10%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                            <tr>
                                <td class="text-primary small"><?php echo $req['email']; ?></td>
                                <td class="fw-bold"><?php echo DateThai($req['date_required']); ?></td>
                                <td><?php echo $req['full_name']; ?></td>
                                <td><?php echo $req['department']; ?></td>
                                <td><small><?php echo $req['item_list']; ?></small></td>
                                <td>
                                    <?php 
                                        $statusClass = ($req['status']=='Pending'?'bg-warning text-dark':($req['status']=='Approved'?'bg-success':'bg-danger'));
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $req['status']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?php echo $req['request_id']; ?>)">
                                            <i class="bi bi-pencil-square"></i> ตรวจสอบ
                                        </button>
                                        <a href="../print_request.php?id=<?php echo $req['request_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>
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

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="update_and_approve.php" method="POST" id="requestForm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">ตรวจสอบและพิจารณาใบเบิก</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="text-center p-5"><div class="spinner-border text-primary"></div></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    
                    <button type="button" class="btn btn-danger px-4" onclick="confirmReject()">
                        <i class="bi bi-x-circle me-1"></i> ไม่อนุมัติ
                    </button>
                    
                    <button type="button" class="btn btn-success px-4 shadow-sm" onclick="confirmApprove()">
                        <i class="bi bi-check-circle me-1"></i> บันทึกและอนุมัติ
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const itemsData = <?php echo $items_json; ?>;
let itemIdx = 0;

function openEditModal(reqId) {
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const content = document.getElementById('modalBody');
    content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
    editModal.show();

    fetch('../ajax/get_request.php?id=' + reqId)
        .then(response => response.json())
        .then(data => {
            itemIdx = 0;
            let rowsHtml = '';
            data.details.forEach(item => { rowsHtml += generateRow(itemIdx, item); itemIdx++; });

            content.innerHTML = `
                <input type="hidden" name="request_id" value="${data.header.request_id}">
                <input type="hidden" name="user_id" value="${data.header.user_id}">
                <div class="row g-3 mb-3">
                    <div class="col-md-2"><label class="small">คำนำหน้า</label><input type="text" name="requester_prefix" class="form-control" value="${data.header.requester_prefix || ''}"></div>
                    <div class="col-md-4"><label class="small">ชื่อ-นามสกุล</label><input type="text" name="full_name" class="form-control" value="${data.header.full_name}" required></div>
                    <div class="col-md-3"><label class="small">ตำแหน่ง</label><input type="text" name="requester_position" class="form-control" value="${data.header.requester_position || ''}"></div>
                    <div class="col-md-3"><label class="small">แผนก</label><input type="text" name="department" class="form-control" value="${data.header.department}"></div>
                </div>
                <div class="mb-3"><label class="small">วัตถุประสงค์</label><textarea name="purpose" class="form-control" rows="1">${data.header.purpose}</textarea></div>
                <div class="mb-4 col-md-4"><label class="small">วันที่ต้องการใช้</label><input type="date" name="date_required" class="form-control" value="${data.header.date_required}"></div>
                <table class="table table-bordered table-sm" id="modalItemTable">
                    <thead class="table-light"><tr><th>รายการ</th><th width="15%">จำนวน</th><th width="15%">หน่วย</th><th>หมายเหตุ</th><th width="5%">ลบ</th></tr></thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRowInModal()">+ เพิ่มรายการพัสดุ</button>
            `;
        })
        .catch(err => {
            content.innerHTML = `<div class="alert alert-danger">ไม่พบไฟล์ดึงข้อมูล: ตรวจสอบเส้นทาง ajax/get_request.php</div>`;
        });
}

function generateRow(idx, item = null) {
    let opts = itemsData.map(i => `<option value="${i.item_id}" ${item && i.item_id == item.item_id ? 'selected' : ''}>${i.item_name}</option>`).join('');
    return `<tr>
        <td><select name="items[${idx}][id]" class="form-select form-select-sm">${opts}</select></td>
        <td><input type="number" name="items[${idx}][qty]" class="form-control form-control-sm text-center" value="${item?item.qty_requested:1}"></td>
        <td><input type="text" name="items[${idx}][unit]" class="form-control form-control-sm text-center" value="${item?item.unit:'ชิ้น'}"></td>
        <td><input type="text" name="items[${idx}][remark]" class="form-control form-control-sm" value="${item?item.remark:''}"></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
    </tr>`;
}

function addRowInModal() {
    document.querySelector('#modalItemTable tbody').insertAdjacentHTML('beforeend', generateRow(itemIdx));
    itemIdx++;
}
function confirmApprove() {
    Swal.fire({
        title: 'ยืนยันการอนุมัติ?',
        text: "ระบบจะบันทึกข้อมูลและส่งอีเมลแจ้งผลการอนุมัติไปยังผู้ขอเบิกทันที",
        icon: 'success', 
        showCancelButton: true,
        confirmButtonColor: '#198754', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันการอนุมัติ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('requestForm');
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'Approved';
            
            form.appendChild(statusInput);
            form.submit(); 
        }
    })
}

function confirmReject() {
    Swal.fire({
        title: 'ยืนยันการไม่อนุมัติ?',
        text: "คุณต้องการปฏิเสธคำขอเบิกรายการนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ไม่อนุมัติ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('requestForm');
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'Rejected';
            form.appendChild(statusInput);
            form.submit();
        }
    })
}
</script>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'อัปเดตข้อมูลสำเร็จ!',
            text: 'ระบบได้บันทึกข้อมูลและส่งอีเมลแจ้งผู้เบิกเรียบร้อยแล้ว',
            showConfirmButton: false,
            timer: 2500 // แสดงข้อความ 2.5 วินาทีแล้วปิดเอง
        });
        
        // ลบ ?msg=success ออกจาก URL เพื่อไม่ให้แจ้งเตือนซ้ำเวลากดรีเฟรชหน้าเว็บอีกรอบ
        window.history.replaceState(null, null, window.location.pathname);
    });
</script>
<?php endif; ?>