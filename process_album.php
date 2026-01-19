<?php
require_once '../includes/config.php'; //
$db = getDB(); //

// 1. Kiểm tra quyền Admin
if (!isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $album_id = $_POST['album_id'] ?? null;
    $title = trim($_POST['title']);
    $artist_id = (int)$_POST['artist_id'];
    $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;

    $cover_url = null;

    // 2. Xử lý Upload ảnh bìa
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            // Tạo tên file duy nhất
            $filename = 'album_' . time() . '_' . uniqid() . '.' . $ext;
            $target = '../uploads/covers/' . $filename;

            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target)) {
                $cover_url = '/uploads/covers/' . $filename;
            }
        }
    }

    // 3. Thực thi SQL
    try {
        if ($action === 'add') {
            // Thêm Album mới
            $sql = "INSERT INTO albums (title, artist_id, release_date, cover_url) VALUES (?, ?, ?, ?)";
            $db->prepare($sql)->execute([$title, $artist_id, $release_date, $cover_url]);
        } 
        else if ($action === 'edit' && $album_id) {
            // Sửa Album
            $sql = "UPDATE albums SET title = ?, artist_id = ?, release_date = ? WHERE id = ?";
            $db->prepare($sql)->execute([$title, $artist_id, $release_date, $album_id]);

            // Cập nhật ảnh bìa mới nếu có upload
            if ($cover_url) {
                $db->prepare("UPDATE albums SET cover_url = ? WHERE id = ?")->execute([$cover_url, $album_id]);
            }
        }

        header("Location: albums.php?status=success");
        exit();
    } catch (PDOException $e) {
        die("Lỗi: " . $e->getMessage());
    }
}