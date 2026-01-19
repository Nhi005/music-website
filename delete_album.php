<?php
require_once '../includes/config.php'; //
header('Content-Type: application/json');

// Kiểm tra quyền Admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = getDB(); //

    try {
        // 1. Lấy đường dẫn ảnh cũ để xóa file vật lý
        $stmt = $db->prepare("SELECT cover_url FROM albums WHERE id = ?");
        $stmt->execute([$id]);
        $album = $stmt->fetch();

        // 2. Xóa trong database
        $del = $db->prepare("DELETE FROM albums WHERE id = ?");
        if ($del->execute([$id])) {
            // Xóa file ảnh thực tế trên server
            if ($album && $album['cover_url']) {
                $file_path = ".." . $album['cover_url'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            echo json_encode(['success' => true]); //
        } else {
            echo json_encode(['success' => false]); //
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}