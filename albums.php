<?php
require_once '../includes/config.php'; //
if(!isAdmin()) { redirect('../login.php'); } //

$page_title = "Quản lý Album";
$db = getDB(); //

// Xử lý tìm kiếm
$search = $_GET['search'] ?? '';
$sql = "SELECT al.*, a.name as artist_name, COUNT(s.id) as total_songs 
        FROM albums al 
        LEFT JOIN artists a ON al.artist_id = a.id 
        LEFT JOIN songs s ON al.id = s.album_id"; //

if ($search) {
    $sql .= " WHERE al.title LIKE :search OR a.name LIKE :search";
}
$sql .= " GROUP BY al.id ORDER BY al.created_at DESC";

$stmt = $db->prepare($sql);
if ($search) { $stmt->execute(['search' => "%$search%"]); } 
else { $stmt->execute(); }
$albums = $stmt->fetchAll();

// Lấy danh sách nghệ sĩ cho Dropdown trong Modal
$artists = $db->query("SELECT id, name FROM artists ORDER BY name ASC")->fetchAll();

require_once 'includes/header.php'; //
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 class="page-title"><i class="fas fa-compact-disc"></i> Danh sách Album</h2>
    <div class="header-actions" style="display: flex; gap: 15px;">
        <button class="btn-filter active" onclick="openAddModal()" style="background: var(--admin-primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
            <i class="fas fa-plus"></i> Thêm album mới
        </button>
        <form action="" method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm tên album hoặc nghệ sĩ..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>
</div>

<div class="chart-card"> <div class="card-body" style="padding: 0;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--admin-sidebar); color: var(--admin-text-muted);">
                    <th style="padding: 15px;">Bìa Album</th>
                    <th style="padding: 15px;">Tên Album</th>
                    <th style="padding: 15px;">Nghệ sĩ</th>
                    <th style="padding: 15px;">Ngày phát hành</th>
                    <th style="padding: 15px;">Số bài hát</th>
                    <th style="padding: 15px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($albums as $album): ?>
                <tr style="border-bottom: 1px solid var(--admin-border);">
                    <td style="padding: 15px;">
                        <img src="<?php echo SITE_URL . ($album['cover_url'] ?: '/assets/images/default-album.jpg'); ?>" 
                             style="width: 50px; height: 50px; border-radius: 4px; object-fit: cover;">
                    </td>
                    <td style="padding: 15px;"><strong><?php echo htmlspecialchars($album['title']); ?></strong></td>
                    <td style="padding: 15px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($album['artist_name']); ?></td>
                    <td style="padding: 15px;"><?php echo $album['release_date']; ?></td>
                    <td style="padding: 15px;"><?php echo $album['total_songs']; ?> bài</td>
                    <td style="padding: 15px;">
                        <button class="btn-notification" onclick='openEditModal(<?php echo json_encode($album); ?>)' title="Sửa">
                            <i class="fas fa-edit" style="color: #3498db;"></i>
                        </button>
                        <button class="btn-notification" onclick="deleteAlbum(<?php echo $album['id']; ?>)" title="Xóa"> <i class="fas fa-trash" style="color: #e74c3c;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="albumModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8);">
    <div style="background: var(--admin-card); margin: 5% auto; padding: 30px; width: 500px; border-radius: 12px; border: 1px solid var(--admin-border);">
        <h2 id="modalTitle" style="margin-bottom: 20px;">Quản lý Album</h2>
        <form action="process_album.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="album_id" id="albumId">

            <div style="margin-bottom: 15px;">
                <label>Tên Album *</label>
                <input type="text" name="title" id="title" required style="width:100%; padding:10px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white; border-radius:6px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label>Nghệ sĩ *</label>
                <select name="artist_id" id="artist_id" required style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
                    <?php foreach($artists as $art): ?>
                        <option value="<?php echo $art['id']; ?>"><?php echo htmlspecialchars($art['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Ngày phát hành</label>
                <input type="date" name="release_date" id="release_date" style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label>Ảnh bìa Album</label>
                <input type="file" name="cover_image" accept="image/*" style="width:100%; color: var(--admin-text-muted);">
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
    document.getElementById('albumModal').style.display = 'block';
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Thêm album mới';
    document.getElementById('title').value = '';
}

function openEditModal(album) {
    document.getElementById('albumModal').style.display = 'block';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Sửa album';
    document.getElementById('albumId').value = album.id;
    document.getElementById('title').value = album.title;
    document.getElementById('artist_id').value = album.artist_id;
    document.getElementById('release_date').value = album.release_date;
}

function closeModal() {
    document.getElementById('albumModal').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?> //