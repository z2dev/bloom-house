<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$adminLetter = 'A';
$adminFirstName = 'Admin';
$stmtAdmin = $conn->prepare("SELECT full_name FROM admin WHERE admin_id = ?");
if ($stmtAdmin) {
    $adminId = (int)$_SESSION['admin_id'];
    $stmtAdmin->bind_param('i', $adminId);
    $stmtAdmin->execute();
    $adminResult = $stmtAdmin->get_result();
    if ($admin = $adminResult->fetch_assoc()) {
        $parts = explode(' ', trim($admin['full_name']));
        $adminFirstName = $parts[0];
        $adminLetter = strtoupper(substr($adminFirstName, 0, 1));
    }
    $stmtAdmin->close();
}

$pageMsg = '';
$pageError = '';

// حفظ رد الأدمن
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'])) {
    $message_id = (int)($_POST['reply_message_id'] ?? 0);
    $admin_reply = trim($_POST['admin_reply'] ?? '');

    if ($message_id <= 0 || $admin_reply === '') {
        $pageError = 'Please write a reply before saving.';
    } else {
        $status = 'Replied';
        $replied_at = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("UPDATE contact_messages SET admin_reply = ?, replied_at = ?, status = ? WHERE message_id = ?");
        if ($stmt) {
            $stmt->bind_param('sssi', $admin_reply, $replied_at, $status, $message_id);
            if ($stmt->execute()) {
                header('Location: admin-contact.php?msg=' . urlencode('Reply sent successfully.'));
                exit();
            } else {
                $pageError = 'Could not save reply.';
            }
            $stmt->close();
        } else {
            $pageError = 'Database error. Please make sure the contact_messages table was updated.';
        }
    }
}

// حذف رسالة
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE message_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delete_id);
        if ($stmt->execute()) {
            header('Location: admin-contact.php?msg=' . urlencode('Message deleted successfully.'));
            exit();
        }
        $stmt->close();
    }
    $pageError = 'Could not delete message.';
}

if (isset($_GET['mark']) && isset($_GET['id'])) {
    $mid = (int)$_GET['id'];
    $status = $_GET['mark'] === 'read' ? 'Read' : 'Unread';
    $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE message_id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $status, $mid);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: admin-contact.php');
    exit();
}

if (isset($_GET['msg'])) {
    $pageMsg = htmlspecialchars($_GET['msg']);
}

$messages = [];
$res = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
}

$totalMessages = count($messages);
$unreadCount = 0;
$pendingReply = 0;
foreach ($messages as $m) {
    if (($m['status'] ?? '') === 'Unread') {
        $unreadCount++;
    }
    if (empty($m['admin_reply'])) {
        $pendingReply++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages – Bloom House Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/manage-products.css">
    <link rel="stylesheet" href="css/admin-pages.css">
    <style>
        .msg-card{background:#fff;border:1px solid #e4ede8;border-radius:14px;padding:20px 22px;margin-bottom:14px;transition:box-shadow .2s;}
        .msg-card:hover{box-shadow:0 6px 20px rgba(30,60,40,0.08);}
        .msg-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;gap:12px;flex-wrap:wrap;}
        .msg-name{font-weight:700;font-size:.95rem;color:#1c2b22;}
        .msg-email{font-size:.78rem;color:#6b8c7a;margin-left:8px;}
        .msg-date{font-size:.75rem;color:#9ab5a6;white-space:nowrap;}
        .msg-topic{display:inline-flex;padding:3px 10px;border-radius:999px;background:#e8f3ed;color:#2f5e4a;font-size:.72rem;font-weight:600;margin-bottom:8px;}
        .msg-body{font-size:.875rem;color:#3a5244;line-height:1.6;margin:8px 0 0;}
        .msg-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
        .btn-reply{padding:8px 16px;border-radius:8px;border:none;background:#2f5e4a;color:#fff;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;}
        .btn-reply:hover{background:#1f4032;}
        .btn-del-msg{padding:8px 14px;border-radius:8px;border:1.5px solid #e4ede8;background:#fff;color:#c0392b;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
        .btn-mark{padding:8px 14px;border-radius:8px;border:1.5px solid #e4ede8;background:#fff;color:#2f5e4a;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
        .unread-dot{width:9px;height:9px;border-radius:50%;background:#2f5e4a;display:inline-block;margin-right:6px;}
        .msg-card.read{opacity:.86;}
        .reply-form{margin-top:14px;padding:14px;border:1px solid #e4ede8;border-radius:12px;background:#fafcfb;}
        .reply-form label{display:block;font-size:.78rem;font-weight:700;color:#2f5e4a;margin-bottom:6px;}
        .reply-form textarea{width:100%;min-height:90px;resize:vertical;border:1.5px solid #d4e3da;border-radius:10px;padding:10px 12px;font-family:'Poppins',sans-serif;font-size:.84rem;outline:none;background:#fff;color:#1c2b22;}
        .reply-form textarea:focus{border-color:#2f5e4a;box-shadow:0 0 0 3px rgba(47,94,74,.08);}
        .saved-reply{margin-top:12px;background:#eaf7ef;border:1px solid #a8d9b8;border-radius:12px;padding:12px;}
        .saved-reply strong{font-size:.8rem;color:#2f5e4a;display:block;margin-bottom:5px;}
        .saved-reply p{font-size:.85rem;color:#2d4a3e;line-height:1.6;margin:0;}
        .alert-ok,.alert-err{border-radius:10px;padding:10px 14px;font-size:.84rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
        .alert-ok{background:#e8f3ed;border:1px solid #b2d8bf;color:#1f4032;}
        .alert-err{background:#fef0f0;border:1px solid #f5c2c2;color:#c0392b;}
        .empty-msg{background:#fff;border:1px solid #e4ede8;border-radius:14px;padding:36px;text-align:center;color:#9ab5a6;}
    </style>
</head>
<body>

<?php
    $adminPageTitle = 'Contact Messages';
    $activePage = 'contact';
    include 'admin.php';
    ?>
        <div class="stats-row" style="grid-template-columns:repeat(3,1fr);margin-bottom:26px;">
            <div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-envelope"></i></div><div class="stat-info"><p>Total Messages</p><strong><?= $totalMessages ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon si-blue"><i class="bi bi-envelope-open"></i></div><div class="stat-info"><p>Unread</p><strong><?= $unreadCount ?></strong></div></div>
            <div class="stat-card"><div class="stat-icon si-orange"><i class="bi bi-clock"></i></div><div class="stat-info"><p>Pending Reply</p><strong><?= $pendingReply ?></strong></div></div>
        </div>

        <?php if ($pageMsg): ?>
            <div class="alert-ok"><i class="bi bi-check-circle"></i> <?= $pageMsg ?></div>
        <?php endif; ?>
        <?php if ($pageError): ?>
            <div class="alert-err"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($pageError) ?></div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="msg-card <?= ($msg['status'] ?? '') === 'Read' || ($msg['status'] ?? '') === 'Replied' ? 'read' : '' ?>">
                    <div class="msg-header">
                        <div>
                            <?php if (($msg['status'] ?? '') === 'Unread'): ?><span class="unread-dot"></span><?php endif; ?>
                            <span class="msg-name"><?= htmlspecialchars($msg['full_name']) ?></span>
                            <span class="msg-email"><?= htmlspecialchars($msg['email'] ?? '') ?></span>
                        </div>
                        <span class="msg-date"><?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?></span>
                    </div>

                    <span class="msg-topic"><?= htmlspecialchars($msg['subject']) ?></span>
                    <p class="msg-body"><?= nl2br(htmlspecialchars($msg['message_body'])) ?></p>

                    <?php if (!empty($msg['admin_reply'])): ?>
                        <div class="saved-reply">
                            <strong>Admin Reply</strong>
                            <p><?= nl2br(htmlspecialchars($msg['admin_reply'])) ?></p>
                            <?php if (!empty($msg['replied_at'])): ?>
                                <span class="msg-date">Replied at: <?= date('d M Y, h:i A', strtotime($msg['replied_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form class="reply-form" method="POST" action="admin-contact.php">
                        <input type="hidden" name="reply_message_id" value="<?= (int)$msg['message_id'] ?>">
                        <label>Reply</label>
                        <textarea name="admin_reply" placeholder="Write your reply here..." required><?= htmlspecialchars($msg['admin_reply'] ?? '') ?></textarea>
                        <div class="msg-actions">
                            <button class="btn-reply" type="submit"><i class="fas fa-reply"></i> Save Reply</button>
                            <?php if (($msg['status'] ?? '') === 'Unread'): ?>
                                <a class="btn-mark" href="admin-contact.php?mark=read&id=<?= (int)$msg['message_id'] ?>"><i class="bi bi-envelope-open"></i> Mark Read</a>
                            <?php else: ?>
                                <a class="btn-mark" href="admin-contact.php?mark=unread&id=<?= (int)$msg['message_id'] ?>"><i class="bi bi-envelope"></i> Mark Unread</a>
                            <?php endif; ?>
                            <a class="btn-del-msg" href="admin-contact.php?delete=<?= (int)$msg['message_id'] ?>" onclick="return confirm('Delete this message?');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-msg">🌱 No contact messages yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
