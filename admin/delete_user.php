<?php
require_once '../includes/config.php'; //
header('Content-Type: application/json');

// 1. Kiểm tra quyền Admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = getDB(); //

    // 2. Không cho phép Admin tự xóa chính mình
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Bạn không thể tự xóa tài khoản của chính mình!']);
        exit();
    }

    try {
        // 3. Xóa người dùng trong Database
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa người dùng này.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}