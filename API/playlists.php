<?php
// api/playlists.php
require_once '../includes/config.php'; 

// Đặt header JSON (trừ trường hợp update form)
if (!isset($_GET['action']) || $_GET['action'] !== 'update') {
    header('Content-Type: application/json');
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // === CASE 1: TẠO PLAYLIST MỚI ===
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = trim($_POST['name'] ?? 'Playlist mới');
                $stmt = $db->prepare("INSERT INTO playlists (user_id, name, created_at) VALUES (?, ?, NOW())");
                if ($stmt->execute([$user_id, $name])) {
                    echo json_encode(['success' => true, 'message' => 'Tạo thành công']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi DB']);
                }
            }
            break;

        // === CASE 2: THÊM BÀI HÁT VÀO PLAYLIST ===
        case 'add_song':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $playlist_id = $_POST['playlist_id'] ?? 0;
                $song_id = $_POST['song_id'] ?? 0;

                // Kiểm tra playlist có phải của user này không
                $check = $db->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
                $check->execute([$playlist_id, $user_id]);

                if ($check->rowCount() > 0) {
                    // Kiểm tra bài hát đã có chưa để tránh trùng
                    $exists = $db->prepare("SELECT id FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
                    $exists->execute([$playlist_id, $song_id]);

                    if ($exists->rowCount() == 0) {
                        $stmt = $db->prepare("INSERT INTO playlist_songs (playlist_id, song_id, added_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$playlist_id, $song_id]);
                        echo json_encode(['success' => true, 'message' => 'Đã thêm bài hát']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Bài hát này đã có trong playlist']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy playlist hoặc không có quyền']);
                }
            }
            break;

        // === CASE 3: XÓA BÀI HÁT KHỎI PLAYLIST (Cái bạn đang thiếu) ===
        case 'remove_song':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $playlist_id = $_POST['playlist_id'] ?? 0;
                $song_id = $_POST['song_id'] ?? 0;

                // Kiểm tra quyền sở hữu playlist trước khi xóa
                $check = $db->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
                $check->execute([$playlist_id, $user_id]);

                if ($check->rowCount() > 0) {
                    $stmt = $db->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
                    $stmt->execute([$playlist_id, $song_id]);
                    echo json_encode(['success' => true, 'message' => 'Đã xóa bài hát']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi xác thực quyền sở hữu']);
                }
            }
            break;

        // === CASE 4: XÓA PLAYLIST (Cái bạn đang thiếu) ===
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $playlist_id = $_POST['id'] ?? 0;

                // Kiểm tra quyền
                $check = $db->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
                $check->execute([$playlist_id, $user_id]);

                if ($check->rowCount() > 0) {
                    // Xóa bài hát trong playlist trước (nếu DB không để Cascade)
                    $db->prepare("DELETE FROM playlist_songs WHERE playlist_id = ?")->execute([$playlist_id]);
                    
                    // Xóa playlist
                    $stmt = $db->prepare("DELETE FROM playlists WHERE id = ?");
                    $stmt->execute([$playlist_id]);
                    
                    echo json_encode(['success' => true, 'message' => 'Đã xóa playlist']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể xóa playlist này']);
                }
            }
            break;

        // === CASE 5: CẬP NHẬT THÔNG TIN (Sửa tên, mô tả) ===
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? 0;
                $name = $_POST['name'] ?? '';
                $desc = $_POST['description'] ?? '';
                $is_public = isset($_POST['is_public']) ? 1 : 0;

                $stmt = $db->prepare("UPDATE playlists SET name = ?, description = ?, is_public = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$name, $desc, $is_public, $id, $user_id]);

                // Vì form ở modal submit bình thường (không phải ajax) nên cần redirect về trang cũ
                header("Location: ../playlist.php?id=" . $id);
                exit;
            }
            break;

        // === CASE MẶC ĐỊNH: LẤY DANH SÁCH PLAYLIST ===
        default:
            $stmt = $db->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'playlists' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}
?>