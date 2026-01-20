<?php
require_once '../includes/config.php'; //
$db = getDB(); //

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $name = $_POST['name'];
    $country = $_POST['country'];
    $bio = $_POST['bio'];
    $artist_id = $_POST['artist_id'] ?? null;

    $avatar_url = "";

    // Xử lý upload ảnh Avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'artist_' . time() . '.' . $ext;
        
        // Tạo thư mục nếu chưa có
        if (!is_dir("../uploads/artists/")) {
            mkdir("../uploads/artists/", 0777, true);
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], "../uploads/artists/" . $filename)) {
            $avatar_url = "/uploads/artists/" . $filename;
        }
    }

    if ($action == 'add') {
        $stmt = $db->prepare("INSERT INTO artists (name, country, bio, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $country, $bio, $avatar_url]);
    } else {
        // Cập nhật thông tin cơ bản
        $sql = "UPDATE artists SET name = ?, country = ?, bio = ? WHERE id = ?";
        $db->prepare($sql)->execute([$name, $country, $bio, $artist_id]);

        // Nếu có upload ảnh mới thì cập nhật riêng
        if ($avatar_url) {
            $db->prepare("UPDATE artists SET avatar = ? WHERE id = ?")->execute([$avatar_url, $artist_id]);
        }
    }

    header("Location: artists.php?success=1");
}