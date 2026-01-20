<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$conn = getDB();

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Empty keyword'
    ]);
    exit;
}

$keyword = '%' . $q . '%';

/* SEARCH SONGS */

$sqlSongs = "
    SELECT 
        s.id,
        s.title,
        s.image_url,
        s.file_url,
        a.name AS artist_name
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    WHERE s.title LIKE :keyword
    ORDER BY s.play_count DESC
    LIMIT 10
";

$stmt = $conn->prepare($sqlSongs);
$stmt->execute(['keyword' => $keyword]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* SEARCH ARTISTS */

$sqlArtists = "
    SELECT 
        id,
        name,
        avatar,
        country
    FROM artists
    WHERE name LIKE :keyword
    LIMIT 5
";

$stmt = $conn->prepare($sqlArtists);
$stmt->execute(['keyword' => $keyword]);
$artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'songs' => $songs,
    'artists' => $artists
]);
