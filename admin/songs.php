<?php
// 1. Khởi tạo cấu hình và bảo mật
require_once '../includes/config.php';
if (!isAdmin()) { 
    redirect('../login.php'); 
}

$page_title = "Quản lý bài hát";
$db = getDB();

// 2. Xử lý tìm kiếm bài hát
$search = $_GET['search'] ?? '';
$sql = "SELECT s.*, a.name as artist_name, al.title as album_title 
        FROM songs s 
        LEFT JOIN artists a ON s.artist_id = a.id 
        LEFT JOIN albums al ON s.album_id = al.id";

if ($search) {
    $sql .= " WHERE s.title LIKE :search OR a.name LIKE :search";
}
$sql .= " ORDER BY s.created_at DESC";

$stmt = $db->prepare($sql);
if ($search) { 
    $stmt->execute(['search' => "%$search%"]); 
} else { 
    $stmt->execute(); 
}
$songs = $stmt->fetchAll();

// 3. Lấy dữ liệu cho các ô chọn (Dropdown)
$artists = $db->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();
$albums = $db->query("SELECT id, title FROM albums ORDER BY title ASC")->fetchAll();

require_once 'includes/header.php';
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 class="page-title"><i class="fas fa-music"></i> Danh sách bài hát</h2>
    <div class="header-actions" style="display: flex; gap: 15px;">
        <button class="btn-primary" onclick="openAddModal()" style="background: var(--admin-primary); border:none; padding: 10px 20px; border-radius: 8px; color: white; cursor: pointer;">
            <i class="fas fa-plus"></i> Thêm bài hát
        </button>
        <form action="" method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm tên bài hát hoặc nghệ sĩ..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>
</div>

<div class="chart-card">
    <div class="card-body" style="padding: 0;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--admin-sidebar); color: var(--admin-text-muted);">
                    <th style="padding: 15px;">Ảnh</th>
                    <th style="padding: 15px;">Tiêu đề</th>
                    <th style="padding: 15px;">Nghệ sĩ</th>
                    <th style="padding: 15px;">Album</th>
                    <th style="padding: 15px;">Thời lượng</th>
                    <th style="padding: 15px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($songs as $song): ?>
                <tr style="border-bottom: 1px solid var(--admin-border);">
                    <td style="padding: 15px;">
                        <img src="<?php echo SITE_URL . ($song['image_url'] ?: '/assets/images/default-cover.jpg'); ?>" 
                             style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover;">
                    </td>
                    <td style="padding: 15px;"><strong><?php echo htmlspecialchars($song['title']); ?></strong></td>
                    <td style="padding: 15px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($song['artist_name']); ?></td>
                    <td style="padding: 15px; color: var(--admin-primary); font-style: italic;">
                        <?php echo htmlspecialchars($song['album_title'] ?: 'Single (Không album)'); ?>
                    </td>
                    <td style="padding: 15px;"><?php echo $song['duration']; ?></td>
                    <td style="padding: 15px;">
                        <button class="btn-notification" onclick='openEditModal(<?php echo json_encode($song); ?>)' title="Sửa">
                            <i class="fas fa-edit" style="color: #3498db;"></i>
                        </button>
                        <button class="btn-notification" onclick="deleteSong(<?php echo $song['id']; ?>)" title="Xóa">
                            <i class="fas fa-trash" style="color: #e74c3c;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="songModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8);">
    <div style="background: var(--admin-card); margin: 3% auto; padding: 30px; width: 550px; border-radius: 12px; border: 1px solid var(--admin-border);">
        <h2 id="modalTitle" style="margin-bottom: 20px;">Quản lý bài hát</h2>
        <form action="process_song.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="song_id" id="songId">

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px;">Tiêu đề bài hát *</label>
                <input type="text" name="title" id="title" required style="width:100%; padding:10px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white; border-radius:6px;">
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label>Nghệ sĩ *</label>
                    <select name="artist_id" id="artist_id" required style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
                        <?php foreach($artists as $a): ?>
                            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Album (Tùy chọn)</label>
                    <select name="album_id" id="album_id" style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
                        <option value="">-- Không thuộc Album --</option>
                        <?php foreach($albums as $al): ?>
                            <option value="<?php echo $al['id']; ?>"><?php echo htmlspecialchars($al['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label>Thời lượng (MM:SS)</label>
                    <input type="text" name="duration" id="duration" placeholder="04:20" style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label>File nhạc (.mp3) *</label>
                <input type="file" name="audio_file" id="audio_file" accept=".mp3" style="width:100%; color:var(--admin-text-muted);">
                <small id="audioNote" style="color:var(--admin-primary); display:none;">Để trống nếu không muốn thay đổi file</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Ảnh bìa (.jpg, .png)</label>
                <input type="file" name="cover_image" accept="image/*" style="width:100%; color:var(--admin-text-muted);">
            </div>

            <div style="display:flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal()" style="padding:10px 20px; border:none; border-radius:6px; cursor:pointer; background:var(--admin-hover); color:white;">Hủy</button>
                <button type="submit" style="padding:10px 20px; border:none; border-radius:6px; cursor:pointer; background:var(--admin-primary); color:white;">Lưu dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('songModal').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Thêm bài hát mới';
    document.getElementById('formAction').value = 'add';
    document.getElementById('audio_file').required = true;
    document.getElementById('audioNote').style.display = 'none';
    document.getElementById('album_id').value = ""; // Mặc định không chọn album
}

function openEditModal(song) {
    document.getElementById('songModal').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Sửa thông tin bài hát';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('songId').value = song.id;
    document.getElementById('title').value = song.title;
    document.getElementById('artist_id').value = song.artist_id;
    document.getElementById('album_id').value = song.album_id || ""; // Gán album_id nếu có
    document.getElementById('duration').value = song.duration;
    document.getElementById('audio_file').required = false; 
    document.getElementById('audioNote').style.display = 'block';
}

function closeModal() {
    document.getElementById('songModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('songModal');
    if (event.target == modal) { closeModal(); }
}
</script>

<?php require_once 'includes/footer.php'; ?>