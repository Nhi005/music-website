<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$conn = getDB();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['song_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing song_id']);
    exit;
}

$song_id = (int)$data['song_id'];
$user_id = $_SESSION['user_id'] ?? null;

// trigger sẽ tự tăng play_count
$sql = "INSERT INTO listening_history (user_id, song_id) VALUES (:user_id, :song_id)";
$stmt = $conn->prepare($sql);

$stmt->execute([
    'user_id' => $user_id,
    'song_id' => $song_id
]);

echo json_encode(['success' => true]);
