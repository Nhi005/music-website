<?php
require_once '../includes/config.php'; //
if(!isAdmin()) { redirect('../login.php'); } //

$page_title = "Quản lý nghệ sĩ";
$db = getDB(); //

// Xử lý tìm kiếm nghệ sĩ
$search = $_GET['search'] ?? '';
$sql = "SELECT a.*, 
               COUNT(s.id) as total_songs, 
               IFNULL(SUM(s.play_count), 0) as total_plays
        FROM artists a
        LEFT JOIN songs s ON a.id = s.artist_id"; // Join để lấy thống kê

if ($search) {
    $sql .= " WHERE a.name LIKE :search OR a.country LIKE :search";
}
$sql .= " GROUP BY a.id ORDER BY a.name ASC";

$stmt = $db->prepare($sql);
if ($search) { $stmt->execute(['search' => "%$search%"]); } 
else { $stmt->execute(); }
$artists = $stmt->fetchAll();

require_once 'includes/header.php'; // Đảm bảo header này chứa admin-wrapper
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 class="page-title">Danh sách nghệ sĩ</h2>
    <div class="header-actions" style="display: flex; gap: 15px;">
        <button class="btn-filter active" onclick="openModal('add')" style="background: var(--admin-primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
            <i class="fas fa-plus"></i> Thêm nghệ sĩ mới
        </button>
        <form action="" method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm tên hoặc quốc gia..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>
</div>

<div class="chart-card"> <div class="card-body" style="padding: 0;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--admin-sidebar); color: var(--admin-text-muted);">
                    <th style="padding: 15px;">Ảnh đại diện</th>
                    <th style="padding: 15px;">Tên nghệ sĩ</th>
                    <th style="padding: 15px;">Quốc gia</th>
                    <th style="padding: 15px;">Số bài hát</th>
                    <th style="padding: 15px;">Tổng lượt nghe</th>
                    <th style="padding: 15px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($artists as $artist): ?>
                <tr class="top-item" style="border-bottom: 1px solid var(--admin-border);">
                    <td style="padding: 15px;">
                        <img src="<?php echo SITE_URL . ($artist['avatar'] ?: '/assets/images/default-artist.jpg'); ?>" 
                             style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                    </td>
                    <td style="padding: 15px;">
                        <strong><?php echo htmlspecialchars($artist['name']); ?></strong>
                        <p style="font-size: 12px; color: var(--admin-text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($artist['bio']); ?>
                        </p>
                    </td>
                    <td style="padding: 15px;"><?php echo htmlspecialchars($artist['country']); ?></td>
                    <td style="padding: 15px; text-align: center;"><?php echo $artist['total_songs']; ?></td>
                    <td style="padding: 15px;"><?php echo number_format($artist['total_plays']); ?></td>
                    <td style="padding: 15px;">
                        <button onclick='openEditModal(<?php echo json_encode($artist); ?>)' class="btn-notification" title="Sửa">
                            <i class="fas fa-edit" style="color: #3498db;"></i>
                        </button>
                        <button onclick="deleteArtist(<?php echo $artist['id']; ?>)" class="btn-notification" title="Xóa">
                            <i class="fas fa-trash" style="color: #e74c3c;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="artistModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8);">
    <div style="background: var(--admin-card); margin: 5% auto; padding: 30px; width: 500px; border-radius: 12px; border: 1px solid var(--admin-border);">
        <h2 id="modalTitle">Thêm nghệ sĩ</h2>
        <form action="process_artist.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="artist_id" id="artistId">

            <div style="margin-bottom: 15px;">
                <label>Tên nghệ sĩ *</label>
                <input type="text" name="name" id="name" required style="width:100%; padding:8px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>Quốc gia</label>
                <input type="text" name="country" id="country" style="width:100%; padding:8px; background:var(--admin-hover); color:white;">
            </div>

            <div style="margin-bottom: 15px;">
                <label>Tiểu sử (Bio)</label>
                <textarea name="bio" id="bio" rows="3" style="width:100%; padding:8px; background:var(--admin-hover); color:white;"></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Ảnh đại diện (Upload mới)</label>
                <input type="file" name="avatar" accept="image/*" style="width:100%;">
            </div>

            <div style="display:flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal()" class="btn-filter">Hủy</button>
                <button type="submit" class="btn-filter active">Lưu nghệ sĩ</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode) {
    document.getElementById('artistModal').style.display = 'block';
    document.getElementById('formAction').value = mode;
    document.getElementById('modalTitle').innerText = mode === 'add' ? 'Thêm nghệ sĩ mới' : 'Sửa thông tin nghệ sĩ';
}

function openEditModal(artist) {
    openModal('edit');
    document.getElementById('artistId').value = artist.id;
    document.getElementById('name').value = artist.name;
    document.getElementById('country').value = artist.country;
    document.getElementById('bio').value = artist.bio;
}

function closeModal() {
    document.getElementById('artistModal').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>