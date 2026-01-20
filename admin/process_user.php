<?php
require_once '../includes/config.php'; //
$db = getDB(); //

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    try {
        if ($action == 'add') {
            // Kiểm tra email/username đã tồn tại chưa
            $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check->execute([$email, $username]);
            if ($check->rowCount() > 0) {
                die("Lỗi: Username hoặc Email đã tồn tại.");
            }

            // Mã hóa mật khẩu trước khi lưu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $db->prepare($sql)->execute([$username, $email, $hashed_password, $role]);
        } 
        else {
            // Cập nhật thông tin cơ bản
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $db->prepare($sql)->execute([$username, $email, $role, $user_id]);

            // Nếu người dùng nhập mật khẩu mới thì cập nhật mật khẩu
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user_id]);
            }
        }
        header("Location: users.php?status=success");
    } catch (Exception $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
}