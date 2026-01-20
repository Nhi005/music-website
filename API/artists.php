<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$conn = getDB();

/* CHI TIẾT NGHỆ SĨ */

if (isset($_GET['id'])) {
    $artist_id = (int)$_GET['id'];

    // Lấy thông tin nghệ sĩ
    $sqlArtist = "SELECT * FROM artists WHERE id = :id LIMIT 1";
    $stmt = $conn->prepare($sqlArtist);
    $stmt->execute(['id' => $artist_id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artist) {
        echo json_encode(['success' => false, 'message' => 'Artist not found']);
        exit;
    }

    // Lấy bài hát của nghệ sĩ
    $sqlSongs = "
        SELECT 
            id,
            title,
            image_url,
            file_url,
            play_count
        FROM songs
        WHERE artist_id = :artist_id
        ORDER BY play_count DESC
    ";

    $stmt = $conn->prepare($sqlSongs);
    $stmt->execute(['artist_id' => $artist_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'artist' => $artist,
        'songs' => $songs
    ]);
    exit;
}

/* DANH SÁCH NGHỆ SĨ */

$sql = "
    SELECT 
        id,
        name,
        avatar,
        country
    FROM artists
    ORDER BY name ASC
";

$stmt = $conn->query($sql);
$artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'artists' => $artists
]);
