<?php
// BẬT HIỂN THỊ LỖI
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Lịch sử nghe nhạc";
require_once 'includes/header.php';
$db = getDB();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];

// 2. LẤY DỮ LIỆU LỊCH SỬ (Giới hạn 50 bài gần nhất)
try {
    $stmt = $db->prepare("
        SELECT h.*, 
               s.title, s.image_url, s.file_url, s.duration,
               a.name as artist_name, a.id as artist_id,
               al.title as album_title, al.id as album_id
        FROM listening_history h
        JOIN songs s ON h.song_id = s.id
        LEFT JOIN artists a ON s.artist_id = a.id
        LEFT JOIN albums al ON s.album_id = al.id
        WHERE h.user_id = ?
        ORDER BY h.listened_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $songs = [];
}

$total_songs = count($songs);
?>

<style>
    /* Sử dụng style đồng bộ với Profile/Playlist */
    :root {
        --history-bg-start: #18c126ff; /* Tím */
        --history-bg-end: #121212;
    }

    .history-header {
        background: linear-gradient(180deg, var(--history-bg-start) 0%, var(--history-bg-end) 100%);
        padding: 80px 30px 30px 30px;
        display: flex;
        align-items: flex-end;
        gap: 30px;
        min-height: 340px;
    }

    /* Icon box giống trang Playlist của tôi */
    .history-icon-box {
        width: 232px;
        height: 232px;
        background: linear-gradient(135deg, #3d9e43ff 0%, #18c126ff 100%);
        box-shadow: 0 4px 60px rgba(0,0,0,0.5);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 80px;
        color: white;
        border-radius: 4px;
    }

    .history-info {
        color: white;
        width: 100%;
    }

    .history-type {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .history-name {
        font-size: clamp(40px, 6vw, 90px);
        font-weight: 900;
        margin: 0;
        line-height: 1;
        letter-spacing: -2px;
    }

    .history-stats {
        margin-top: 20px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
        color: rgba(255,255,255,0.7);
    }
    
    .history-stats b { color: #fff; }

    .history-content {
        background-color: #121212;
        padding: 30px;
        min-height: 50vh;
    }

    /* Action Bar */
    .action-bar { margin-bottom: 30px; }
    .btn-play-large {
        width: 56px; height: 56px; border-radius: 50%; background: #1db954; color: white; border: none;
        font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all 0.3s; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .btn-play-large:hover { transform: scale(1.06); background: #1ed760; }

    /* Song List Table */
    .songs-table-header {
        display: grid;
        grid-template-columns: 50px 6fr 4fr 3fr 100px;
        gap: 16px;
        padding: 8px 16px;
        border-bottom: 1px solid #282828;
        color: #b3b3b3;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .song-row {
        display: grid;
        grid-template-columns: 50px 6fr 4fr 3fr 100px;
        gap: 16px;
        padding: 10px 16px;
        border-radius: 4px;
        align-items: center;
        color: white;
        transition: 0.2s;
    }

    .song-row:hover { background-color: #2a2a2a; }

    .song-number { color: #b3b3b3; text-align: center; font-size: 16px; }
    .song-row:hover .song-number { display: none; }
    
    .btn-play-small { display: none; background: transparent; border: none; color: white; font-size: 14px; cursor: pointer; margin: 0 auto; }
    .song-row:hover .btn-play-small { display: block; }

    .song-title-cell { display: flex; align-items: center; }
    .song-title-cell img { width: 40px; height: 40px; border-radius: 4px; object-fit: cover; margin-right: 15px; }
    
    .song-title-text h4 { font-size: 16px; margin: 0 0 4px 0; font-weight: 500; color: white; }
    .song-title-text a { color: white; text-decoration: none; }
    .song-title-text a:hover { text-decoration: underline; }

    .text-muted-link { color: #b3b3b3; text-decoration: none; font-size: 14px; }
    .text-muted-link:hover { color: white; text-decoration: underline; }

    .timestamp { font-size: 14px; color: #b3b3b3; }

    /* Responsive */
    @media (max-width: 768px) {
        .history-header { flex-direction: column; align-items: center; text-align: center; padding-top: 100px; }
        .history-icon-box { width: 180px; height: 180px; }
        .songs-table-header, .song-row { grid-template-columns: 40px 1fr 60px; }
        .song-artist-cell, .song-time-cell { display: none; }
    }
</style>

<div class="container-fluid p-0">
    <div class="history-header">

        
        <div class="history-info">
            <div class="history-type">Thư viện</div>
            <h1 class="history-name">Lịch sử nghe</h1>
            
            <div class="history-stats">
                <b><?php echo $_SESSION['username']; ?></b>
                <span>• <?php echo $total_songs; ?> bài hát gần đây</span>
            </div>
        </div>
    </div>

    <div class="history-content">
        <?php if(!empty($songs)): ?>
            <div class="action-bar">
                <button class="btn-play-large" onclick="playFirstSong()" title="Phát lại tất cả">
                    <i class="fas fa-play"></i>
                </button>
            </div>

            <div class="songs-table-header">
                <div>#</div>
                <div>Tiêu đề</div>
                <div class="song-artist-cell">Album</div>
                <div class="song-time-cell">Thời gian</div>
                <div style="text-align: right;"><i class="far fa-clock"></i></div>
            </div>

            <div class="songs-list">
                <?php foreach($songs as $index => $song): 
                     $img = !empty($song['image_url']) ? SITE_URL . $song['image_url'] : SITE_URL . '/assets/images/default-cover.jpg';
                     
                     // Xử lý thời gian hiển thị (Vừa xong, X phút trước...)
                     $time_str = "";
                     $time = strtotime($song['listened_at']);
                     $diff = time() - $time;
                     if ($diff < 60) $time_str = 'Vừa xong';
                     elseif ($diff < 3600) $time_str = floor($diff/60) . ' phút trước';
                     elseif ($diff < 86400) $time_str = floor($diff/3600) . ' giờ trước';
                     else $time_str = date('d/m/Y', $time);
                ?>
                <div class="song-row" data-song-id="<?php echo $song['song_id']; ?>">
                    <div>
                        <span class="song-number"><?php echo $index + 1; ?></span>
                        <button class="btn-play-small" onclick="playSong(<?php echo $song['song_id']; ?>)">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    
                    <div class="song-title-cell">
                        <img src="<?php echo $img; ?>" alt="Cover">
                        <div class="song-title-text">
                            <h4>
                                <a href="player.php?id=<?php echo $song['song_id']; ?>">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a>
                            </h4>
                            <a href="artist.php?id=<?php echo $song['artist_id']; ?>" class="text-muted-link">
                                <?php echo htmlspecialchars($song['artist_name']); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="song-artist-cell">
                        <a href="album.php?id=<?php echo $song['album_id']; ?>" class="text-muted-link">
                            <?php echo htmlspecialchars($song['album_title'] ?? '-'); ?>
                        </a>
                    </div>
                    
                    <div class="song-time-cell timestamp">
                        <?php echo $time_str; ?>
                    </div>
                    
                    <div style="text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:15px;">
                        <button onclick="toggleFavorite(<?php echo $song['song_id']; ?>)" 
                                style="background:none; border:none; color:#b3b3b3; cursor:pointer;" title="Yêu thích">
                            <i class="far fa-heart"></i>
                        </button>
                        <span style="font-size:14px; color:#b3b3b3; min-width:40px;">
                            <?php echo $song['duration']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 60px 0; color: #b3b3b3;">
                <i class="fas fa-history" style="font-size: 64px; margin-bottom: 20px; opacity: 0.5;"></i>
                <h2>Chưa có lịch sử</h2>
                <p>Những bài hát bạn nghe sẽ xuất hiện tại đây.</p>
                <a href="index.php" class="btn btn-primary" 
                   style="margin-top:20px; padding:12px 30px; border-radius:50px; text-decoration:none; display:inline-block; background:#1db954; color:white; font-weight:700;">
                   Nghe nhạc ngay
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function playFirstSong() {
    const firstRow = document.querySelector('.song-row');
    if(firstRow && window.playSong) {
        window.playSong(firstRow.dataset.songId);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>