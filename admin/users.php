<?php
require_once '../includes/config.php'; //
if(!isAdmin()) { redirect('../login.php'); } //

$page_title = "Quản lý người dùng";
$db = getDB(); //

// Xử lý tìm kiếm người dùng
$search = $_GET['search'] ?? '';
$sql = "SELECT id, username, email, role, avatar, created_at FROM users"; //

if ($search) {
    $sql .= " WHERE username LIKE :search OR email LIKE :search";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
if ($search) { 
    $stmt->execute(['search' => "%$search%"]); 
} else { 
    $stmt->execute(); 
}
$users = $stmt->fetchAll();

require_once 'includes/header.php'; //
?>

<div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 class="page-title"><i class="fas fa-users"></i> Quản lý người dùng</h2>
    <div class="header-actions" style="display: flex; gap: 15px;">
        <button class="btn-filter active" onclick="openAddModal()" style="background: var(--admin-primary); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
            <i class="fas fa-user-plus"></i> Thêm người dùng
        </button>
        <form action="" method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Tìm username hoặc email..." value="<?php echo htmlspecialchars($search); ?>">
        </form>
    </div>
</div>

<div class="chart-card"> <div class="card-body" style="padding: 0;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; background: var(--admin-sidebar); color: var(--admin-text-muted);">
                    <th style="padding: 15px;">Avatar</th>
                    <th style="padding: 15px;">Username</th>
                    <th style="padding: 15px;">Email</th>
                    <th style="padding: 15px;">Vai trò</th>
                    <th style="padding: 15px;">Ngày tham gia</th>
                    <th style="padding: 15px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr style="border-bottom: 1px solid var(--admin-border);">
                    <td style="padding: 15px;">
                        <img src="<?php echo SITE_URL . ($user['avatar'] ?: '/assets/images/default-avatar.png'); ?>" 
                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    </td>
                    <td style="padding: 15px;"><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                    <td style="padding: 15px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td style="padding: 15px;">
                        <span class="badge-admin" style="background: <?php echo $user['role'] == 'admin' ? '#e74c3c' : 'var(--admin-hover)'; ?>;">
                            <?php echo strtoupper($user['role']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px;"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                    <td style="padding: 15px;">
                        <button class="btn-notification" onclick='openEditModal(<?php echo json_encode($user); ?>)' title="Sửa">
                            <i class="fas fa-user-edit" style="color: #3498db;"></i>
                        </button>
                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                        <button class="btn-notification" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Xóa">
                            <i class="fas fa-user-times" style="color: #e74c3c;"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8);">
    <div style="background: var(--admin-card); margin: 5% auto; padding: 30px; width: 500px; border-radius: 12px; border: 1px solid var(--admin-border);">
        <h2 id="modalTitle" style="margin-bottom: 20px;">Quản lý tài khoản</h2>
        <form action="process_user.php" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="user_id" id="userId">

            <div style="margin-bottom: 15px;">
                <label>Username *</label>
                <input type="text" name="username" id="username" required style="width:100%; padding:10px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white; border-radius:6px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label>Email *</label>
                <input type="email" name="email" id="email" required style="width:100%; padding:10px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white; border-radius:6px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label id="passLabel">Mật khẩu *</label>
                <input type="password" name="password" id="password" style="width:100%; padding:10px; background:var(--admin-hover); border:1px solid var(--admin-border); color:white; border-radius:6px;">
                <small id="passNote" style="color: var(--admin-text-muted); display:none;">Để trống nếu không muốn đổi mật khẩu</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Vai trò</label>
                <select name="role" id="role" style="width:100%; padding:10px; background:var(--admin-hover); color:white; border-radius:6px;">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div style="display:flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal()" style="padding:10px 20px; background:var(--admin-hover); color:white; border-radius:6px; border:none; cursor:pointer;">Hủy</button>
                <button type="submit" style="padding:10px 20px; background:var(--admin-primary); color:white; border-radius:6px; border:none; cursor:pointer;">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('userModal').style.display = 'block';
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Thêm người dùng mới';
    document.getElementById('passLabel').innerText = 'Mật khẩu *';
    document.getElementById('password').required = true;
    document.getElementById('passNote').style.display = 'none';
    document.getElementById('username').value = '';
    document.getElementById('email').value = '';
}

function openEditModal(user) {
    document.getElementById('userModal').style.display = 'block';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Sửa người dùng';
    document.getElementById('userId').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('passLabel').innerText = 'Mật khẩu mới';
    document.getElementById('password').required = false;
    document.getElementById('passNote').style.display = 'block';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Thêm hàm deleteUser vào để admin.js có thể gọi
function deleteUser(id) {
    if(confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
        fetch('delete_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert('Lỗi: ' + data.message);
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>