<?php
// BẬT HIỂN THỊ LỖI (DEBUG MODE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// NHÚNG CONFIG
if (file_exists('config.php')) {
    require_once 'config.php';
} elseif (file_exists('includes/config.php')) {
    require_once 'includes/config.php'; 
} else {
    die("Lỗi: Không tìm thấy file config.php!");
}

$db = getDB();
$album_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($album_id <= 0) {
    redirect('index.php');
}

// LẤY THÔNG TIN ALBUM
try {
    $stmt = $db->prepare("SELECT al.*, a.name as artist_name, a.id as artist_id 
                          FROM albums al 
                          LEFT JOIN artists a ON al.artist_id = a.id 
                          WHERE al.id = ?");
    $stmt->execute([$album_id]);
    $album = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$album) {
        redirect('index.php');
    }
} catch(Exception $e) {
    die("Lỗi: " . $e->getMessage());
}

// LẤY TẤT CẢ BÀI HÁT TRONG ALBUM
try {
    $stmt = $db->prepare("SELECT s.*, a.name as artist_name, a.id as artist_id 
                          FROM songs s 
                          LEFT JOIN artists a ON s.artist_id = a.id 
                          WHERE s.album_id = ? 
                          ORDER BY s.id ASC");
    $stmt->execute([$album_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $songs = [];
}

// TÍNH TỔNG THỜI LƯỢNG VÀ LƯỢT NGHE
$total_duration = 0;
$total_plays = 0;
foreach ($songs as $song) {
    $time_parts = explode(':', $song['duration']);
    $minutes = (int)$time_parts[0];
    $seconds = (int)$time_parts[1];
    $total_duration += ($minutes * 60) + $seconds;
    $total_plays += $song['play_count'];
}

$hours = floor($total_duration / 3600);
$minutes = floor(($total_duration % 3600) / 60);
$duration_text = $hours > 0 ? "{$hours} giờ {$minutes} phút" : "{$minutes} phút";

// XỬ LÝ COVER ALBUM
$album_cover = !empty($album['cover_url']) 
    ? SITE_URL . $album['cover_url'] 
    : SITE_URL . '/assets/images/default-cover.jpg';

$page_title = $album['title'];
require_once 'includes/header.php'; 
?>

<style>
:root {
    --album-bg-color: #2a5298; /* Màu gradient cho header */
}

.album-header {
    background: linear-gradient(180deg, var(--album-bg-color) 0%, #121212 100%);
    padding: 80px 30px 30px 30px;
    display: flex;
    align-items: flex-end;
    gap: 30px;
    min-height: 340px;
}

.album-cover-large {
    width: 232px;
    height: 232px;
    border-radius: 4px;
    object-fit: cover;
    box-shadow: 0 4px 60px rgba(0,0,0,0.5);
    flex-shrink: 0;
}

.album-info-header {
    color: white;
    width: 100%;
}

.album-type {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.album-title {
    font-size: clamp(32px, 5vw, 72px);
    font-weight: 900;
    margin: 0 0 12px 0;
    line-height: 1.1;
    letter-spacing: -1px;
}

.album-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.album-meta a {
    color: white;
    text-decoration: none;
    font-weight: 700;
}

.album-meta a:hover {
    text-decoration: underline;
}

.album-meta .dot {
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
}

.album-content {
    background-color: #121212;
    padding: 24px 30px;
    min-height: 60vh;
}

.album-actions {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 32px;
    padding: 24px 0;
}

.btn-play-large {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #1db954;
    color: white;
    border: none;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.btn-play-large:hover {
    transform: scale(1.06);
    background: #1ed760;
}

.btn-action {
    background: transparent;
    border: none;
    color: #b3b3b3;
    font-size: 32px;
    cursor: pointer;
    transition: color 0.2s;
}

.btn-action:hover {
    color: white;
}

.btn-action.active {
    color: #1db954;
}

/* SONGS TABLE */
.songs-table {
    width: 100%;
}

.songs-table-header {
    display: grid;
    grid-template-columns: 40px 6fr 4fr 2fr 100px;
    gap: 16px;
    padding: 8px 16px;
    border-bottom: 1px solid #282828;
    color: #b3b3b3;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.song-row {
    display: grid;
    grid-template-columns: 40px 6fr 4fr 2fr 100px;
    gap: 16px;
    padding: 8px 16px;
    border-radius: 4px;
    align-items: center;
    transition: background-color 0.2s;
    color: white;
}

.song-row:hover {
    background-color: #282828;
}

.song-row .song-number {
    color: #b3b3b3;
    font-size: 16px;
    text-align: center;
}

.song-row:hover .song-number {
    display: none;
}

.song-row .btn-play-small {
    display: none;
    background: transparent;
    border: none;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

.song-row:hover .btn-play-small {
    display: block;
}

.song-title-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.song-thumbnail {
    width: 40px;
    height: 40px;
    border-radius: 2px;
    object-fit: cover;
}

.song-title-text h4 {
    font-size: 16px;
    font-weight: 400;
    margin: 0 0 4px 0;
    color: white;
}

.song-title-text p {
    font-size: 14px;
    color: #b3b3b3;
    margin: 0;
}

.song-artist-cell a {
    color: #b3b3b3;
    text-decoration: none;
    font-size: 14px;
}

.song-artist-cell a:hover {
    color: white;
    text-decoration: underline;
}

.song-plays-cell {
    color: #b3b3b3;
    font-size: 14px;
}

.song-duration {
    color: #b3b3b3;
    font-size: 14px;
    text-align: right;
}

.song-actions-cell {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    opacity: 0;
    transition: opacity 0.2s;
}

.song-row:hover .song-actions-cell {
    opacity: 1;
}

.song-actions-cell button {
    background: transparent;
    border: none;
    color: #b3b3b3;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    transition: color 0.2s;
}

.song-actions-cell button:hover {
    color: white;
}

.album-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 1px solid #282828;
    color: #b3b3b3;
    font-size: 14px;
}

.album-footer p {
    margin: 8px 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .album-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding-top: 100px;
    }
    
    .album-cover-large {
        width: 192px;
        height: 192px;
    }
    
    .album-title {
        font-size: 32px;
    }
    
    .album-actions {
        justify-content: center;
    }
    
    .songs-table-header {
        grid-template-columns: 40px 1fr 80px;
    }
    
    .song-row {
        grid-template-columns: 40px 1fr 80px;
    }
    
    .song-artist-cell,
    .song-plays-cell,
    .song-actions-cell {
        display: none;
    }
    
    .song-title-text p {
        display: block;
    }
}
</style>

<div class="container-fluid p-0">
    <!-- ALBUM HEADER -->
    <div class="album-header">
        <img src="<?php echo $album_cover; ?>" 
             alt="<?php echo htmlspecialchars($album['title']); ?>" 
             class="album-cover-large">
        
        <div class="album-info-header">
            <div class="album-type">Album</div>
            <h1 class="album-title"><?php echo htmlspecialchars($album['title']); ?></h1>
            
            <div class="album-meta">
                <a href="artist.php?id=<?php echo $album['artist_id']; ?>">
                    <?php echo htmlspecialchars($album['artist_name']); ?>
                </a>
                <span class="dot"></span>
                <span><?php echo date('Y', strtotime($album['release_date'])); ?></span>
                <span class="dot"></span>
                <span><?php echo count($songs); ?> bài hát</span>
                <span class="dot"></span>
                <span><?php echo $duration_text; ?></span>
            </div>
        </div>
    </div>

    <!-- ALBUM CONTENT -->
    <div class="album-content">
        <!-- ALBUM ACTIONS -->
        <div class="album-actions">
            <button class="btn-play-large" onclick="playAlbum()" title="Phát">
                <i class="fas fa-play"></i>
            </button>
            
            <button class="btn-action" onclick="toggleFavoriteAlbum(<?php echo $album_id; ?>)" title="Lưu vào thư viện">
                <i class="far fa-heart"></i>
            </button>
            
            <button class="btn-action" onclick="shareAlbum()" title="Chia sẻ">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <!-- SONGS TABLE -->
        <?php if(empty($songs)): ?>
            <div class="text-center" style="color: #b3b3b3; padding: 40px 0;">
                <p>Album chưa có bài hát nào.</p>
            </div>
        <?php else: ?>
            <div class="songs-table">
                <div class="songs-table-header">
                    <div>#</div>
                    <div>Tiêu đề</div>
                    <div class="song-artist-cell">Nghệ sĩ</div>
                    <div class="song-plays-cell">Lượt nghe</div>
                    <div style="text-align: right;"><i class="far fa-clock"></i></div>
                </div>
                
                <?php foreach($songs as $index => $song): 
                    // Tạo link ảnh trước để code bên dưới gọn hơn
                    $song_img_full = !empty($song['image_url']) 
                        ? SITE_URL . $song['image_url'] 
                        : SITE_URL . '/assets/images/default-cover.jpg';
                ?>
                <div class="song-row" 
                    data-song-id="<?php echo $song['id']; ?>"
                    data-title="<?php echo htmlspecialchars($song['title']); ?>"
                    data-artist="<?php echo htmlspecialchars($song['artist_name']); ?>"
                    data-image="<?php echo $song_img_full; ?>">
                    <div>
                        <span class="song-number"><?php echo $index + 1; ?></span>
                        <button class="btn-play-small" onclick="playSong(<?php echo $song['id']; ?>)">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    
                    <div class="song-title-cell">
                        <img src="<?php 
                            echo !empty($song['image_url']) 
                                ? SITE_URL . $song['image_url'] 
                                : SITE_URL . '/assets/images/default-cover.jpg';
                        ?>" alt="<?php echo htmlspecialchars($song['title']); ?>" class="song-thumbnail">
                        
                        <div class="song-title-text">
                            <h4>
                                <a href="player.php?id=<?php echo $song['id']; ?>" style="color: white; text-decoration: none;">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a>
                            </h4>
                            <p class="d-md-none">
                                <a href="artist.php?id=<?php echo $song['artist_id']; ?>" style="color: #b3b3b3; text-decoration: none;">
                                    <?php echo htmlspecialchars($song['artist_name']); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="song-artist-cell">
                        <a href="artist.php?id=<?php echo $song['artist_id']; ?>">
                            <?php echo htmlspecialchars($song['artist_name']); ?>
                        </a>
                    </div>
                    
                    <div class="song-plays-cell">
                        <?php echo number_format($song['play_count']); ?>
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                        <div class="song-actions-cell">
                            <button onclick="addToPlaylist(<?php echo $song['id']; ?>)" title="Thêm vào playlist">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button onclick="toggleFavorite(<?php echo $song['id']; ?>)" title="Yêu thích">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="song-duration"><?php echo $song['duration']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- ALBUM FOOTER INFO -->
            <div class="album-footer">
                <p><i class="fas fa-calendar"></i> Ngày phát hành: <?php echo date('d/m/Y', strtotime($album['release_date'])); ?></p>
                <p><i class="fas fa-play-circle"></i> Tổng lượt nghe: <?php echo number_format($total_plays); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// PHÁT TẤT CẢ BÀI HÁT TRONG ALBUM
function playAlbum() {
    const firstSong = document.querySelector('.song-row');
    if (firstSong) {
        const songId = firstSong.dataset.songId;
        playSong(songId);
    }
}

// CHIA SẺ ALBUM
function shareAlbum() {
    const url = window.location.href;
    const title = '<?php echo addslashes($album['title']); ?>';
    const artist = '<?php echo addslashes($album['artist_name']); ?>';
    
    if (navigator.share) {
        navigator.share({
            title: title,
            text: `Nghe album ${title} của ${artist}`,
            url: url
        }).catch(err => console.log('Lỗi chia sẻ:', err));
    } else {
        // Fallback: Copy link
        navigator.clipboard.writeText(url).then(() => {
            alert('Đã sao chép link album vào clipboard!');
        }).catch(err => {
            console.error('Lỗi sao chép:', err);
        });
    }
}

// LƯU ALBUM VÀO THƯ VIỆN (YÊU THÍCH)
function toggleFavoriteAlbum(albumId) {
    // TODO: Gọi API để lưu/bỏ lưu album
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        btn.classList.add('active');
        console.log('Đã lưu album:', albumId);
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        btn.classList.remove('active');
        console.log('Đã bỏ lưu album:', albumId);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>