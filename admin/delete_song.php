<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $db = getDB();

    // Lấy thông tin file để xóa vật lý trong thư mục (tùy chọn nhưng nên làm)
    $stmt = $db->prepare("SELECT file_url, image_url FROM songs WHERE id = ?");
    $stmt->execute([$id]);
    $song = $stmt->fetch();

    // Xóa trong Database
    $del = $db->prepare("DELETE FROM songs WHERE id = ?");
    if ($del->execute([$id])) {
        // Xóa file thực tế để tiết kiệm bộ nhớ
        if ($song['file_url']) @unlink(".." . $song['file_url']);
        if ($song['image_url']) @unlink(".." . $song['image_url']);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}