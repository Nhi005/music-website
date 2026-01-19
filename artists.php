<?php
// BẬT HIỂN THỊ LỖI
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
$page_title = "Tất cả nghệ sĩ";

// --- CẤU HÌNH PHÂN TRANG ---
$limit = 12; // Số nghệ sĩ hiển thị trên 1 trang (bạn có thể sửa số này: 12, 18, 24...)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- XỬ LÝ DATABASE ---
try {
    // 1. Đếm tổng số nghệ sĩ để biết cần bao nhiêu trang
    $count_stmt = $db->query("SELECT COUNT(*) FROM artists");
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 2. Lấy danh sách nghệ sĩ cho trang hiện tại
    // Sử dụng LIMIT và OFFSET
    $stmt = $db->prepare("SELECT * FROM artists ORDER BY name ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $artists = [];
    $total_pages = 0;
}

require_once 'includes/header.php'; 
?>

<style>
    .page-header {
        padding: 40px 0 20px 0;
        margin-bottom: 20px;
    }
    
    .page-title {
        color: white;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    /* GRID LAYOUT */
    .artists-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 24px;
        padding-bottom: 40px; 
    }

    .artist-card {
        background: #181818;
        padding: 20px;
        border-radius: 8px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
    }

    .artist-card:hover {
        background: #282828;
    }

    .artist-img-wrapper {
        width: 140px;
        height: 140px;
        margin-bottom: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        border-radius: 50%;
        overflow: hidden;
    }

    .artist-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        transition: transform 0.3s;
    }

    .artist-card:hover .artist-img {
        transform: scale(1.05);
    }

    .artist-info h3 {
        font-weight: 700;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        margin: 0 0 4px 0;
    }

    .artist-info p {
        color: #b3b3b3;
        font-size: 14px;
        margin: 0;
    }


    /* CSS CHO PHÂN TRANG (PAGINATION) */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        margin-bottom: 120px; /* Cách footer một khoảng lớn */
    }

    .pagination {
        display: flex;
        list-style: none;
        padding: 0;
        gap: 8px;
    }

    .pagination a {
        color: white;
        text-decoration: none;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #121212;
        border: 1px solid #282828;
        font-weight: 600;
        transition: all 0.2s;
    }

    .pagination a:hover {
        background-color: #282828;
        border-color: #fff;
    }

    .pagination a.active {
        background-color: #1db954;
        color: white;
        border-color: #1db954;
    }
    
    .pagination a.disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: default;
    }

    @media (max-width: 768px) {
        .artists-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }
        .artist-img-wrapper {
            width: 100px;
            height: 100px;
        }
    }
</style>

<div class="container" style="padding-top: 80px;">
    <div class="page-header">
        <h1 class="page-title">Tất cả Nghệ sĩ</h1>
        <p style="color: #b3b3b3;">
            Hiện có <?php echo $total_records; ?> nghệ sĩ trên hệ thống
        </p>
    </div>

    <?php if(empty($artists)): ?>
        <div class="text-center" style="color: #b3b3b3; padding: 40px 0;">
            <p>Chưa có nghệ sĩ nào (hoặc trang này không tồn tại).</p>
        </div>
    <?php else: ?>
        <div class="artists-grid">
            <?php foreach($artists as $artist): ?>
                <a href="artist.php?id=<?php echo $artist['id']; ?>" class="artist-card">
                    <div class="artist-img-wrapper">
                        <img src="<?php 
                            echo !empty($artist['avatar']) 
                                ? SITE_URL . $artist['avatar'] 
                                : SITE_URL . '/assets/images/default-artist.jpg';
                        ?>" alt="<?php echo htmlspecialchars($artist['name']); ?>" class="artist-img">
                    </div>
                    
                 
                    <div class="artist-info">
                        <h3><?php echo htmlspecialchars($artist['name']); ?></h3>
                        <p>Nghệ sĩ</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                    <a class="disabled"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>

                <?php
                // Logic hiển thị thông minh: chỉ hiện trang xung quanh trang hiện tại
                $range = 2; // Số trang hiển thị 2 bên
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                        echo '<a href="?page=' . $i . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                    } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                        echo '<a class="disabled">...</a>';
                    }
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <a class="disabled"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>