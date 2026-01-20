<?php
// api/save_history.php
header('Content-Type: application/json');
require_once '../includes/config.php'; // Đường dẫn tới file config chứa hàm getDB

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$song_id = isset($input['song_id']) ? (int)$input['song_id'] : 0;

if ($song_id > 0) {
    $db = getDB();
    $user_id = $_SESSION['user_id'];

    try {
        // 1. Xóa lịch sử cũ của bài này (để khi insert mới nó sẽ ngoi lên đầu)
        $stmt = $db->prepare("DELETE FROM listening_history WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$user_id, $song_id]);

        // 2. Thêm vào lịch sử mới nhất
        $stmt = $db->prepare("INSERT INTO listening_history (user_id, song_id, listened_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $song_id]);

        // 3. (Tùy chọn) Giới hạn lịch sử chỉ giữ 100 bài gần nhất
        // Có thể làm sau nếu cần tối ưu
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Song ID']);
}
?>