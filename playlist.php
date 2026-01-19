<?php
// BẬT HIỂN THỊ LỖI
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/header.php';
$db = getDB();

$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($playlist_id <= 0) {
    redirect('profile.php');
}

// 1. LẤY THÔNG TIN PLAYLIST
try {
    $stmt = $db->prepare("SELECT p.*, u.username, u.avatar as user_avatar 
                          FROM playlists p 
                          LEFT JOIN users u ON p.user_id = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$playlist_id]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$playlist) redirect('profile.php');
    
    // Check quyền riêng tư
    if (!$playlist['is_public'] && (!isLoggedIn() || $_SESSION['user_id'] != $playlist['user_id'])) {
        redirect('profile.php');
    }
} catch(Exception $e) {
    die("Lỗi: " . $e->getMessage());
}

$is_owner = isLoggedIn() && $_SESSION['user_id'] == $playlist['user_id'];

// 2. LẤY BÀI HÁT
try {
    $stmt = $db->prepare("SELECT s.*, a.name as artist_name, a.id as artist_id, 
                          ps.position, ps.added_at 
                          FROM playlist_songs ps 
                          LEFT JOIN songs s ON ps.song_id = s.id 
                          LEFT JOIN artists a ON s.artist_id = a.id 
                          WHERE ps.playlist_id = ? 
                          ORDER BY ps.added_at DESC"); // Sắp xếp theo ngày thêm mới nhất
    $stmt->execute([$playlist_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $songs = [];
}

// Tính thời lượng
$total_duration = 0;
foreach ($songs as $song) {
    $parts = explode(':', $song['duration']);
    if(count($parts) == 2) $total_duration += $parts[0] * 60 + $parts[1];
}
$hours = floor($total_duration / 3600);
$minutes = floor(($total_duration % 3600) / 60);
$duration_text = $hours > 0 ? "{$hours} giờ {$minutes} phút" : "{$minutes} phút";

// Cover Image Logic
$has_custom_cover = false;
$playlist_cover = '';

if (!empty($songs) && !empty($songs[0]['image_url'])) {
    $playlist_cover = SITE_URL . $songs[0]['image_url'];
    $has_custom_cover = true;
}
?>

<style>
    /* Style đồng bộ với Profile/History */
    :root {
        --playlist-bg-start: #475448ff; /* Màu xanh đen sang trọng */
        --playlist-bg-end: #121212;
    }

    .playlist-header {
        background: linear-gradient(180deg, var(--playlist-bg-start) 0%, var(--playlist-bg-end) 100%);
        padding: 80px 30px 30px 30px;
        display: flex;
        align-items: flex-end;
        gap: 30px;
        min-height: 340px;
    }

    /* Khung ảnh bìa (Dùng cho cả ảnh thật và icon mặc định) */
    .playlist-cover-box {
        width: 232px;
        height: 232px;
        box-shadow: 0 4px 60px rgba(0,0,0,0.5);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #333; /* Fallback */
        border-radius: 4px;
        overflow: hidden;
    }

    /* Nếu không có ảnh -> Hiện Gradient + Icon */
    .playlist-cover-box.default {
        background: linear-gradient(135deg, #3d9e43ff 0%, #18c126ff 100%); /* Gradient xanh dương */
    }
    
    .playlist-cover-box.default i {
        font-size: 80px;
        color: white;
    }

    .playlist-cover-box img {
        width: 100%; height: 100%; object-fit: cover;
    }

    /* Thông tin bên phải */
    .playlist-info-header { color: white; width: 100%; }
    
    .playlist-type { font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
    .badge-private { background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: none; display: inline-flex; align-items: center; gap: 5px; }

    .playlist-name {
        font-size: clamp(32px, 5vw, 72px); /* Font to giống Profile */
        font-weight: 900;
        margin: 0 0 12px 0;
        line-height: 1.1;
        letter-spacing: -2px;
    }

    .playlist-desc { font-size: 16px; color: rgba(255,255,255,0.7); margin-bottom: 10px; }

    .playlist-meta { display: flex; align-items: center; gap: 5px; font-size: 14px; color: #fff; }
    .owner-link { color: white; text-decoration: none; font-weight: 700; }
    .owner-link:hover { text-decoration: underline; }
    .playlist-meta span { color: rgba(255,255,255,0.7); }

    /* Nội dung chính */
    .playlist-content { background-color: #121212; padding: 30px; min-height: 50vh; }

    /* Action Bar */
    .action-bar { display: flex; align-items: center; gap: 24px; margin-bottom: 32px; }
    .btn-play-large {
        width: 56px; height: 56px; border-radius: 50%; background: #1db954; color: white; border: none;
        font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all 0.3s; box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .btn-play-large:hover { transform: scale(1.06); background: #1ed760; }

    .btn-icon { background: transparent; border: none; color: #b3b3b3; font-size: 32px; cursor: pointer; transition: 0.2s; }
    .btn-icon:hover { color: white; }
    .btn-icon.active { color: #1db954; }

    /* Bảng bài hát */
    .songs-table-header {
        display: grid; grid-template-columns: 50px 6fr 4fr 3fr 80px;
        padding: 8px 16px; border-bottom: 1px solid #333; color: #b3b3b3; font-size: 12px; text-transform: uppercase; margin-bottom: 10px;
    }
    .song-row {
        display: grid; grid-template-columns: 50px 6fr 4fr 3fr 80px;
        padding: 10px 16px; border-radius: 4px; align-items: center; color: white; transition: 0.2s;
    }
    .song-row:hover { background: #2a2a2a; }
    .song-row:hover .song-number { display: none; }
    .song-row:hover .btn-play-small { display: block; }
    
    .song-number { color: #b3b3b3; text-align: center; }
    .btn-play-small { display: none; background: transparent; border: none; color: white; margin: 0 auto; cursor: pointer; }
    
    .song-title-cell { display: flex; align-items: center; gap: 15px; }
    .song-title-cell img { width: 40px; height: 40px; object-fit: cover; }
    .song-info h4 { font-size: 16px; margin: 0; color: white; font-weight: 500; }
    .song-info a { color: white; text-decoration: none; }
    .song-info a:hover { text-decoration: underline; }
    
    .text-muted-link { color: #b3b3b3; text-decoration: none; font-size: 14px; }
    .text-muted-link:hover { color: white; text-decoration: underline; }

    .btn-remove { opacity: 0; background: none; border: none; color: #b3b3b3; cursor: pointer; transition: 0.2s; font-size: 16px; }
    .song-row:hover .btn-remove { opacity: 1; }
    .btn-remove:hover { color: #ff5555; }

    /* Empty State */
    .empty-state { text-align: center; padding: 60px 0; color: #b3b3b3; }
    .empty-state i { font-size: 60px; margin-bottom: 20px; opacity: 0.5; }
    .btn-find { padding: 12px 32px; border-radius: 50px; background: #333; color: white; text-decoration: none; font-weight: 700; display: inline-block; margin-top: 20px; transition: 0.2s; border: 1px solid white; }
    .btn-find:hover { background: white; color: black; transform: scale(1.05); }

    /* Modal Styles (Giữ nguyên logic modal) */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
    .modal.active { display: flex; }
    .modal-content { background: #282828; width: 500px; padding: 24px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; color: white; margin-bottom: 8px; font-weight: 700; font-size: 14px; }
    .form-input { width: 100%; padding: 12px; background: #3e3e3e; border: 1px solid transparent; color: white; border-radius: 4px; }
    .form-input:focus { border-color: #1db954; outline: none; }
    .btn-submit { background: #1db954; color: white; border: none; padding: 12px 24px; border-radius: 50px; font-weight: 700; cursor: pointer; float: right; }

    @media (max-width: 768px) {
        .playlist-header { flex-direction: column; text-align: center; padding-top: 100px; }
        .songs-table-header, .song-row { grid-template-columns: 40px 1fr 60px; }
        .song-artist-cell, .song-added-cell { display: none; }
        .song-actions-cell { display: none !important; }
    }
</style>

<div class="container-fluid p-0">
    <div class="playlist-header">
        <div class="playlist-cover-box <?php echo $has_custom_cover ? '' : 'default'; ?>">
            <?php if ($has_custom_cover): ?>
                <img src="<?php echo $playlist_cover; ?>" alt="Cover">
            <?php else: ?>
                <i class="fas fa-music"></i>
            <?php endif; ?>
        </div>
        
        <div class="playlist-info-header">
            <div class="playlist-type">
                Playlist
                <?php if(!$playlist['is_public']): ?>
                    <span class="badge-private"><i class="fas fa-lock"></i> Riêng tư</span>
                <?php endif; ?>
            </div>
            
            <h1 class="playlist-name"><?php echo htmlspecialchars($playlist['name']); ?></h1>
            
            <?php if(!empty($playlist['description'])): ?>
                <p class="playlist-desc"><?php echo htmlspecialchars($playlist['description']); ?></p>
            <?php endif; ?>
            
            <div class="playlist-meta">
                <img src="<?php echo $playlist['user_avatar'] ? SITE_URL . $playlist['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($playlist['username']); ?>" 
                     style="width:24px; height:24px; border-radius:50%; vertical-align:middle; margin-right:5px;">
                
                <a href="#" class="owner-link"><?php echo htmlspecialchars($playlist['username']); ?></a>
                
                <?php if(!empty($songs)): ?>
                    <span>• <?php echo count($songs); ?> bài hát</span>
                    <span>• <?php echo $duration_text; ?></span>
                <?php else: ?>
                    <span>• 0 bài hát</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="playlist-content">
        <div class="action-bar">
            <?php if(!empty($songs)): ?>
            <button class="btn-play-large" onclick="playPlaylist()" title="Phát Playlist">
                <i class="fas fa-play"></i>
            </button>
            <?php endif; ?>

            <?php if($is_owner): ?>
                <button class="btn-icon" onclick="openEditModal()" title="Chỉnh sửa">
                    <i class="far fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="deletePlaylist(<?php echo $playlist_id; ?>)" title="Xóa Playlist">
                    <i class="far fa-trash-alt"></i>
                </button>
            <?php else: ?>
                <button class="btn-icon" onclick="toggleSavePlaylist(<?php echo $playlist_id; ?>)" title="Lưu Playlist">
                    <i class="far fa-heart"></i>
                </button>
            <?php endif; ?>
            
            <button class="btn-icon" onclick="sharePlaylist()" title="Chia sẻ">
                <i class="fas fa-share-alt"></i> </button>
        </div>

        <?php if(!empty($songs)): ?>
            <div class="songs-table-header">
                <div>#</div>
                <div>Tiêu đề</div>
                <div class="song-artist-cell">Nghệ sĩ</div>
                <div class="song-added-cell">Ngày thêm</div>
                <div style="text-align: right;"><i class="far fa-clock"></i></div>
            </div>

            <?php foreach($songs as $index => $song): 
                $img = !empty($song['image_url']) ? SITE_URL . $song['image_url'] : SITE_URL . '/assets/images/default-cover.jpg';
            ?>
            <div class="song-row" data-song-id="<?php echo $song['id']; ?>">
                <div>
                    <span class="song-number"><?php echo $index + 1; ?></span>
                    <button class="btn-play-small" onclick="playSong(<?php echo $song['id']; ?>)">
                        <i class="fas fa-play"></i>
                    </button>
                </div>

                <div class="song-title-cell">
                    <img src="<?php echo $img; ?>" alt="">
                    <div class="song-info">
                        <h4>
                            <a href="player.php?id=<?php echo $song['id']; ?>"><?php echo htmlspecialchars($song['title']); ?></a>
                        </h4>
                        <a href="artist.php?id=<?php echo $song['artist_id']; ?>" class="text-muted-link d-md-none">
                            <?php echo htmlspecialchars($song['artist_name']); ?>
                        </a>
                    </div>
                </div>

                <div class="song-artist-cell">
                    <a href="artist.php?id=<?php echo $song['artist_id']; ?>" class="text-muted-link">
                        <?php echo htmlspecialchars($song['artist_name']); ?>
                    </a>
                </div>

                <div class="song-added-cell text-muted-link">
                    <?php 
                        $date = new DateTime($song['added_at']);
                        echo $date->format('d/m/Y');
                    ?>
                </div>

                <div style="text-align:right; display:flex; align-items:center; justify-content:flex-end; gap:15px;">
                    <button onclick="toggleFavorite(<?php echo $song['id']; ?>)" class="text-muted-link" style="background:none; border:none; cursor:pointer;">
                        <i class="far fa-heart"></i>
                    </button>
                    
                    <?php if($is_owner): ?>
                    <button class="btn-remove" onclick="removeSong(<?php echo $playlist_id; ?>, <?php echo $song['id']; ?>)" title="Xóa khỏi playlist">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                    
                    <span style="font-size:14px; color:#b3b3b3; min-width:40px;">
                        <?php echo $song['duration']; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-music"></i>
                <h2>Playlist đang trống</h2>
                <p>Hãy tìm kiếm bài hát và thêm vào playlist này.</p>
                <?php if($is_owner): ?>
                    <a href="search.php" class="btn-find">Tìm bài hát để thêm</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if($is_owner): ?>
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2 style="color:white; margin-top:0;">Sửa thông tin</h2>
        <form method="POST" action="api/playlists.php?action=update">
            <input type="hidden" name="id" value="<?php echo $playlist_id; ?>">
            <div class="form-group">
                <label>Tên</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($playlist['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description" class="form-input" rows="3"><?php echo htmlspecialchars($playlist['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label style="font-weight:400;">
                    <input type="checkbox" name="is_public" value="1" <?php echo $playlist['is_public'] ? 'checked' : ''; ?>> 
                    Công khai Playlist này
                </label>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeEditModal()" style="background:transparent; border:1px solid #777; color:white; padding:10px 20px; border-radius:50px; cursor:pointer; margin-right:10px;">Hủy</button>
                <button type="submit" class="btn-submit">Lưu</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function playPlaylist() {
    const firstRow = document.querySelector('.song-row');
    if(firstRow && window.playSong) {
        window.playSong(firstRow.dataset.songId);
    }
}

function openEditModal() {
    document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function removeSong(playlistId, songId) {
    if(confirm('Xóa bài hát khỏi playlist?')) {
        // Sử dụng FormData để gửi giống như submit form HTML -> PHP dễ nhận
        let formData = new FormData();
        formData.append('playlist_id', playlistId);
        formData.append('song_id', songId);

        fetch('api/playlists.php?action=remove_song', {
            method: 'POST',
            body: formData 
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Reload để cập nhật danh sách
                location.reload();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa bài hát'));
            }
        })
        .catch(err => console.error('Error:', err));
    }
}

function deletePlaylist(id) {
    if(confirm('Bạn chắc chắn muốn xóa playlist này?')) {
        let formData = new FormData();
        formData.append('id', id);

        fetch('api/playlists.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Quay về trang danh sách playlist cá nhân
                window.location.href = 'profile.php'; 
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa playlist'));
            }
        })
        .catch(err => console.error('Error:', err));
    }
}

// Chia sẻ
function sharePlaylist() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => alert('Đã copy link playlist!'));
}
</script>

<?php require_once 'includes/footer.php'; ?>