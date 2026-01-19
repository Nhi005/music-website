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
$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artist_id <= 0) {
    redirect('index.php');
}

// ================== HÀM XỬ LÝ ẢNH (QUAN TRỌNG) ==================
function build_cover_url($path) {
    // Ảnh mặc định
    $default = SITE_URL . '/assets/images/default-cover.jpg';
    
    if (empty($path) || trim($path) === '') {
        return $default;
    }

    // Nếu là link online (http/https) -> giữ nguyên
    if (preg_match('~^https?://~', $path)) {
        return $path;
    }

    // Kiểm tra file nội bộ
    $clean_path = ltrim($path, '/');
    if (file_exists($clean_path)) {
        return SITE_URL . '/' . $clean_path;
    }

    return $default;
}

// LẤY THÔNG TIN NGHỆ SĨ
try {
    $stmt = $db->prepare("SELECT * FROM artists WHERE id = ?");
    $stmt->execute([$artist_id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$artist) {
        redirect('index.php');
    }
} catch(Exception $e) {
    die("Lỗi: " . $e->getMessage());
}

// LẤY TẤT CẢ BÀI HÁT CỦA NGHỆ SĨ
try {
    $stmt = $db->prepare("SELECT s.*, a.name as artist_name, al.title as album_title, al.id as album_id 
                          FROM songs s 
                          LEFT JOIN artists a ON s.artist_id = a.id 
                          LEFT JOIN albums al ON s.album_id = al.id 
                          WHERE s.artist_id = ? 
                          ORDER BY s.play_count DESC");
    $stmt->execute([$artist_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $songs = [];
}

// LẤY ALBUM CỦA NGHỆ SĨ
try {
    $stmt = $db->prepare("SELECT al.*, COUNT(s.id) as song_count 
                          FROM albums al 
                          LEFT JOIN songs s ON al.id = s.album_id 
                          WHERE al.artist_id = ? 
                          GROUP BY al.id 
                          ORDER BY al.release_date DESC");
    $stmt->execute([$artist_id]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $albums = [];
}

// TÍNH TỔNG LƯỢT NGHE
$total_plays = array_sum(array_column($songs, 'play_count'));

// XỬ LÝ AVATAR NGHỆ SĨ
$artist_avatar_path = $artist['avatar'] ?? '';
if (preg_match('~^https?://~', $artist_avatar_path)) {
    $artist_avatar = $artist_avatar_path;
} else {
    $artist_avatar = !empty($artist_avatar_path) 
        ? SITE_URL . $artist_avatar_path 
        : SITE_URL . '/assets/images/default-artist.jpg';
}

$page_title = $artist['name'];
require_once 'includes/header.php'; 
?>

<style>
:root {
    --artist-bg-color: #2a5298; /* Màu tím cho nghệ sĩ */
}

.artist-header {
    background: linear-gradient(180deg, var(--artist-bg-color) 0%, #121212 100%);
    padding: 80px 30px 30px 30px;
    display: flex;
    align-items: flex-end;
    gap: 30px;
    min-height: 340px;
}

.artist-avatar-large {
    width: 232px;
    height: 232px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 4px 60px rgba(0,0,0,0.5);
    flex-shrink: 0;
}

.artist-info-header {
    color: white;
    width: 100%;
}

.artist-type {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.verified-badge {
    background: #3d91ff;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.artist-name {
    font-size: clamp(32px, 5vw, 96px);
    font-weight: 900;
    margin: 0 0 12px 0;
    line-height: 1;
    letter-spacing: -2px;
}

.artist-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.artist-stats .dot {
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
}

.artist-content {
    background-color: #121212;
    padding: 24px 30px;
    min-height: 60vh;
}

.artist-actions {
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

.section-title {
    color: white;
    font-size: 24px;
    font-weight: 700;
    margin: 48px 0 24px 0;
}

.artist-bio {
    background: #181818;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 32px;
}

.artist-bio h3 {
    color: white;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
}

.artist-bio p {
    color: #b3b3b3;
    line-height: 1.6;
    font-size: 14px;
}

/* SONGS TABLE */
.songs-table {
    width: 100%;
    margin-bottom: 48px;
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

.song-album-cell {
    color: #b3b3b3;
    font-size: 14px;
}

.song-album-cell a {
    color: #b3b3b3;
    text-decoration: none;
}

.song-album-cell a:hover {
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

/* ALBUMS GRID */
.albums-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.album-card {
    background: #181818;
    padding: 16px;
    border-radius: 8px;
    transition: background-color 0.3s ease;
    text-decoration: none;
    color: white;
    display: block;
    position: relative;
}

.album-card:hover {
    background: #282828;
}

.album-cover {
    width: 100%;
    aspect-ratio: 1/1;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.5);
}

.album-info h3 {
    font-weight: 700;
    font-size: 16px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 8px;
}

.album-info p {
    color: #b3b3b3;
    font-size: 14px;
}

.album-play-btn {
    position: absolute;
    left: 50%;
    /* Vì không có khung bao ảnh, top 40% sẽ giúp nút nằm khớp vùng ảnh hơn là 50% */
    top: 40%; 
    /* Lệnh này giúp tâm của nút nằm chính xác tại điểm 50% */
    transform: translate(-50%, -50%);
    
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #1db954;
    color: white;
    border: none;
    font-size: 20px;
    
    display: none; /* Mặc định ẩn */
    align-items: center;
    justify-content: center;
    
    box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    transition: all 0.3s;
    z-index: 10;
}

.album-card:hover .album-play-btn {
    display: flex;
}

.album-play-btn:hover {
    /* Kết hợp cả căn giữa (translate) và phóng to (scale) */
    transform: translate(-50%, -50%) scale(1.1); 
    background: #1ed760;
    cursor: pointer;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .artist-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding-top: 100px;
    }
    
    .artist-avatar-large {
        width: 192px;
        height: 192px;
    }
    
    .artist-name {
        font-size: 48px;
    }
    
    .artist-actions {
        justify-content: center;
    }
    
    .songs-table-header {
        grid-template-columns: 40px 1fr 80px;
    }
    
    .song-row {
        grid-template-columns: 40px 1fr 80px;
    }
    
    .song-album-cell,
    .song-plays-cell,
    .song-actions-cell {
        display: none;
    }
    
    .albums-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<div class="container-fluid p-0">
    <div class="artist-header">
        <img src="<?php echo $artist_avatar; ?>" 
             alt="<?php echo htmlspecialchars($artist['name']); ?>" 
             class="artist-avatar-large">
        
        <div class="artist-info-header">
            <div class="artist-type">
                <span class="verified-badge"><i class="fas fa-check"></i></span>
                Nghệ sĩ được xác minh
            </div>
            <h1 class="artist-name"><?php echo htmlspecialchars($artist['name']); ?></h1>
            
            <div class="artist-stats">
                <?php if(!empty($artist['country'])): ?>
                    <span><?php echo htmlspecialchars($artist['country']); ?></span>
                    <span class="dot"></span>
                <?php endif; ?>
                <span><?php echo number_format($total_plays); ?> người nghe hàng tháng</span>
            </div>
        </div>
    </div>

    <div class="artist-content">
        <div class="artist-actions">
            <button class="btn-play-large" onclick="playArtist()" title="Phát">
                <i class="fas fa-play"></i>
            </button>
            
            <button class="btn-action" onclick="toggleFollowArtist(<?php echo $artist_id; ?>)" title="Theo dõi">
                <i class="fas fa-user-plus"></i>
            </button>
            
            <button class="btn-action" onclick="shareArtist()" title="Chia sẻ">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <?php if (!empty($artist['bio'])): ?>
        <div class="artist-bio">
            <h3>Giới thiệu</h3>
            <p><?php echo nl2br(htmlspecialchars($artist['bio'])); ?></p>
        </div>
        <?php endif; ?>

        <h2 class="section-title">Nổi bật</h2>
        <?php if(empty($songs)): ?>
            <div class="text-center" style="color: #b3b3b3; padding: 40px 0;">
                <p>Chưa có bài hát nào.</p>
            </div>
        <?php else: ?>
            <div class="songs-table">
                <div class="songs-table-header">
                    <div>#</div>
                    <div>Tiêu đề</div>
                    <div class="song-album-cell">Album</div>
                    <div class="song-plays-cell">Lượt nghe</div>
                    <div style="text-align: right;"><i class="far fa-clock"></i></div>
                </div>
                
                <?php foreach(array_slice($songs, 0, 10) as $index => $song): 
                    // TẠO URL ẢNH ĐỂ DÙNG CHUNG
                    $cover_url = build_cover_url($song['image_url'] ?? '');
                ?>
                <div class="song-row" 
                     data-song-id="<?php echo $song['id']; ?>"
                     data-title="<?php echo htmlspecialchars($song['title']); ?>"
                     data-artist="<?php echo htmlspecialchars($song['artist_name']); ?>"
                     data-image="<?php echo htmlspecialchars($cover_url); ?>">
                    
                    <div>
                        <span class="song-number"><?php echo $index + 1; ?></span>
                        <button class="btn-play-small" onclick="playSong(<?php echo $song['id']; ?>)">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    
                    <div class="song-title-cell">
                        <img src="<?php echo htmlspecialchars($cover_url); ?>" 
                             alt="<?php echo htmlspecialchars($song['title']); ?>" 
                             class="song-thumbnail">
                        
                        <div class="song-title-text">
                            <h4>
                                <a href="player.php?id=<?php echo $song['id']; ?>" style="color: white; text-decoration: none;">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a>
                            </h4>
                        </div>
                    </div>
                    
                    <div class="song-album-cell">
                        <?php if(!empty($song['album_title'])): ?>
                            <a href="album.php?id=<?php echo $song['album_id']; ?>">
                                <?php echo htmlspecialchars($song['album_title']); ?>
                            </a>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
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
        <?php endif; ?>

        <?php if (!empty($albums)): ?>
        <h2 class="section-title">Discography</h2>
        <div class="albums-grid">
            <?php foreach($albums as $album): 
                $album_cover = build_cover_url($album['cover_url'] ?? '');
            ?>
            <a href="album.php?id=<?php echo $album['id']; ?>" class="album-card">
                <img src="<?php echo htmlspecialchars($album_cover); ?>" 
                     alt="<?php echo htmlspecialchars($album['title']); ?>" 
                     class="album-cover">
                
                <button class="album-play-btn" onclick="event.preventDefault(); playAlbum(<?php echo $album['id']; ?>)">
                    <i class="fas fa-play"></i>
                </button>
                
                <div class="album-info">
                    <h3><?php echo htmlspecialchars($album['title']); ?></h3>
                    <p><?php echo date('Y', strtotime($album['release_date'])); ?> • <?php echo $album['song_count']; ?> bài hát</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// PHÁT NGHỆ SĨ (phát bài hát đầu tiên)
function playArtist() {
    const firstSong = document.querySelector('.song-row');
    if (firstSong) {
        // Lấy thông tin từ data attributes
        const songId = firstSong.dataset.songId;
        
        // Gọi hàm playSong
        if (window.playSong) {
            playSong(songId);
        } else {
            console.error('Hàm playSong không tồn tại!');
        }
    }
}

// PHÁT ALBUM
function playAlbum(albumId) {
    window.location.href = 'album.php?id=' + albumId;
}

// THEO DÕI NGHỆ SĨ
function toggleFollowArtist(artistId) {
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    if (icon.classList.contains('fa-user-plus')) {
        icon.classList.remove('fa-user-plus');
        icon.classList.add('fa-user-check');
        btn.classList.add('active');
        console.log('Đã theo dõi nghệ sĩ:', artistId);
    } else {
        icon.classList.remove('fa-user-check');
        icon.classList.add('fa-user-plus');
        btn.classList.remove('active');
        console.log('Đã bỏ theo dõi nghệ sĩ:', artistId);
    }
}

// CHIA SẺ NGHỆ SĨ
function shareArtist() {
    const url = window.location.href;
    const artistName = '<?php echo addslashes($artist['name']); ?>';
    
    if (navigator.share) {
        navigator.share({
            title: artistName,
            text: `Nghe nhạc của ${artistName}`,
            url: url
        }).catch(err => console.log('Lỗi chia sẻ:', err));
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('Đã sao chép link nghệ sĩ vào clipboard!');
        }).catch(err => {
            console.error('Lỗi sao chép:', err);
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>