<?php
// BẬT HIỂN THỊ LỖI
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = "Chỉnh sửa hồ sơ";
require_once 'includes/header.php';
$db = getDB();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// 2. XỬ LÝ FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- A. CẬP NHẬT THÔNG TIN CƠ BẢN ---
    $username = trim($_POST['username']);
    $country = trim($_POST['country']);
    $bio = trim($_POST['bio']); // Nếu trong DB bạn có cột bio (tiểu sử)

    // Validate username
    if (empty($username)) {
        $error = "Tên hiển thị không được để trống.";
    } else {
        // Cập nhật thông tin text
        try {
            // Kiểm tra xem có cột bio không, nếu không thì bỏ dòng bio ra nhé
            $sql = "UPDATE users SET username = ?, country = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $country, $user_id]);
            
            // Cập nhật session username luôn để header hiển thị đúng ngay lập tức
            $_SESSION['username'] = $username;
            $message = "Cập nhật thông tin thành công!";
        } catch (Exception $e) {
            $error = "Lỗi cập nhật: " . $e->getMessage();
        }
    }

    // --- B. XỬ LÝ UPLOAD AVATAR ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $filetype = $_FILES['avatar']['type'];
        $filesize = $_FILES['avatar']['size'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Kiểm tra đuôi file & kích thước (max 5MB)
        if (!in_array($ext, $allowed)) {
            $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF).";
        } elseif ($filesize > 5 * 1024 * 1024) {
            $error = "File ảnh quá lớn (Tối đa 5MB).";
        } else {
            // Tạo tên file mới để tránh trùng
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            // Đường dẫn lưu file (Tạo thư mục assets/uploads/avatars nếu chưa có)
            $upload_dir = 'assets/uploads/avatars/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                // Update đường dẫn vào DB (Lưu ý dấu / ở đầu để SITE_URL nối vào cho đúng)
                $db_path = '/' . $destination;
                $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$db_path, $user_id]);
                $message = "Đã cập nhật ảnh đại diện!";
            } else {
                $error = "Lỗi khi tải ảnh lên server.";
            }
        }
    }

    // --- C. XỬ LÝ ĐỔI MẬT KHẨU ---
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!empty($new_pass)) {
        // Lấy mật khẩu cũ trong DB ra check
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();

        if (empty($current_pass)) {
            $error = "Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu mới.";
        } elseif (!password_verify($current_pass, $user_data['password'])) {
            $error = "Mật khẩu hiện tại không đúng.";
        } elseif (strlen($new_pass) < 6) {
            $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "Mật khẩu xác nhận không khớp.";
        } else {
            // Mọi thứ ok, mã hóa và lưu
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_pass, $user_id]);
            $message = "Đổi mật khẩu thành công!";
        }
    }
}

// 3. LẤY LẠI THÔNG TIN USER ĐỂ HIỂN THỊ RA FORM
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Avatar hiển thị
$display_avatar = !empty($user['avatar']) ? SITE_URL . $user['avatar'] : "https://ui-avatars.com/api/?name=" . urlencode($user['username']);
?>

<style>
    .edit-profile-section {
        padding: 60px 0;
        background-color: #121212;
        min-height: 80vh;
    }
    
    .edit-container {
        max-width: 700px;
        margin: 0 auto;
        background: #181818;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }

    .edit-header {
        border-bottom: 1px solid #333;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .edit-header h2 { margin: 0; color: white; }

    /* Avatar Upload */
    .avatar-upload {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .current-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #333;
    }

    .upload-btn-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }

    .btn-upload {
        border: 1px solid #727272ff;
        color: white;
        background-color: transparent;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-upload:hover { border-color: white; }

    .upload-btn-wrapper input[type=file] {
        font-size: 100px;
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
    }

    /* Form Fields */
    .form-section { margin-bottom: 30px; }
    .form-section h3 { font-size: 18px; color: white; margin-bottom: 15px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; color: #b3b3b3ff; margin-bottom: 8px; font-size: 14px; font-weight: 700; }
    
    .form-control {
        width: 100%;
        background: #333;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 12px;
        color: white;
        font-size: 16px;
        transition: 0.3s;
    }

    .form-control:focus {
        background: #404040;
        border-color: #1db954;
        outline: none;
    }

    .form-control[readonly] {
        background: #222;
        color: #777;
        cursor: not-allowed;
    }

    /* Alerts */
    .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .alert-success { background: rgba(29, 185, 84, 0.2); color: #1db954; border: 1px solid #1db954; }
    .alert-danger { background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid #f44336; }

    /* Buttons */
    .btn-save {
        background: #1db954;
        color: white;
        border: none;
        padding: 14px 32px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: 0.2s;
        float: right;
    }

    .btn-save:hover { transform: scale(1.04); background: #1ed760; }

    .btn-cancel {
        color: #b3b3b3;
        text-decoration: none;
        font-weight: 700;
        margin-right: 20px;
        float: right;
        padding: 14px 0;
    }
    .btn-cancel:hover { color: white; }

    .clearfix::after { content: ""; clear: both; display: table; }
</style>

<div class="edit-profile-section">
    <div class="container">
        <div class="edit-container">
            <div class="edit-header">
                <h2>Chỉnh sửa hồ sơ</h2>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <div class="avatar-upload">
                    <img src="<?php echo $display_avatar; ?>" class="current-avatar" id="avatarPreview">
                    <div>
                        <div class="upload-btn-wrapper">
                            <button class="btn-upload">Chọn ảnh mới</button>
                            <input type="file" name="avatar" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <p style="font-size:12px; color:#b3b3b3; margin-top:5px;">JPG, PNG hoặc GIF. Tối đa 5MB.</p>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Thông tin cá nhân</h3>
                    <div class="form-group">
                        <label>Email (Không thể thay đổi)</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tên hiển thị</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Quốc gia</label>
                        <select name="country" class="form-control">
                            <option value="">Chọn quốc gia</option>
                            <option value="Vietnam" <?php echo ($user['country'] == 'Vietnam') ? 'selected' : ''; ?>>Vietnam</option>
                            <option value="United States" <?php echo ($user['country'] == 'United States') ? 'selected' : ''; ?>>United States</option>
                            <option value="Japan" <?php echo ($user['country'] == 'Japan') ? 'selected' : ''; ?>>Japan</option>
                            <option value="Korea" <?php echo ($user['country'] == 'Korea') ? 'selected' : ''; ?>>Korea</option>
                            <option value="Other" <?php echo ($user['country'] == 'Other') ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #333; margin: 30px 0;">

                <div class="form-section">
                    <h3>Đổi mật khẩu</h3>
                    <p style="color:#b3b3b3; font-size:13px; margin-bottom:15px;">Chỉ điền vào đây nếu bạn muốn thay đổi mật khẩu.</p>
                    
                    <div class="form-group">
                        <label>Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Nhập mật khẩu cũ để xác minh">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Mật khẩu mới (tối thiểu 6 ký tự)">
                    </div>
                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu mới">
                    </div>
                </div>

                <div class="clearfix">
                    <button type="submit" class="btn-save">Lưu hồ sơ</button>
                    <a href="profile.php" class="btn-cancel">Hủy</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Xem trước ảnh khi chọn file
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>