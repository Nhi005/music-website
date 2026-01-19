<?php
require_once 'includes/config.php';

// Nếu đã đăng nhập thì redirect
if(isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($username) || empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif(strlen($username) < 3) {
        $error = 'Username phải có ít nhất 3 ký tự';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } elseif(strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Kiểm tra email đã tồn tại
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if($stmt->fetch()) {
            $error = 'Email đã được sử dụng';
        } else {
            // Tạo tài khoản mới
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            
            if($stmt->execute([$username, $email, $hashed_password])) {
                $success = 'Đăng ký thành công! Đang chuyển đến trang đăng nhập...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-color) 0%, var(--bg-light) 100%);
            padding: 20px;
        }
        
        .auth-container {
            background: var(--bg-light);
            border-radius: 16px;
            padding: 50px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .auth-header .logo {
            font-size: 42px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: var(--text-muted);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: #2ecc71;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
        }
        
        .divider span {
            background: var(--bg-light);
            padding: 0 20px;
            position: relative;
            color: var(--text-muted);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--text-muted);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .password-strength {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: #e74c3c;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #f39c12;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: #2ecc71;
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-music"></i>
                </div>
                <h1>Đăng ký</h1>
                <p>Tạo tài khoản <?php echo SITE_NAME; ?> miễn phí</p>
            </div>
            
            <?php if($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Tên người dùng</label>
                    <input type="text" id="username" name="username" placeholder="Tên hiển thị" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    Đăng ký
                </button>
            </form>
            
            <div class="divider">
                <span>hoặc</span>
            </div>
            
            <div class="auth-footer">
                Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if(password.length >= 6) strength++;
            if(password.length >= 10) strength++;
            if(/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if(strength <= 2) {
                strengthBar.classList.add('weak');
            } else if(strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
        
        // Confirm password validation
        const confirmPassword = document.getElementById('confirm_password');
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            if(passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp!');
                confirmPassword.focus();
            }
        });
    </script>
</body>
</html>