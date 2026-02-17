<?php
require_once 'ajax/get_all_ajax.php'; 

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบเบิกของที่ระลึก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" href="img/Husoc_MSU_Logo.png" type="image/png">

    <style>
        :root { --msu-yellow: #F2CD00; --msu-gray: #4D4D4F; --husoc-bg: #f4f6f9; }
        body { background-color: var(--husoc-bg); font-family: 'Sarabun', sans-serif; }
        .top-bar { background-color: #fff; border-bottom: 4px solid var(--msu-yellow); padding: 15px 0; }
        .logo-img { height: 70px; margin-right: 20px; }
        .main-card { background: white; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.05); margin-top: 30px; margin-bottom: 50px; }
        .card-header-custom { background-color: var(--msu-gray); color: white; padding: 15px; border-radius: 8px 8px 0 0; }
        .btn-msu { background-color: var(--msu-yellow); color: #333; font-weight: bold; border: none; }
        .btn-msu:hover { background-color: #dcb300; }
        .item-preview { transition: transform 0.2s; object-fit: cover; }
        .item-preview:hover { transform: scale(1.1); opacity: 0.9; }
    </style>
</head>
<body>

    <header class="top-bar">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <img src="img/Husoc_MSU_Logo.png" class="logo-img" alt="Logo" onerror="this.style.display='none'">
                <div>
                    <h1 style="font-size:1.4rem; font-weight:700; color:var(--msu-gray); margin:0;">ระบบเบิกของที่ระลึกงานประชาสัมพันธ์</h1>
                    <h2 style="font-size:1rem; color:#777; margin:0;">คณะมนุษยศาสตร์และสังคมศาสตร์ มหาวิทยาลัยมหาสารคาม</h2>
                </div>
            </div>
            <div>
                <a href="admin/login.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-lock"></i> สำหรับเจ้าหน้าที่</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-card">
            <div class="card-header-custom"><i class="bi bi-file-earmark-text-fill me-2"></i> แบบฟอร์มขออนุมัติเบิก</div>
            <div class="card-body p-4">

                <form action="save_request.php" method="POST">
                    
                    <h6 class="text-muted border-bottom pb-2 mb-3">ข้อมูลผู้ขอเบิก</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label">คำนำหน้า</label>
                            <select name="requester_prefix" id="prefix" class="form-select" required>
                                <option value="">- เลือก -</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" list="user_list" placeholder="ระบุชื่อ..." required oninput="checkUser()">
                            <datalist id="user_list">
                                <?php foreach($users as $u): ?>
                                    <option value="<?php echo $u['full_name']; ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" name="requester_position" id="position" class="form-control" placeholder="ระบุตำแหน่ง">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ฝ่าย/แผนก <span class="text-danger">*</span></label>
                            <input type="text" name="department" id="department" class="form-control" placeholder="ระบุสังกัด..." required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" name="requester_email" id="email" class="form-control" placeholder="example@msu.ac.th" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" name="requester_phone" id="phone" class="form-control" placeholder="ระบุเบอร์โทร">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">วันที่ทำรายการ <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">วันที่ต้องการใช้ของ <span class="text-danger">*</span></label>
                            <input type="date" name="date_required" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">วัตถุประสงค์การเบิก</label>
                            <textarea name="purpose" class="form-control" rows="2" placeholder="ระบุรายละเอียดโครงการ หรือเหตุผลการเบิก..." required></textarea>
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3">รายการพัสดุ/ของที่ระลึก</h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle" id="itemTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="10%">รูปตัวอย่าง</th>
                                    <th width="40%">รายการ</th>
                                    <th width="15%">จำนวน</th>
                                    <th width="15%">หน่วยนับ</th>
                                    <th width="15%">หมายเหตุ</th>
                                    <th width="5%">ลบ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addItem()">+ เพิ่มรายการ</button>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-msu btn-lg px-5 shadow-sm">บันทึกและพิมพ์ใบเบิก</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รูปภาพตัวอย่าง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="modalImageShow" src="" style="max-width: 100%; max-height: 80vh; border-radius: 5px;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const usersData = <?php echo $users_json; ?>;
        const itemsData = <?php echo $items_json; ?>;
        let itemCount = 0;

        window.onload = function() {
            addItem();
        };

        function checkUser() {
            const inputName = document.getElementById('full_name').value;
            const foundUser = usersData.find(u => u.full_name === inputName);
            if (foundUser) {
                document.getElementById('prefix').value = foundUser.prefix || '';
                document.getElementById('position').value = foundUser.position || '';
                document.getElementById('phone').value = foundUser.phone || '';
                document.getElementById('department').value = foundUser.department || '';
                document.getElementById('email').value = foundUser.email || '';
            }
        }

        function onSelectItem(selectObj) {
            let row = selectObj.closest('tr');
            let imgTag = row.querySelector('.item-preview');
            let qtyInput = row.querySelector('.qty-input');
            let selectedItemId = selectObj.value;
            let item = itemsData.find(i => i.item_id == selectedItemId);

            if (item) {
                if (item.item_image) {
                    imgTag.src = 'img/' + item.item_image;
                    imgTag.style.display = 'inline-block';
                } else {
                    imgTag.style.display = 'none';
                    imgTag.src = '';
                }
                
                if(item.stock > 0) {
                    qtyInput.max = item.stock;
                    qtyInput.placeholder = "ไม่เกิน " + item.stock;
                } else {
                    qtyInput.max = 0;
                    qtyInput.value = 0;
                    qtyInput.placeholder = "หมด";
                }
            } else {
                imgTag.style.display = 'none';
                qtyInput.removeAttribute('max');
                qtyInput.placeholder = "";
            }
        }

        function viewImage(imgSrc) {
            if (imgSrc && imgSrc !== window.location.href) {
                document.getElementById('modalImageShow').src = imgSrc;
                new bootstrap.Modal(document.getElementById('imageModal')).show();
            }
        }

        function addItem() {
            const table = document.getElementById("itemTable").getElementsByTagName('tbody')[0];
            const newRow = table.insertRow(table.rows.length);
            
            let optionsHtml = '<option value="">-- เลือกรายการ --</option>';
            itemsData.forEach(item => {
                let stockText = ` (คงเหลือ: ${item.stock})`;
                let disabled = '';
                let style = '';
                
                if (item.stock <= 0) {
                    stockText = ` (สินค้าหมด)`;
                    disabled = 'disabled';
                    style = 'color: #aaa;';
                }

                optionsHtml += `<option value="${item.item_id}" ${disabled} style="${style}">${item.item_name}${stockText}</option>`;
            });

            newRow.innerHTML = `
                <td class="text-center bg-white" style="vertical-align: middle;">
                    <img src="" class="item-preview" 
                         onclick="viewImage(this.src)"
                         style="height: 60px; display: none; border-radius: 5px; border: 1px solid #ddd; cursor: pointer;"
                         title="คลิกเพื่อขยายดูรูปใหญ่">
                </td>
                <td>
                    <select name="items[${itemCount}][id]" class="form-select" required onchange="onSelectItem(this)">
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${itemCount}][qty]" class="form-control text-center qty-input" min="1" value="1" required>
                </td>
                <td>
                    <select name="items[${itemCount}][unit]" class="form-select text-center">
                        <option value="ชิ้น">ชิ้น</option>
                        <option value="ชุด">ชุด</option>
                    </select>
                </td>
                <td><input type="text" name="items[${itemCount}][remark]" class="form-control"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()">X</button>
                </td>
            `;
            itemCount++;
        }
    </script>
</body>
</html>