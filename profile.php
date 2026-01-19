<?php
// BẬT HIỂN THỊ LỖI (DEBUG MODE)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. NHÚNG CONFIG
if (file_exists('config.php')) {
    require_once 'config.php';
} elseif (file_exists('includes/config.php')) {
    require_once 'includes/config.php'; 
} else {
    die("Lỗi: Không tìm thấy file config.php!");
}

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// 3. LẤY THÔNG TIN USER
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        redirect('login.php');
    }

    $user_avatar = !empty($user['avatar']) 
        ? SITE_URL . $user['avatar'] 
        : "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&background=1db954&color=fff&size=256";

} catch (PDOException $e) {
    die("Lỗi truy vấn User: " . $e->getMessage());
}

// 4. LẤY THỐNG KÊ CƠ BẢN
$playlist_count = 0;
$following_count = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM playlists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $playlist_count = $stmt->fetchColumn();
    
    // Kiểm tra bảng follows nếu có
    $stmt = $db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt->execute([$user_id]);
    $following_count = $stmt->fetchColumn();
} catch (Exception $e) {}


// =========================================================================
// XỬ LÝ LOGIC TAB (PLAYLIST vs FAVORITES)
// =========================================================================
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'playlists'; // Mặc định là playlists

// Dữ liệu cho từng tab
$my_playlists = [];
$favorite_songs = [];

if ($current_tab == 'favorites') {
    // --- LẤY DANH SÁCH YÊU THÍCH ---
    try {
        $sql = "SELECT s.*, a.name as artist_name 
                FROM songs s
                JOIN favorites f ON s.id = f.song_id
                LEFT JOIN artists a ON s.artist_id = a.id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $favorite_songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $favorite_songs = [];
    }
} else {
    // --- LẤY DANH SÁCH PLAYLIST (Mặc định) ---
    try {
        $stmt = $db->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $my_playlists = $stmt->fetchAll();
    } catch (Exception $e) {
        $my_playlists = [];
    }
}

// =========================================================================
// GIAO DIỆN
// =========================================================================
$page_title = "Hồ sơ cá nhân";
require_once 'includes/header.php'; 
?>

<style>
    /* CSS Cấu trúc Profile */
    :root { --profile-bg-start: rgb(90, 98, 90); --profile-bg-end: #121212; }

    .profile-header {
        background: linear-gradient(180deg, var(--profile-bg-start) 0%, var(--profile-bg-end) 100%);
        padding: 100px 30px 30px 30px;
        display: flex; align-items: flex-end; gap: 30px;
    }
    .profile-avatar {
        width: 232px; height: 232px; border-radius: 50%;
        object-fit: cover; box-shadow: 0 4px 60px rgba(0,0,0,0.5); flex-shrink: 0;
    }
    .profile-info { color: white; width: 100%; }
    .profile-type { font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
    .profile-name { font-size: clamp(40px, 6vw, 90px); font-weight: 900; margin: 0; line-height: 1; letter-spacing: -2px; }
    .profile-stats { margin-top: 20px; font-size: 16px; display: flex; align-items: center; gap: 5px; }
    .btn-edit-profile {
        display: inline-block; margin-top: 15px; border: 1px solid #727272;
        color: white; text-decoration: none; padding: 7px 20px; border-radius: 50px;
        font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    }
    .btn-edit-profile:hover { border-color: white; transform: scale(1.05); }

    /* CSS Content & Tabs */
    .profile-content { background-color: #121212; padding: 20px 30px; min-height: 50vh; }
    
    .profile-nav {
        display: flex; gap: 30px; margin-bottom: 30px; border-bottom: 1px solid #333;
    }
    .nav-link {
        background: none; border: none; color: #b3b3b3; font-size: 16px; font-weight: 700;
        padding-bottom: 10px; cursor: pointer; text-decoration: none; position: relative;
    }
    .nav-link:hover { color: white; }
    .nav-link.active { color: white; }
    .nav-link.active::after {
        content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 3px; background-color: #1db954;
    }

    /* CSS Playlist Grid */
    .playlist-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 24px;
    }
    .playlist-card {
        background: #181818; padding: 16px; border-radius: 8px;
        text-decoration: none; color: white; display: block; transition: 0.3s;
    }
    .playlist-card:hover { background: #282828; }
    .playlist-img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 4px; margin-bottom: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.5); }
    .playlist-title { font-weight: 700; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 5px; }
    .playlist-desc { color: #b3b3b3; font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* CSS Song List (Cho Favorites) */
    .song-list-item {
        display: flex; align-items: center; padding: 10px 20px; border-radius: 5px; color: white; transition: 0.2s;
    }
    .song-list-item:hover { background-color: rgba(255,255,255,0.1); }
    .song-img-small { width: 40px; height: 40px; object-fit: cover; margin-right: 15px; border-radius: 4px; }
    .song-info-flex { flex: 1; display: flex; flex-direction: column; }
    .song-title-row { font-weight: 600; font-size: 16px; color: white; text-decoration: none; }
    .song-artist-row { font-size: 13px; color: #b3b3b3; }
    .song-actions { display: flex; align-items: center; gap: 15px; }
    .btn-icon { background: none; border: none; color: #b3b3b3; cursor: pointer; font-size: 16px; }
    .btn-icon:hover { color: white; }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-header { flex-direction: column; align-items: center; text-align: center; padding-top: 100px; }
        .profile-avatar { width: 180px; height: 180px; }
        .profile-stats { justify-content: center; }
    }
</style>

<div class="container-fluid p-0">
    <div class="profile-header">
        <img src="<?php echo $user_avatar; ?>" alt="Avatar" class="profile-avatar">
        
        <div class="profile-info">
            <div class="profile-type">Hồ sơ</div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
            
            <div class="profile-stats">
                <?php if(!empty($user['country'])): ?>
                    <span><?php echo htmlspecialchars($user['country']); ?></span> • 
                <?php endif; ?>
                <span><?php echo $playlist_count; ?> Playlist</span> • 
                <span><?php echo $following_count; ?> Đang theo dõi</span>
            </div>

            <a href="edit_profile.php" class="btn-edit-profile">Chỉnh sửa hồ sơ</a>
        </div>
    </div>

    <div class="profile-content">
        
        <div class="profile-nav">
            <a href="profile.php?tab=playlists" class="nav-link <?php echo $current_tab != 'favorites' ? 'active' : ''; ?>">
                Playlist của tôi
            </a>
            <a href="profile.php?tab=favorites" class="nav-link <?php echo $current_tab == 'favorites' ? 'active' : ''; ?>">
                Bài hát yêu thích
            </a>
        </div>

        <?php if ($current_tab != 'favorites'): ?>
            <?php if(empty($my_playlists)): ?>
                <div class="text-muted" style="color: #b3b3b3;">
                    <p>Bạn chưa tạo playlist nào.</p>
                    <a href="#" class="btn btn-primary" style="margin-top: 10px; border-radius: 50px; padding: 10px 30px;">Tạo Playlist</a>
                </div>
            <?php else: ?>
                <div class="playlist-grid">
                    <?php foreach($my_playlists as $pl): ?>
                        <a href="playlist.php?id=<?php echo $pl['id']; ?>" class="playlist-card">
                            <img src="<?php 
                                echo !empty($pl['image']) 
                                    ? SITE_URL . $pl['image'] 
                                    : SITE_URL . '/assets/images/default-playlist.jpg'; 
                                ?>" class="playlist-img" alt="Cover">
                            <div class="playlist-title"><?php echo htmlspecialchars($pl['name']); ?></div>
                            <div class="playlist-desc">
                                <?php echo !empty($pl['description']) ? htmlspecialchars($pl['description']) : "Của " . htmlspecialchars($user['username']); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        
        <?php else: ?>
            <?php if(empty($favorite_songs)): ?>
                <div class="text-center" style="padding: 50px 0; color: #b3b3b3;">
                    <i class="far fa-heart" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3>Bạn chưa thích bài hát nào</h3>
                    <p>Hãy thả tim cho những bài hát bạn yêu thích để lưu vào đây.</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 15px; border-radius: 50px; padding: 10px 30px;">Khám phá ngay</a>
                </div>
            <?php else: ?>
                <div class="songs-list">
                    <?php foreach($favorite_songs as $index => $song): ?>
                        <div class="song-list-item" data-song-id="<?php echo $song['id']; ?>">
                            <div style="width: 30px; color: #b3b3b3;"><?php echo $index + 1; ?></div>
                            
                            <div style="position: relative; margin-right: 15px; cursor: pointer;" onclick="playSong(<?php echo $song['id']; ?>)">
                                <img src="<?php echo !empty($song['image_url']) ? SITE_URL . $song['image_url'] : SITE_URL . '/assets/images/default-cover.jpg'; ?>" 
                                     class="song-img-small">
                            </div>

                            <div class="song-info-flex">
                                <a href="player.php?id=<?php echo $song['id']; ?>" class="song-title-row">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a>
                                <span class="song-artist-row"><?php echo htmlspecialchars($song['artist_name']); ?></span>
                            </div>

                            <div class="song-actions">
                                <span style="font-size: 13px; color: #b3b3b3; margin-right: 10px;">
                                    <?php echo isset($song['duration']) ? $song['duration'] : '--:--'; ?>
                                </span>
                                
                                <button class="btn-icon" onclick="toggleFavoriteInProfile(<?php echo $song['id']; ?>, this)" title="Bỏ thích">
                                    <i class="fas fa-heart" style="color: #1db954;"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script>
function toggleFavoriteInProfile(songId, btn) {
    // 1. Gọi hàm toggleFavorite gốc trong main.js để xử lý API
    // Giả sử hàm toggleFavorite(songId) đã có trong main.js
    if (typeof toggleFavorite === "function") {
        toggleFavorite(songId);
    } else {
        // Fallback nếu chưa có hàm gốc (gọi trực tiếp API)
        fetch('api/favorites.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({song_id: songId})
        });
    }

    // 2. Hiệu ứng biến mất ngay lập tức (Chỉ áp dụng khi đang ở tab Favorites)
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab') === 'favorites') {
        const row = btn.closest('.song-list-item');
        if(row) {
            row.style.transition = "all 0.3s ease";
            row.style.opacity = "0";
            row.style.transform = "translateX(-20px)";
            
            setTimeout(() => {
                row.remove();
                // Kiểm tra nếu hết bài thì reload để hiện empty state
                if(document.querySelectorAll('.song-list-item').length === 0) {
                    location.reload(); 
                }
            }, 300);
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>