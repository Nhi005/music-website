<?php
require_once '../includes/config.php';

// Kiểm tra quyền admin
if(!isAdmin()) {
    redirect('../login.php');
}

$page_title = "Quản lý bài hát";
$db = getDB();

// Xử lý xóa bài hát
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $song_id = (int)$_GET['delete'];
    
    try {
        // Lấy thông tin file để xóa
        $song = $db->prepare("SELECT file_url, image_url FROM songs WHERE id = ?");
        $song->execute([$song_id]);
        $song_data = $song->fetch(PDO::FETCH_ASSOC);
        
        if($song_data) {
            // Xóa file MP3
            if($song_data['file_url'] && file_exists('../' . $song_data['file_url'])) {
                unlink('../' . $song_data['file_url']);
            }
            
            // Xóa ảnh cover
            if($song_data['image_url'] && file_exists('../' . $song_data['image_url'])) {
                unlink('../' . $song_data['image_url']);
            }
            
            // Xóa bài hát khỏi database
            $stmt = $db->prepare("DELETE FROM songs WHERE id = ?");
            $stmt->execute([$song_id]);
            
            $_SESSION['success'] = "Đã xóa bài hát thành công!";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    
    redirect('songs.php');
}

// Lấy danh sách bài hát với phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

if($search) {
    $where = "WHERE s.title LIKE ? OR a.name LIKE ?";
    $params = ['%' . $search . '%', '%' . $search . '%'];
}

// Đếm tổng số bài hát
$count_sql = "SELECT COUNT(*) as total FROM songs s LEFT JOIN artists a ON s.artist_id = a.id $where";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_songs = $count_stmt->fetch()['total'];
$total_pages = ceil($total_songs / $per_page);

// Lấy danh sách bài hát
$sql = "SELECT s.*, a.name as artist_name, al.title as album_title
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        LEFT JOIN albums al ON s.album_id = al.id
        $where
        ORDER BY s.created_at DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách nghệ sĩ và album cho form thêm/sửa
$artists = $db->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$albums = $db->query("SELECT id, title FROM albums ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<style>
.songs-table-container {
    background: var(--admin-card);
    border-radius: 12px;
    border: 1px solid var(--admin-border);
    overflow: hidden;
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--admin-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.table-header h2 {
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
}

.search-box input {
    padding: 10px 15px 10px 40px;
    background: var(--admin-hover);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    color: var(--admin-text);
    width: 250px;
    outline: none;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: var(--admin-primary);
    background: var(--admin-bg);
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--admin-text-muted);
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-primary {
    background: var(--admin-primary);
    color: white;
}

.btn-primary:hover {
    background: #1aa34a;
    transform: translateY(-2px);
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.songs-table {
    width: 100%;
    border-collapse: collapse;
}

.songs-table th,
.songs-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
}

.songs-table th {
    background: var(--admin-hover);
    color: var(--admin-text-muted);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.songs-table tbody tr {
    transition: background 0.3s;
}

.songs-table tbody tr:hover {
    background: var(--admin-hover);
}

.song-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.song-cover {
    width: 50px;
    height: 50px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
}

.song-details h4 {
    margin: 0 0 4px 0;
    font-size: 15px;
    color: var(--admin-text);
}

.song-details p {
    margin: 0;
    font-size: 13px;
    color: var(--admin-text-muted);
}

.badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background: rgba(29, 185, 84, 0.2);
    color: var(--admin-primary);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.pagination {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    color: var(--admin-text-muted);
    font-size: 14px;
}

.pagination-links {
    display: flex;
    gap: 8px;
}

.pagination-links a,
.pagination-links span {
    padding: 8px 12px;
    background: var(--admin-hover);
    border-radius: 6px;
    color: var(--admin-text);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.pagination-links a:hover {
    background: var(--admin-primary);
    color: white;
}

.pagination-links span.active {
    background: var(--admin-primary);
    color: white;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: rgba(29, 185, 84, 0.1);
    border: 1px solid rgba(29, 185, 84, 0.3);
    color: var(--admin-primary);
}

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    color: #e74c3c;
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-state i {
    font-size: 64px;
    color: var(--admin-text-muted);
    margin-bottom: 20px;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: var(--admin-text);
}

.empty-state p {
    color: var(--admin-text-muted);
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .songs-table {
        font-size: 13px;
    }
    
    .song-cover {
        width: 40px;
        height: 40px;
    }
    
    .songs-table th:nth-child(4),
    .songs-table td:nth-child(4),
    .songs-table th:nth-child(5),
    .songs-table td:nth-child(5) {
        display: none;
    }
    
    .pagination {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<!-- Alerts -->
<?php if(isset($_SESSION['success'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
</div>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
</div>
<?php endif; ?>

<!-- Table Container -->
<div class="songs-table-container">
    <div class="table-header">
        <h2>
            <i class="fas fa-music"></i>
            Quản lý bài hát (<?php echo number_format($total_songs); ?>)
        </h2>
        
        <div class="header-actions">
            <form action="" method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Tìm kiếm bài hát, nghệ sĩ..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>
            
            <a href="song-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Thêm bài hát
            </a>
        </div>
    </div>

    <?php if(empty($songs)): ?>
    <div class="empty-state">
        <i class="fas fa-music"></i>
        <h3>Chưa có bài hát nào</h3>
        <p>Bắt đầu thêm bài hát đầu tiên của bạn</p>
        <a href="song-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Thêm bài hát mới
        </a>
    </div>
    <?php else: ?>
    
    <table class="songs-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Bài hát</th>
                <th>Nghệ sĩ</th>
                <th>Album</th>
                <th>Thời lượng</th>
                <th>Lượt nghe</th>
                <th>Ngày thêm</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($songs as $song): ?>
            <tr>
                <td>#<?php echo $song['id']; ?></td>
                
                <td>
                    <div class="song-info">
                        <img src="<?php echo BASE_URL . ($song['image_url'] ?: '/assets/images/default-cover.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($song['title']); ?>" 
                             class="song-cover">
                        <div class="song-details">
                            <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                            <p>
                                <i class="fas fa-file-audio"></i>
                                <?php echo basename($song['file_url']); ?>
                            </p>
                        </div>
                    </div>
                </td>
                
                <td><?php echo htmlspecialchars($song['artist_name'] ?? 'N/A'); ?></td>
                
                <td><?php echo htmlspecialchars($song['album_title'] ?? '-'); ?></td>
                
                <td>
                    <i class="fas fa-clock"></i>
                    <?php echo $song['duration']; ?>
                </td>
                
                <td>
                    <span class="badge badge-success">
                        <i class="fas fa-play-circle"></i>
                        <?php echo number_format($song['play_count']); ?>
                    </span>
                </td>
                
                <td><?php echo date('d/m/Y', strtotime($song['created_at'])); ?></td>
                
                <td>
                    <div class="action-buttons">
                        <a href="song-edit.php?id=<?php echo $song['id']; ?>" 
                           class="btn btn-warning btn-sm" 
                           title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <a href="?delete=<?php echo $song['id']; ?>" 
                           class="btn btn-danger btn-sm" 
                           title="Xóa"
                           onclick="return confirm('Bạn có chắc muốn xóa bài hát này?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <div class="pagination-info">
            Hiển thị <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_songs); ?> 
            trong tổng số <?php echo $total_songs; ?> bài hát
        </div>
        
        <div class="pagination-links">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>