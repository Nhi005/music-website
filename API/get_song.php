<?php
require_once '../includes/config.php'; // Đảm bảo đường dẫn đúng tới config
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $db->prepare("SELECT s.*, a.name as artist_name 
                          FROM songs s 
                          LEFT JOIN artists a ON s.artist_id = a.id 
                          WHERE s.id = ?");
    $stmt->execute([$id]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($song) {
        // Trả về dữ liệu dạng JSON cho JavaScript đọc
        echo json_encode([
            'success' => true,
            'title' => $song['title'],
            'artist' => $song['artist_name'],
            'image' => !empty($song['image_url']) ? SITE_URL . $song['image_url'] : SITE_URL . '/assets/images/default-cover.jpg',
            'src' => SITE_URL . $song['file_url'] // Đường dẫn file mp3
        ]);
        exit;
    }
}

echo json_encode(['success' => false]);
?>