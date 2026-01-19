<?php
// albums.php - TRANG HIỂN THỊ TẤT CẢ ALBUM
$page_title = "Kho Album";
require_once 'includes/header.php'; // Gọi Header
$db = getDB();

// 1. LẤY TẤT CẢ ALBUM (Mới nhất lên đầu)
try {
    $sql = "SELECT al.*, a.name as artist_name 
            FROM albums al 
            LEFT JOIN artists a ON al.artist_id = a.id 
            ORDER BY al.created_at DESC"; 
    $stmt = $db->query($sql);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $albums = [];
}
?>

<style>
    .albums-page-container {
        padding: 40px 0 60px;
        min-height: 80vh;
    }
    
    .section-header h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* GRID LAYOUT (Giống trang chủ) */
    .albums-grid-full {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 24px;
    }

    /* CARD STYLE */
    .album-card-full {
        background: #181818;
        padding: 16px;
        border-radius: 8px;
        text-decoration: none;
        display: block;
        transition: all 0.3s ease;
    }

    .album-card-full:hover {
        background: #282828;
        transform: translateY(-5px);
    }

    .album-img-box-full {
        position: relative;
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    .album-img-box-full img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.3s;
    }

    /* Hiệu ứng zoom ảnh */
    .album-card-full:hover .album-img-box-full img {
        transform: scale(1.05);
    }

    /* Nút Play overlay */
    .play-overlay-full {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: 0.3s;
        transform: translateY(10px);
    }

    .album-card-full:hover .play-overlay-full {
        opacity: 1;
        transform: translateY(0);
    }

    .btn-play-full {
        width: 50px; height: 50px;
        background: #1db954;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    }

    .album-title-full {
        font-size: 16px; font-weight: 700; color: white; margin: 0 0 4px 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    
    .album-artist-full {
        font-size: 14px; color: #b3b3b3; margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
</style>

<div class="container">
    <div class="albums-page-container">
        <div class="section-header">
            <h2><i class="fas fa-compact-disc"></i> Tất cả Album</h2>
        </div>

        <?php if (!empty($albums)): ?>
            <div class="albums-grid-full">
                <?php foreach ($albums as $album): 
                    $img = !empty($album['cover_url']) ? SITE_URL . $album['cover_url'] : SITE_URL . '/assets/images/default-cover.jpg';
                ?>
                    <a href="album.php?id=<?php echo $album['id']; ?>" class="album-card-full">
                        <div class="album-img-box-full">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($album['title']); ?>">
                            <div class="play-overlay-full">
                                <span class="btn-play-full"><i class="fas fa-play"></i></span>
                            </div>
                        </div>
                        <div class="album-info-full">
                            <h3 class="album-title-full"><?php echo htmlspecialchars($album['title']); ?></h3>
                            <p class="album-artist-full"><?php echo htmlspecialchars($album['artist_name']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #777; margin-top: 50px;">
                <i class="fas fa-compact-disc" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                <h3>Hệ thống chưa có album nào</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>