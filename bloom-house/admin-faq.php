<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

// Token prevents the same form/reply from being submitted twice.
if (empty($_SESSION['faq_form_token'])) {
    $_SESSION['faq_form_token'] = bin2hex(random_bytes(16));
}

// اسم الأدمن
$adminLetter = 'A';
$r = mysqli_query($conn, "SELECT full_name FROM admin WHERE admin_id=" . (int) $_SESSION['admin_id']);
if ($r && $a = mysqli_fetch_assoc($r)) {
    $adminLetter = strtoupper(substr(trim($a['full_name']), 0, 1));
}

// حذف
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM faqs WHERE faq_id = $id");
    header('Location: admin-faq.php?msg=' . urlencode('FAQ deleted successfully.'));
    exit();
}

// إضافة أو تعديل
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['form_token'] ?? '';

    // Stop duplicate replies/submissions caused by double-clicking or browser resend.
    if (empty($postedToken) || empty($_SESSION['faq_form_token']) || !hash_equals($_SESSION['faq_form_token'], $postedToken)) {
        header('Location: admin-faq.php?msg=' . urlencode('This reply was already submitted.'));
        exit();
    }

    // Use the token only once, before saving, so the same request cannot be repeated.
    unset($_SESSION['faq_form_token']);

    $faq_id = (int) ($_POST['faq_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');

    if (empty($question) || empty($answer)) {
        $_SESSION['faq_form_token'] = bin2hex(random_bytes(16));
        $formError = 'Please fill in both question and answer.';
    } else {
        if ($faq_id > 0) {
            $stmt = $conn->prepare("UPDATE faqs SET question=?, answer=? WHERE faq_id=?");
            $stmt->bind_param('ssi', $question, $answer, $faq_id);
            $msg = 'FAQ updated successfully.';
        } else {
            // Extra protection: do not insert the exact same question/answer twice.
            $check = $conn->prepare("SELECT faq_id FROM faqs WHERE question=? AND answer=? LIMIT 1");
            $check->bind_param('ss', $question, $answer);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                header('Location: admin-faq.php?msg=' . urlencode('This reply already exists.'));
                exit();
            }

            $created_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO faqs (question, answer, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $question, $answer, $created_at);
            $msg = 'FAQ added successfully.';
        }
        if ($stmt->execute()) {
            $_SESSION['faq_form_token'] = bin2hex(random_bytes(16));
            header('Location: admin-faq.php?msg=' . urlencode($msg));
            exit();
        } else {
            $_SESSION['faq_form_token'] = bin2hex(random_bytes(16));
            $formError = 'Something went wrong. Please try again.';
        }
    }
}

$pageMsg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';

// جلب للتعديل
$editFaq = null;
if (isset($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM faqs WHERE faq_id = $eid");
    $editFaq = mysqli_fetch_assoc($r);
}

// جلب الكل
$faqs = [];
$res = mysqli_query($conn, "SELECT * FROM faqs ORDER BY faq_id ASC");
while ($row = mysqli_fetch_assoc($res))
    $faqs[] = $row;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQs – Bloom House Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
    <style>
        .alert-ok {
            background: #e8f3ed;
            border: 1px solid #b2d8bf;
            color: #1f4032;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .84rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-err {
            background: #fef0f0;
            border: 1px solid #f5c2c2;
            color: #c0392b;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: .84rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .faq-list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .faq-list-head h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1c2b22;
        }

        .count-badge {
            background: #e8f3ed;
            color: #2f5e4a;
            font-size: .75rem;
            font-weight: 600;
            padding: 3px 12px;
            border-radius: 20px;
        }
    </style>
</head>

<body>

    <?php
    $adminPageTitle = 'Manage FAQs';
    $activePage = 'faq';
    include 'admin.php';
    ?>

    <!-- FORM -->
    <div class="faq-form">
        <h3>
            <i class="fas <?= $editFaq ? 'fa-edit' : 'fa-plus-circle' ?>" style="color:#2f5e4a;margin-right:6px;"></i>
            <?= $editFaq ? 'Edit FAQ' : 'Add New FAQ' ?>
        </h3>

        <?php if ($formError): ?>
            <div class="alert-err"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($formError) ?></div>
        <?php endif; ?>

        <form method="POST" action="admin-faq.php">
            <input type="hidden" name="form_token" value="<?= htmlspecialchars($_SESSION['faq_form_token']) ?>">
            <input type="hidden" name="faq_id" value="<?= $editFaq['faq_id'] ?? 0 ?>">
            <input type="text" name="question" placeholder="Question..."
                value="<?= htmlspecialchars($editFaq['question'] ?? '') ?>" required maxlength="255">
            <textarea name="answer" placeholder="Answer..."
                required><?= htmlspecialchars($editFaq['answer'] ?? '') ?></textarea>
            <div style="display:flex;align-items:center;gap:10px;">
                <button type="submit" class="btn-add-faq">
                    <i class="fas <?= $editFaq ? 'fa-save' : 'fa-plus' ?>"></i>
                    <?= $editFaq ? 'Save Changes' : 'Add FAQ' ?>
                </button>
                <?php if ($editFaq): ?>
                    <a href="admin-faq.php" style="color:#9ab5a6;font-size:.85rem;text-decoration:none;">✕ Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- FAQ LIST -->
    <?php if ($pageMsg): ?>
        <div class="alert-ok"><i class="bi bi-check-circle"></i> <?= $pageMsg ?></div>
    <?php endif; ?>

    <div class="faq-list-head">
        <h3><i class="bi bi-question-circle" style="color:#2f5e4a;margin-right:6px;"></i>All FAQs</h3>
        <span class="count-badge"><?= count($faqs) ?> questions</span>
    </div>

    <div id="faq-list">
        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $i => $faq): ?>
                <div class="faq-row" id="faq-row-<?= $faq['faq_id'] ?>">
                    <div class="faq-content">
                        <div class="faq-q-text">Q<?= $i + 1 ?>. <?= htmlspecialchars($faq['question']) ?></div>
                        <div class="faq-a-text"><?= nl2br(htmlspecialchars($faq['answer'])) ?></div>
                    </div>
                    <div class="faq-row-actions">
                        <a href="admin-faq.php?edit=<?= $faq['faq_id'] ?>" class="btn-edit-faq">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <button class="btn-del-faq" onclick="confirmDeleteFaq(<?= $faq['faq_id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div
                style="text-align:center;padding:40px;background:#fff;border:1px solid #e4ede8;border-radius:14px;color:#9ab5a6;">
                🌱 No FAQs yet. Add your first one!
            </div>
        <?php endif; ?>
    </div>

    </div>
    </div>

    <!-- DELETE MODAL -->
    <div class="modal-overlay" id="deleteFaqModal">
        <div class="confirm-modal">
            <div class="confirm-icon"><i class="bi bi-trash"></i></div>
            <h3>Delete FAQ?</h3>
            <p>This action cannot be undone.</p>
            <div class="confirm-btns">
                <button type="button" class="btn-cancel">Cancel</button>
                <a id="deleteFaqBtn" href="#" class="btn-del">Delete</a>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"><i class="bi bi-check2"></i> <span id="toastMsg"></span></div>

    <script>
        // Stops any submit/reply from being sent twice.
        (function () {
            var submitted = false;
            document.addEventListener('submit', function (event) {
                var form = event.target;
                if (!form) return;

                if (submitted) {
                    event.preventDefault();
                    return false;
                }

                submitted = true;
                var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.style.opacity = '0.7';
                    submitBtn.dataset.originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                }
            }, true);
        })();

        // Open the delete confirmation modal and set the correct delete link.
        function confirmDeleteFaq(id) {
            document.getElementById('deleteFaqBtn').href = 'admin-faq.php?delete=' + id;
            document.getElementById('deleteFaqModal').classList.add('open');
        }

        // Close the delete modal.
        function closeDeleteFaqModal() {
            document.getElementById('deleteFaqModal').classList.remove('open');
        }

        // Show success toast message.
        function showToast(msg) {
            var toast = document.getElementById('toast');
            var toastMsg = document.getElementById('toastMsg');

            if (!toast || !toastMsg || !msg) return;

            toastMsg.textContent = msg;
            toast.classList.add('show');

            setTimeout(function () {
                toast.classList.remove('show');
            }, 2800);
        }

        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('deleteFaqModal');
            var cancelBtn = document.querySelector('#deleteFaqModal .btn-cancel');
            var deleteBtn = document.getElementById('deleteFaqBtn');
            var params = new URLSearchParams(window.location.search);
            var msg = params.get('msg');
            var deleteLocked = false;

            // Close modal when clicking the dark background.
            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeDeleteFaqModal();
                    }
                });
            }

            // Close modal when clicking Cancel.
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeDeleteFaqModal);
            }

            // Prevent duplicate delete clicks.
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function (event) {
                    if (deleteLocked) {
                        event.preventDefault();
                        return false;
                    }
                    deleteLocked = true;
                    deleteBtn.style.pointerEvents = 'none';
                    deleteBtn.style.opacity = '0.7';
                });
            }

            // Auto-show toast when there is a success message in the URL.
            if (msg) {
                showToast(msg);
            }
        });
    </script>
</body>

</html>