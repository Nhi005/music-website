<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$conn = getDB();

// LẤY 1 BÀI HÁT
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $sql = "
        SELECT 
            s.id,
            s.title,
            s.duration,
            s.file_url,
            s.image_url,
            s.play_count,
            a.name AS artist_name
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        WHERE s.id = :id
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($song) {
        echo json_encode([
            'success' => true,
            'song' => $song
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Song not found'
        ]);
    }
    exit;
}

// LẤY DANH SÁCH BÀI HÁT
$sql = "
    SELECT 
        s.id,
        s.title,
        s.duration,
        s.file_url,
        s.image_url,
        s.play_count,
        a.name AS artist_name
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    ORDER BY s.play_count DESC
    LIMIT 20
";

$stmt = $conn->query($sql);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'songs' => $songs
]);
