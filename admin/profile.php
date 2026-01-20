<?php
// admin/profile.php

// 1. NHÚNG CONFIG
require_once '../includes/config.php'; 

// 2. KIỂM TRA SESSION (Đảm bảo đúng key session của admin)
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// 3. XỬ LÝ CẬP NHẬT
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB(); // Hàm lấy kết nối từ config của bạn
    $user_id = $_SESSION['user_id']; // Đảm bảo session lưu user_id khi login
    
    $password_new = $_POST['password_new'];
    $password_confirm = $_POST['password_confirm'];

    if (!empty($password_new)) {
        if ($password_new === $password_confirm) {
            // Mã hóa mật khẩu (sửa lại theo cách web bạn đang dùng, ví dụ md5 hoặc password_hash)
            // Ví dụ dùng MD5 (cơ bản): $pass_hash = md5($password_new);
            // Khuyên dùng: $pass_hash = password_hash($password_new, PASSWORD_DEFAULT);
            
            // Giả sử bạn dùng MD5 như các code cũ thường gặp:
            $pass_hash = md5($password_new); 

            try {
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$pass_hash, $user_id]);
                $message = "Đổi mật khẩu thành công!";
            } catch (Exception $e) {
                $error = "Lỗi hệ thống: " . $e->getMessage();
            }
        } else {
            $error = "Mật khẩu xác nhận không khớp!";
        }
    }
}

$page_title = "Thông tin tài khoản";
require_once '../includes/header.php'; // Nhúng header của admin
?>

<div class="container-fluid" style="padding: 20px;">
    
    <div class="card-settings" style="background: #181818; padding: 30px; border-radius: 8px; color: #fff; max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;">Cài đặt tài khoản</h2>
        
        <?php if($message): ?>
            <div style="background: #18c126; color: #000; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="background: #ff4d4d; color: #fff; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #b3b3b3;">Tên đăng nhập (Không thể đổi)</label>
                <input type="text" value="<?php echo $_SESSION['username']; ?>" disabled 
                       style="width: 100%; padding: 10px; background: #282828; border: 1px solid #333; color: #777; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #fff;">Mật khẩu mới</label>
                <input type="password" name="password_new" placeholder="Nhập mật khẩu mới..."
                       style="width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; color: #fff;">Nhập lại mật khẩu</label>
                <input type="password" name="password_confirm" placeholder="Xác nhận mật khẩu..."
                       style="width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px;">
            </div>

            <button type="submit" 
                    style="background: #18c126; color: white; border: none; padding: 10px 30px; border-radius: 20px; font-weight: bold; cursor: pointer; text-transform: uppercase;">
                Lưu thay đổi
            </button>
        </form>
    </div>

</div>

</main> </div> <script>
    // Xử lý click menu Tài khoản
    document.addEventListener('DOMContentLoaded', function() {
        const userBtn = document.getElementById('userDropdownBtn');
        const userMenu = document.getElementById('userDropdownMenu');

        if(userBtn && userMenu) {
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Ngăn sự kiện click lan ra ngoài
                userMenu.classList.toggle('show');
            });

            // Click ra ngoài thì đóng menu
            document.addEventListener('click', function(e) {
                if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.remove('show');
                }
            });
        }
    });
</script>

<style>
    /* Mặc định ẩn */
    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #282828;
        border: 1px solid #333;
        border-radius: 4px;
        min-width: 200px;
        z-index: 1000;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    }

    /* Class show để hiện */
    .dropdown-menu.show {
        display: block;
    }

    .dropdown-menu a {
        display: block;
        padding: 10px 15px;
        color: #fff;
        text-decoration: none;
        border-bottom: 1px solid #333;
    }

    .dropdown-menu a:hover {
        background: #333;
    }
</style>

</body>
</html>