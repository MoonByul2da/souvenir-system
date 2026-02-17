<?php
require_once 'header.php'; 
require_once '../db_connect.php';

// --- จัดการบันทึก (Add / Edit) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // เพิ่มใหม่
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $itemName = $_POST['item_name'];
        $stock = $_POST['stock'];
        $fileName = '';

        if (isset($_FILES['item_image']) && $_FILES['item_image']['name'] != '') {
            $ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $newInfo = time() . '.' . $ext;
            move_uploaded_file($_FILES['item_image']['tmp_name'], '../img/' . $newInfo);
            $fileName = $newInfo;
        }
        
        $stmt = $conn->prepare("INSERT INTO items (item_name, item_image, stock) VALUES (:name, :img, :stock)");
        $stmt->execute(array(':name' => $itemName, ':img' => $fileName, ':stock' => $stock));
        header("Location: items.php?status=success"); exit();
    }

    // แก้ไข
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = $_POST['item_id'];
        $itemName = $_POST['item_name'];
        $stock = $_POST['stock'];
        
        if (isset($_FILES['item_image']) && $_FILES['item_image']['name'] != '') {
            $ext = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $newInfo = time() . '_edit.' . $ext;
            move_uploaded_file($_FILES['item_image']['tmp_name'], '../img/' . $newInfo);
            $sql = "UPDATE items SET item_name=:name, stock=:stock, item_image=:img WHERE item_id=:id";
            $params = array(':name'=>$itemName, ':stock'=>$stock, ':img'=>$newInfo, ':id'=>$id);
        } else {
            $sql = "UPDATE items SET item_name=:name, stock=:stock WHERE item_id=:id";
            $params = array(':name'=>$itemName, ':stock'=>$stock, ':id'=>$id);
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        header("Location: items.php?status=success"); exit();
    }
}

// --- จัดการลบ ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = :id");
    $stmt->execute(array(':id' => $id));
    header("Location: items.php?status=deleted"); exit();
}

$items = $conn->query("SELECT * FROM items ORDER BY item_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="m-0">เพิ่มรายการใหม่</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">ชื่อของที่ระลึก</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวน (Stock)</label>
                            <input type="number" name="stock" class="form-control" min="0" value="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รูปภาพ</label>
                            <input type="file" name="item_image" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">บันทึก</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="m-0">รายการของที่มีในระบบ</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">รูป</th>
                                <th>ชื่อรายการ</th>
                                <th width="15%" class="text-center">คงเหลือ</th>
                                <th width="20%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if($item['item_image']): ?>
                                            <img src="../img/<?php echo $item['item_image']; ?>" style="height: 50px;">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $item['item_name']; ?></td>
                                    <td class="text-center fw-bold text-primary">
                                        <?php echo number_format($item['stock']); ?>
                                    </td>
                                    <td class="text-center">
                                        <button onclick='openEditModal(<?php echo json_encode($item); ?>)' class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i></button>
                                        <button onclick="confirmDelete(<?php echo $item['item_id']; ?>)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขรายการ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="item_id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">ชื่อของที่ระลึก</label>
                        <input type="text" name="item_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">จำนวน (Stock)</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เปลี่ยนรูปภาพ (ถ้ามี)</label>
                        <input type="file" name="item_image" class="form-control" accept="image/*">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Check URL for success status
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('status') === 'success') {
    Swal.fire({ icon: 'success', title: 'บันทึกข้อมูลเรียบร้อย', showConfirmButton: false, timer: 1500 })
    .then(() => window.history.replaceState(null, null, window.location.pathname));
} else if (urlParams.get('status') === 'deleted') {
    Swal.fire({ icon: 'success', title: 'ลบข้อมูลเรียบร้อย', showConfirmButton: false, timer: 1500 })
    .then(() => window.history.replaceState(null, null, window.location.pathname));
}

function confirmDelete(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลจะหายไปถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `items.php?delete=${id}`;
        }
    })
}

function openEditModal(item) {
    document.getElementById('edit_id').value = item.item_id;
    document.getElementById('edit_name').value = item.item_name;
    document.getElementById('edit_stock').value = item.stock;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
</body>
</html>