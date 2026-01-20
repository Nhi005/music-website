<?php
// File: api/favorites.php
session_start();
require_once '../includes/config.php'; // Đảm bảo đường dẫn đúng

header('Content-Type: application/json');

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thích bài hát']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true);
$song_id = isset($input['song_id']) ? intval($input['song_id']) : 0;

if ($song_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Lỗi ID bài hát']);
    exit;
}

// 2. Kiểm tra xem đã like chưa
$db = getDB(); // Hàm lấy kết nối từ config.php của bạn
try {
    $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND song_id = ?");
    $stmt->execute([$user_id, $song_id]);
    
    if ($stmt->rowCount() > 0) {
        // Đã like -> Xóa (Unlike)
        $del = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND song_id = ?");
        $del->execute([$user_id, $song_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Đã xóa khỏi yêu thích']);
    } else {
        // Chưa like -> Thêm (Like)
        $ins = $db->prepare("INSERT INTO favorites (user_id, song_id) VALUES (?, ?)");
        $ins->execute([$user_id, $song_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Đã thêm vào yêu thích']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
}
?>