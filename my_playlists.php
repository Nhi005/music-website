<?php
// BẬT HIỂN THỊ LỖI
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Playlist của tôi";
require_once 'includes/header.php';
$db = getDB();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// 2. XỬ LÝ TẠO PLAYLIST MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_playlist'])) {
    $name = trim($_POST['playlist_name']);
    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO playlists (user_id, name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $name]);
            // Refresh trang để hiện playlist mới
            echo "<script>window.location.href='my_playlists.php';</script>";
            exit;
        } catch (Exception $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// 3. LẤY DANH SÁCH PLAYLIST + SỐ BÀI HÁT
try {
    // Left Join để đếm số bài hát trong mỗi playlist
    $sql = "SELECT p.*, COUNT(ps.song_id) as song_count 
            FROM playlists p 
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id 
            WHERE p.user_id = ? 
            GROUP BY p.id 
            ORDER BY p.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $playlists = [];
}

$total_playlists = count($playlists);
?>

<style>
    /* COPY STYLE TỪ PROFILE.PHP VÀ TÙY CHỈNH LẠI */
    :root {
        --library-bg-start: rgb(90, 98, 90); /* Màu tím đậm */
        --library-bg-end: #121212;
    }

    .library-header {
        background: linear-gradient(180deg, var(--library-bg-start) 0%, var(--library-bg-end) 100%);
        padding: 80px 30px 30px 30px;
        display: flex;
        align-items: flex-end;
        gap: 30px;
        min-height: 340px;
    }

    /* Icon lớn đại diện cho Thư viện (thay cho Avatar) */
    .library-icon-box {
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
        border-radius: 4px; /* Bo góc ít hơn avatar */
    }

    .library-info {
        color: white;
        width: 100%;
    }

    .library-type {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .library-name {
        font-size: clamp(40px, 6vw, 90px);
        font-weight: 900;
        margin: 0;
        line-height: 1;
        letter-spacing: -2px;
    }

    .library-stats {
        margin-top: 20px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
        color: rgba(255,255,255,0.7);
    }
    
    .library-stats b { color: #fff; }

    /* Nút Tạo Playlist (Style giống nút Edit Profile) */
    .btn-create-playlist {
        display: inline-block;
        margin-top: 20px;
        background-color: white;
        color: black;
        border: none;
        text-decoration: none;
        padding: 12px 32px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: transform 0.1s;
    }

    .btn-create-playlist:hover {
        transform: scale(1.04);
        background-color: #f0f0f0;
    }

    .library-content {
        background-color: #121212;
        padding: 30px;
        min-height: 50vh;
    }

    /* Grid và Card (Dùng lại của Profile) */
    .playlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 24px;
    }

    .playlist-card {
        background: #181818;
        padding: 16px;
        border-radius: 8px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        color: white;
        display: block;
        position: relative;
    }

    .playlist-card:hover {
        background: #282828;
    }

    .playlist-img-box {
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 4px;
        margin-bottom: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        background: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #777;
        overflow: hidden;
    }
    
    .playlist-img-box img {
        width: 100%; height: 100%; object-fit: cover;
    }

    .playlist-title {
        font-weight: 700;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 5px;
    }

    .playlist-desc {
        color: #b3b3b3;
        font-size: 14px;
    }

    /* Modal Tạo Playlist */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
    .modal.active { display: flex; }
    .modal-content { background-color: #282828; padding: 24px; border-radius: 8px; width: 400px; color: white; }
    .form-input { width: 100%; padding: 12px; margin: 15px 0; border-radius: 4px; border: 1px solid #444; background: #3e3e3e; color: white; border: none; }
    .form-input:focus { outline: none; background: #4e4e4e; }
    
    /* Responsive */
    @media (max-width: 768px) {
        .library-header { flex-direction: column; align-items: center; text-align: center; padding-top: 100px; }
        .library-icon-box { width: 180px; height: 180px; }
        .library-stats { justify-content: center; }
    }
</style>

<div class="container-fluid p-0">
    <div class="library-header">
        
        
        <div class="library-info">
            <div class="library-type">Thư viện</div>
            <h1 class="library-name">Playlist của tôi</h1>
            
            <div class="library-stats">
                <b><?php echo $_SESSION['username']; ?></b>
                <span>• <?php echo $total_playlists; ?> playlist</span>
            </div>

            <button onclick="openModal()" class="btn-create-playlist">
                Tạo Playlist mới
            </button>
        </div>
    </div>

    <div class="library-content">
        <?php if(empty($playlists)): ?>
            <div style="text-align: center; padding: 50px 0; color: #b3b3b3;">
                <i class="far fa-folder-open" style="font-size: 48px; margin-bottom: 20px;"></i>
                <p>Bạn chưa có playlist nào.</p>
            </div>
        <?php else: ?>
            <div class="playlist-grid">
                <?php foreach($playlists as $pl): 
                    // Logic lấy ảnh cover (Nếu có cột image thì dùng, không thì dùng default)
                    $cover_img = !empty($pl['image']) ? SITE_URL . $pl['image'] : null;
                ?>
                <a href="playlist.php?id=<?php echo $pl['id']; ?>" class="playlist-card">
                    <div class="playlist-img-box">
                        <?php if($cover_img): ?>
                            <img src="<?php echo $cover_img; ?>" alt="Cover">
                        <?php else: ?>
                            <i class="fas fa-music"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="playlist-title"><?php echo htmlspecialchars($pl['name']); ?></div>
                    <div class="playlist-desc"><?php echo $pl['song_count']; ?> bài hát</div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="createModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0; font-size:24px;">Tạo Playlist</h2>
        <form method="POST">
            <input type="text" name="playlist_name" class="form-input" placeholder="Nhập tên playlist..." required autocomplete="off" autofocus>
            <div style="text-align: right; margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-create-playlist" style="background: transparent; color: white; margin: 0; border: 1px solid #555;" onclick="closeModal()">Hủy</button>
                <button type="submit" name="create_playlist" class="btn-create-playlist" style="background: #1db954; color: white; margin: 0; border: none;">Tạo</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('createModal').classList.add('active');
        // Focus vào ô input
        setTimeout(() => {
            document.querySelector('input[name="playlist_name"]').focus();
        }, 100);
    }

    function closeModal() {
        document.getElementById('createModal').classList.remove('active');
    }

    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        var modal = document.getElementById('createModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>