<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit(); }
require_once '../db_connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// 1. ดึงข้อมูลหัวข้อ (Header)
$stmt = $conn->prepare("SELECT r.*, u.full_name, u.department, u.email FROM souvenir_requests r 
                        JOIN souvenir_users u ON r.user_id = u.user_id WHERE r.request_id = :id");
$stmt->execute([':id' => $id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) { die("ไม่พบข้อมูลรายการนี้"); }

// 2. ดึงรายการของ (Items)
$stmt_items = $conn->prepare("SELECT d.*, i.item_name FROM souvenir_request_details d 
                              JOIN souvenir_items i ON d.item_id = i.item_id WHERE d.request_id = :id");
$stmt_items->execute([':id' => $id]);
$current_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// 3. ดึงรายการพัสดุทั้งหมด (สำหรับตัวเลือก)
$items_all = $conn->query("SELECT * FROM souvenir_items")->fetchAll(PDO::FETCH_ASSOC);
$items_json = json_encode($items_all);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขและอนุมัติใบเบิก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .main-card { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-top: 20px; }
    </style>
</head>
<body>
<div class="container mb-5">
    <div class="main-card p-4">
        <h4 class="mb-4 text-primary border-bottom pb-2">แก้ไขข้อมูลและพิจารณาอนุมัติ (ID: <?php echo $id; ?>)</h4>
        
        <form action="update_and_approve.php" method="POST">
            <input type="hidden" name="request_id" value="<?php echo $id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $req['user_id']; ?>">

            <h6 class="text-muted fw-bold">ข้อมูลผู้ขอเบิก</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <label class="form-label">คำนำหน้า</label>
                    <input type="text" name="requester_prefix" class="form-control" value="<?php echo $req['requester_prefix']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo $req['full_name']; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ตำแหน่ง</label>
                    <input type="text" name="requester_position" class="form-control" value="<?php echo $req['requester_position']; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ฝ่าย/แผนก</label>
                    <input type="text" name="department" class="form-control" value="<?php echo $req['department']; ?>">
                </div>
            </div>

            <h6 class="text-muted fw-bold">รายละเอียดการเบิก</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">วันที่ต้องการใช้ของ</label>
                    <input type="date" name="date_required" class="form-control" value="<?php echo $req['date_required']; ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">วัตถุประสงค์</label>
                    <textarea name="purpose" class="form-control" rows="2"><?php echo $req['purpose']; ?></textarea>
                </div>
            </div>

            <h6 class="text-muted fw-bold">รายการพัสดุ</h6>
            <table class="table table-bordered" id="editItemTable">
                <thead class="table-light">
                    <tr>
                        <th>รายการ</th>
                        <th width="15%">จำนวน</th>
                        <th width="15%">หน่วย</th>
                        <th width="20%">หมายเหตุ</th>
                        <th width="5%">ลบ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($current_items as $index => $item): ?>
                    <tr>
                        <td>
                            <select name="items[<?php echo $index; ?>][id]" class="form-select">
                                <?php foreach($items_all as $i): ?>
                                <option value="<?php echo $i['item_id']; ?>" <?php echo ($i['item_id'] == $item['item_id']) ? 'selected' : ''; ?>>
                                    <?php echo $i['item_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="items[<?php echo $index; ?>][qty]" class="form-control" value="<?php echo $item['qty_requested']; ?>"></td>
                        <td><input type="text" name="items[<?php echo $index; ?>][unit]" class="form-control" value="<?php echo $item['unit']; ?>"></td>
                        <td><input type="text" name="items[<?php echo $index; ?>][remark]" class="form-control" value="<?php echo $item['remark']; ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addEditItem()">+ เพิ่มรายการ</button>

            <hr>
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary px-4">ยกเลิก</a>
                <div>
                    <button type="submit" name="status" value="Approved" class="btn btn-success px-5 fw-bold">อัปเดตข้อมูลและพิมพ์ใบเบิก</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let itemCount = <?php echo count($current_items); ?>;
    const itemsData = <?php echo $items_json; ?>;

    function addEditItem() {
        const table = document.getElementById("editItemTable").getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        let options = itemsData.map(i => `<option value="${i.item_id}">${i.item_name}</option>`).join('');
        
        newRow.innerHTML = `
            <td><select name="items[${itemCount}][id]" class="form-select">${options}</select></td>
            <td><input type="number" name="items[${itemCount}][qty]" class="form-control" value="1"></td>
            <td><input type="text" name="items[${itemCount}][unit]" class="form-control" value="ชิ้น"></td>
            <td><input type="text" name="items[${itemCount}][remark]" class="form-control"></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">X</button></td>
        `;
        itemCount++;
    }
</script>
</body>
</html>