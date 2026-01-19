<?php
require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $title = $_POST['title'];
    $artist_id = $_POST['artist_id'];
    $album_id = !empty($_POST['album_id']) ? $_POST['album_id'] : null; // Xử lý album_id
    $duration = $_POST['duration'];
    $song_id = $_POST['song_id'] ?? null;

    $file_url = "";
    $image_url = "";

    // Xử lý upload MP3
    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        $ext = pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_song.' . $ext;
        move_uploaded_file($_FILES['audio_file']['tmp_name'], "../uploads/songs/" . $filename);
        $file_url = "/uploads/songs/" . $filename;
    }

    // Xử lý upload Ảnh bìa
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_cover.' . $ext;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], "../uploads/covers/" . $filename);
        $image_url = "/uploads/covers/" . $filename;
    }

    if ($action == 'add') {
        // Cập nhật câu lệnh INSERT có album_id
        $stmt = $db->prepare("INSERT INTO songs (title, artist_id, album_id, duration, file_url, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $artist_id, $album_id, $duration, $file_url, $image_url]);
    } else {
        // Cập nhật câu lệnh UPDATE có album_id
        $sql = "UPDATE songs SET title = ?, artist_id = ?, album_id = ?, duration = ? WHERE id = ?";
        $params = [$title, $artist_id, $album_id, $duration, $song_id];
        $db->prepare($sql)->execute($params);

        if ($file_url) {
            $db->prepare("UPDATE songs SET file_url = ? WHERE id = ?")->execute([$file_url, $song_id]);
        }
        if ($image_url) {
            $db->prepare("UPDATE songs SET image_url = ? WHERE id = ?")->execute([$image_url, $song_id]);
        }
    }

    header("Location: songs.php?success=1");
}