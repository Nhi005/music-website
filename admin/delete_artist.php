<?php
// 1. Khởi tạo cấu hình và bảo mật
require_once '../includes/config.php';
header('Content-Type: application/json');

// Kiểm tra quyền Admin trước khi thực hiện
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// 2. Kiểm tra dữ liệu đầu vào từ admin.js
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $db = getDB(); //

    try {
        // 3. Lấy thông tin avatar để xóa file vật lý
        $stmt = $db->prepare("SELECT avatar FROM artists WHERE id = ?");
        $stmt->execute([$id]);
        $artist = $stmt->fetch();

        // 4. Thực hiện xóa trong Database
        // Lưu ý: Bảng songs có ràng buộc ON DELETE CASCADE với bảng artists
        // nên khi xóa nghệ sĩ, các bài hát của họ sẽ tự động bị xóa theo.
        $del = $db->prepare("DELETE FROM artists WHERE id = ?");
        
        if ($del->execute([$id])) {
            // 5. Xóa file ảnh đại diện thực tế trên server
            if ($artist && !empty($artist['avatar'])) {
                $file_path = ".." . $artist['avatar'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            // Trả về kết quả JSON cho admin.js xử lý alert và reload
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}