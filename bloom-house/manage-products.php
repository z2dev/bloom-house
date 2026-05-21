<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

// اسم الأدمن
$adminLetter = 'A';
$adminFirstName = 'Admin';
$r = mysqli_query($conn, "SELECT full_name FROM admin WHERE admin_id=" . (int)$_SESSION['admin_id']);
if ($r && $a = mysqli_fetch_assoc($r)) {
    $parts = explode(' ', trim($a['full_name']));
    $adminFirstName = $parts[0];
    $adminLetter = strtoupper(substr($adminFirstName, 0, 1));
}

// حذف المنتج
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id > 0) {
        mysqli_begin_transaction($conn);

        try {
            // نجيب اسم الصورة قبل الحذف
            $oldImage = '';
            $imgStmt = $conn->prepare("SELECT image FROM plants WHERE plant_id = ?");
            if ($imgStmt) {
                $imgStmt->bind_param('i', $id);
                $imgStmt->execute();
                $imgResult = $imgStmt->get_result();
                if ($imgRow = $imgResult->fetch_assoc()) {
                    $oldImage = $imgRow['image'];
                }
                $imgStmt->close();
            }

            // حذف العلاقات عشان ما يطلع Foreign Key Error
            $tables = ['favourite', 'reviews', 'orders_items'];
            foreach ($tables as $table) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE plant_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $stmt = $conn->prepare("DELETE FROM plants WHERE plant_id = ?");
            if (!$stmt) {
                throw new Exception('Delete statement failed.');
            }

            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deletedRows = $stmt->affected_rows;
            $stmt->close();

            mysqli_commit($conn);

            // حذف ملف الصورة من الفولدر إذا كان موجود
            if ($deletedRows > 0 && !empty($oldImage)) {
                $imagePath = __DIR__ . '/images/plants/' . basename($oldImage);
                if (is_file($imagePath)) {
                    @unlink($imagePath);
                }

                header('Location: manage-products.php?msg=' . urlencode('Product deleted successfully.'));
            } else {
                header('Location: manage-products.php?error=' . urlencode('Product not found.'));
            }
            exit();

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            header('Location: manage-products.php?error=' . urlencode('Could not delete product.'));
            exit();
        }
    }
}

// إضافة أو تعديل
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plant_id             = (int)($_POST['plant_id']            ?? 0);
    $plant_name           = trim($_POST['plant_name']           ?? '');
    $description          = trim($_POST['description']          ?? '');
    $price                = trim($_POST['price']                ?? '');
    $stock_quantity       = (int)($_POST['stock_quantity']      ?? 0);
    $light_requirement    = trim($_POST['light_requirement']    ?? '');
    $watering_instruction = trim($_POST['watering_instruction'] ?? '');
    $availability_status  = trim($_POST['availability_status']  ?? 'Available');
    $category             = trim($_POST['category']             ?? '');
    $height_cm            = (int)($_POST['height_cm']           ?? 1);
    $humidity_requirement = trim($_POST['humidity_requirement'] ?? 'Medium');
    $admin_id             = (int)$_SESSION['admin_id'];

    // في حالة التعديل: لو ما رفعتي صورة جديدة، نخلي القديمة
    $existing_image = trim($_POST['existing_image'] ?? '');
    $image = $existing_image;

    if (empty($plant_name) || $price === '' || empty($category)) {
        $formError = 'Please fill in all required fields.';
    } else {
        // رفع الصورة إلى images/plants
        if (isset($_FILES['plant_image']) && $_FILES['plant_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['plant_image']['error'] !== UPLOAD_ERR_OK) {
                $formError = 'Image upload failed. Please try again.';
            } else {
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $originalName = $_FILES['plant_image']['name'];
                $tmpName = $_FILES['plant_image']['tmp_name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExt)) {
                    $formError = 'Please upload an image file: JPG, PNG, WEBP, or GIF.';
                } else {
                    $uploadDir = __DIR__ . '/images/plants/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $safePlantName = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($plant_name));
                    $safePlantName = trim($safePlantName, '-');
                    if ($safePlantName === '') {
                        $safePlantName = 'plant';
                    }

                    $newImageName = $safePlantName . '-' . time() . '.' . $ext;
                    $destination = $uploadDir . $newImageName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        // لو تعديل ورفعت صورة جديدة، نحذف القديمة
                        if ($plant_id > 0 && !empty($existing_image) && $existing_image !== $newImageName) {
                            $oldPath = $uploadDir . basename($existing_image);
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }

                        $image = $newImageName;
                    } else {
                        $formError = 'Could not save image in images/plants folder.';
                    }
                }
            }
        }

        if ($formError === '' && empty($image)) {
            $formError = 'Please upload a product image.';
        }

        if ($formError === '') {
            if ($height_cm <= 0) {
                $height_cm = 1;
            }

            if (!in_array($humidity_requirement, ['Low', 'Medium', 'High'])) {
                $humidity_requirement = 'Medium';
            }

            $price = (float)$price;

            if ($plant_id > 0) {
                $stmt = $conn->prepare("
                    UPDATE plants
                    SET admin_id=?, plant_name=?, description=?, price=?, stock_quantity=?,
                        light_requirement=?, watering_instruction=?, availability_status=?,
                        category=?, image=?, height_cm=?, humidity_requirement=?
                    WHERE plant_id=?
                ");

                if ($stmt) {
                    $stmt->bind_param(
                        'issdisssssisi',
                        $admin_id,
                        $plant_name,
                        $description,
                        $price,
                        $stock_quantity,
                        $light_requirement,
                        $watering_instruction,
                        $availability_status,
                        $category,
                        $image,
                        $height_cm,
                        $humidity_requirement,
                        $plant_id
                    );
                }

                $msg = 'Product updated successfully.';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO plants
                    (admin_id, plant_name, description, price, stock_quantity,
                     light_requirement, watering_instruction, availability_status,
                     category, image, height_cm, humidity_requirement)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt) {
                    $stmt->bind_param(
                        'issdisssssis',
                        $admin_id,
                        $plant_name,
                        $description,
                        $price,
                        $stock_quantity,
                        $light_requirement,
                        $watering_instruction,
                        $availability_status,
                        $category,
                        $image,
                        $height_cm,
                        $humidity_requirement
                    );
                }

                $msg = 'Product added successfully.';
            }

            if (!$stmt) {
                $formError = 'Database statement error: ' . htmlspecialchars($conn->error);
            } elseif ($stmt->execute()) {
                $stmt->close();
                header('Location: manage-products.php?msg=' . urlencode($msg));
                exit();
            } else {
                $formError = 'Something went wrong: ' . htmlspecialchars($stmt->error);
                $stmt->close();
            }
        }
    }
}

$pageMsg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
$pageError = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// جلب للتعديل
$editProduct = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = mysqli_query($conn, "SELECT * FROM plants WHERE plant_id = $eid");
    if ($r) {
        $editProduct = mysqli_fetch_assoc($r);
    }
}

// إحصائيات
$totalPlants = 0;
$availablePlants = 0;
$lowStock = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM plants");
if ($r && $row = mysqli_fetch_assoc($r)) { $totalPlants = $row['c']; }

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM plants WHERE availability_status='Available'");
if ($r && $row = mysqli_fetch_assoc($r)) { $availablePlants = $row['c']; }

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM plants WHERE stock_quantity <= 5");
if ($r && $row = mysqli_fetch_assoc($r)) { $lowStock = $row['c']; }

// جلب المنتجات
$plants = [];
$res = mysqli_query($conn, "SELECT * FROM plants ORDER BY plant_id ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $plants[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products – Bloom House</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
    <style>
        .mp-layout{display:grid;grid-template-columns:300px 1fr;gap:22px;align-items:start;}
        .form-card{background:#fff;border:1px solid #e4ede8;border-radius:16px;padding:22px;position:sticky;top:80px;}
        .form-card h3{font-size:.95rem;font-weight:700;color:#1c2b22;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        .form-group{margin-bottom:11px;}
        .form-group label{display:block;font-size:.78rem;font-weight:600;color:#2c3e35;margin-bottom:4px;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1.5px solid #d4e3da;border-radius:10px;font-size:.85rem;color:#1c2b22;background:#f9fbf9;font-family:'Poppins',sans-serif;outline:none;transition:border-color .2s;}
        .form-group textarea{resize:vertical;min-height:70px;}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#2f5e4a;background:#fff;}
        .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
        .btn-submit-form{width:100%;padding:10px;border:none;border-radius:10px;background:#2f5e4a;color:#fff;font-family:'Poppins',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;margin-top:6px;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:6px;}
        .btn-submit-form:hover{background:#1f4032;}
        .btn-cancel-link{display:block;text-align:center;margin-top:8px;color:#9ab5a6;font-size:.8rem;text-decoration:none;}
        .btn-cancel-link:hover{color:#2f5e4a;}
        .table-card{background:#fff;border:1px solid #e4ede8;border-radius:16px;overflow:hidden;}
        .table-card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e4ede8;}
        .table-card-head h3{font-size:.95rem;font-weight:700;color:#1c2b22;}
        .count-badge{background:#e8f3ed;color:#2f5e4a;font-size:.75rem;font-weight:600;padding:3px 12px;border-radius:20px;}
        .alert-ok{background:#e8f3ed;border:1px solid #b2d8bf;color:#1f4032;border-radius:10px;padding:10px 14px;font-size:.84rem;margin:12px 20px;display:flex;align-items:center;gap:8px;}
        .alert-err{background:#fef0f0;border:1px solid #f5c2c2;color:#c0392b;border-radius:10px;padding:10px 14px;font-size:.84rem;margin:0 0 12px;display:flex;align-items:center;gap:8px;}
        .prod-img{width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #e4ede8;}
        .badge-indoor{background:#e8f3ed;color:#2f5e4a;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:600;}
        .badge-flower{background:#fde8f3;color:#9b2c6e;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:600;}
        .badge-water{background:#e8f0fb;color:#2f5eab;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:600;}
        .badge-avail{background:#e8f3ed;color:#2f5e4a;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:600;}
        .badge-out{background:#fde8e8;color:#c0392b;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:600;}
        @media(max-width:960px){.mp-layout{grid-template-columns:1fr;}.form-card{position:static;}}
    </style>
</head>
<body>

<?php
    $adminPageTitle = 'Manage Products';
    $activePage = 'products';
    include 'admin.php';
    ?>

        <!-- STATS -->
        <div class="stats-row" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px;">
            <div class="stat-card">
                <div class="stat-icon si-green"><i class="bi bi-box-seam"></i></div>
                <div class="stat-info"><p>Total Products</p><strong><?= $totalPlants ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-blue"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info"><p>Available</p><strong><?= $availablePlants ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-orange"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="stat-info"><p>Low Stock (≤5)</p><strong><?= $lowStock ?></strong></div>
            </div>
        </div>

        <div class="mp-layout">

            <!-- FORM -->
            <div class="form-card">
                <h3>
                    <i class="bi <?= $editProduct ? 'bi-pencil-square' : 'bi-plus-circle' ?>" style="color:#2f5e4a;"></i>
                    <?= $editProduct ? 'Edit Product' : 'Add New Product' ?>
                </h3>

                <?php if ($formError): ?>
                    <div class="alert-err"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div>
                <?php endif; ?>

                <form method="POST" action="manage-products.php" enctype="multipart/form-data">
                    <input type="hidden" name="plant_id" value="<?= $editProduct['plant_id'] ?? 0 ?>">

                    <div class="form-group">
                        <label>Plant Name *</label>
                        <input type="text" name="plant_name" placeholder="e.g. Monstera Deliciosa"
                               value="<?= htmlspecialchars($editProduct['plant_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Price (SAR) *</label>
                            <input type="number" name="price" step="0.01" min="0" placeholder="0.00"
                                   value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Stock</label>
                            <input type="number" name="stock_quantity" min="0" placeholder="0"
                                   value="<?= htmlspecialchars($editProduct['stock_quantity'] ?? '0') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">-- Select --</option>
                            <?php foreach (['Indoor','Flower','Water','Outdoor','Succulent'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($editProduct['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Height (cm)</label>
                            <input type="number" name="height_cm" min="1" placeholder="e.g. 60"
                                   value="<?= htmlspecialchars($editProduct['height_cm'] ?? '1') ?>">
                        </div>
                        <div class="form-group">
                            <label>Humidity</label>
                            <select name="humidity_requirement">
                                <?php foreach (['Low','Medium','High'] as $hum): ?>
                                    <option value="<?= $hum ?>" <?= ($editProduct['humidity_requirement'] ?? 'Medium') === $hum ? 'selected' : '' ?>><?= $hum ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Light Requirement</label>
                        <input type="text" name="light_requirement" placeholder="e.g. Bright indirect light"
                               value="<?= htmlspecialchars($editProduct['light_requirement'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Watering Instructions</label>
                        <textarea name="watering_instruction" placeholder="e.g. Water once a week..."><?= htmlspecialchars($editProduct['watering_instruction'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Product Image <?= $editProduct ? '' : '*' ?></label>
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editProduct['image'] ?? '') ?>">
                        <?php if (!empty($editProduct['image'])): ?>
                            <div style="margin-bottom:8px;display:flex;align-items:center;gap:10px;">
                                <img src="images/plants/<?= htmlspecialchars($editProduct['image']) ?>" alt="Current Image"
                                     style="width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #d4e3da;">
                                <span style="font-size:.78rem;color:#6b8c7a;">Current image: <?= htmlspecialchars($editProduct['image']) ?></span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="plant_image" accept="image/*" <?= $editProduct ? '' : 'required' ?>>
                        <small style="display:block;margin-top:5px;color:#8aad98;font-size:.72rem;">
                            The image will be saved automatically inside images/plants.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Availability</label>
                        <select name="availability_status">
                            <option value="Available"    <?= ($editProduct['availability_status'] ?? '') === 'Available'    ? 'selected' : '' ?>>Available</option>
                            <option value="Out of Stock" <?= ($editProduct['availability_status'] ?? '') === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit-form">
                        <i class="bi <?= $editProduct ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i>
                        <?= $editProduct ? 'Save Changes' : 'Add Product' ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="manage-products.php" class="btn-cancel-link">✕ Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABLE -->
            <div class="table-card">
                <div class="table-card-head">
                    <h3><i class="bi bi-table" style="color:#2f5e4a;margin-right:6px;"></i>All Products</h3>
                    <span class="count-badge"><?= count($plants) ?> plants</span>
                </div>

                <?php if ($pageMsg): ?>
                    <div class="alert-ok"><i class="bi bi-check-circle"></i> <?= $pageMsg ?></div>
                <?php endif; ?>

                <?php if ($pageError): ?>
                    <div class="alert-err" style="margin:12px 20px;"><i class="bi bi-exclamation-circle"></i> <?= $pageError ?></div>
                <?php endif; ?>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($plants)): ?>
                            <?php foreach ($plants as $p): ?>
                            <tr>
                                <td><img src="images/plants/<?= htmlspecialchars($p['image']) ?>" class="prod-img"
                                         onerror="this.src='images/plants/monstera.jpeg'"
                                         alt="<?= htmlspecialchars($p['plant_name']) ?>"></td>
                                <td>
                                    <div class="prod-name"><?= htmlspecialchars($p['plant_name']) ?></div>
                                    <div class="prod-id">ID: <?= $p['plant_id'] ?></div>
                                </td>
                                <td>
                                    <?php
                                    $catClass = 'badge-indoor';
                                    if ($p['category'] === 'Flower') $catClass = 'badge-flower';
                                    if ($p['category'] === 'Water')  $catClass = 'badge-water';
                                    ?>
                                    <span class="<?= $catClass ?>"><?= htmlspecialchars($p['category']) ?></span>
                                </td>
                                <td><?= number_format($p['price'], 2) ?> SAR</td>
                                <td class="<?= $p['stock_quantity'] <= 5 ? 'stock-low' : 'stock-ok' ?>"><?= (int)$p['stock_quantity'] ?></td>
                                <td>
                                    <span class="<?= $p['availability_status'] === 'Available' ? 'badge-avail' : 'badge-out' ?>">
                                        <?= htmlspecialchars($p['availability_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="manage-products.php?edit=<?= $p['plant_id'] ?>" class="act-btn edit" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="act-btn del" title="Delete"
                                            onclick="confirmDelete(<?= $p['plant_id'] ?>, '<?= htmlspecialchars($p['plant_name'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ab5a6;">🌱 No products yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="confirm-modal">
        <div class="confirm-icon"><i class="bi bi-trash"></i></div>
        <h3>Delete Product?</h3>
        <p id="deleteModalMsg">Are you sure?</p>
        <div class="confirm-btns">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="btn-del">Delete</a>
        </div>
    </div>
</div>

<div class="toast" id="toast"><i class="bi bi-check2"></i> <span id="toastMsg"></span></div>

<script src="js/manage-products.js"></script>
</body>
</html>
