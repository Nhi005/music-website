<?php
// admin/settings.php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$page_title = "Cài đặt hệ thống";
$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error   = '';

// ====== XỬ LÝ LƯU CÀI ĐẶT ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $site_name      = trim($_POST['site_name'] ?? '');
    $site_tagline   = trim($_POST['site_tagline'] ?? '');
    $items_per_page = (int)($_POST['items_per_page'] ?? 20);
    if ($items_per_page <= 0) $items_per_page = 20;

    $primary_color   = trim($_POST['primary_color'] ?? '#1db954');
    $default_volume  = (int)($_POST['default_volume'] ?? 70);
    if ($default_volume < 0)   $default_volume = 0;
    if ($default_volume > 100) $default_volume = 100;

    $allow_registration = isset($_POST['allow_registration']) ? '1' : '0';
    $maintenance_mode   = isset($_POST['maintenance_mode'])   ? '1' : '0';
    $autoplay_next      = isset($_POST['autoplay_next'])      ? '1' : '0';
    $enable_history     = isset($_POST['enable_history'])     ? '1' : '0';

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        $pairs = [
            ['site_name',            $site_name],
            ['site_tagline',         $site_tagline],
            ['items_per_page',       $items_per_page],
            ['primary_color',        $primary_color],
            ['default_volume',       $default_volume],
            ['allow_registration',   $allow_registration],
            ['maintenance_mode',     $maintenance_mode],
            ['autoplay_next',        $autoplay_next],
            ['enable_history',       $enable_history],
        ];

        foreach ($pairs as $p) {
            $stmt->execute($p);
        }

        $db->commit();
        $success = "Đã lưu cài đặt thành công.";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Lỗi khi lưu cài đặt: " . $e->getMessage();
    }
}

// ====== LOAD CÀI ĐẶT HIỆN TẠI ======
$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Nếu bảng chưa tồn tại hoặc lỗi gì đó
    $error = $error ?: "Chưa tạo bảng settings trong database.";
}

// Helper lấy setting
function get_setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

require_once 'includes/header.php';
?>

<style>
/* ====== SETTINGS PAGE STYLE (chỉ cho trang này) ====== */
.settings-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.settings-header h2 {
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-header h2 i {
    color: #1db954;
}

.settings-description {
    color: #aaa;
    font-size: 14px;
}

.settings-grid {
    display: grid;
    grid-template-columns: 2fr 2fr;
    gap: 24px;
}

.settings-card {
    background: #181818;
    border-radius: 14px;
    padding: 20px 22px;
    border: 1px solid #282828;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
}

.settings-card h3 {
    font-size: 18px;
    margin-bottom: 4px;
}

.settings-card p.card-subtitle {
    font-size: 13px;
    color: #a0a0a0;
    margin-bottom: 18px;
}

.form-group {
    margin-bottom: 14px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}

.form-group small {
    display: block;
    font-size: 12px;
    color: #888;
    margin-top: 3px;
}

.form-control,
.form-select {
    width: 100%;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #333;
    background: #121212;
    color: #fff;
    font-size: 14px;
    outline: none;
}

.form-control:focus,
.form-select:focus {
    border-color: #1db954;
    box-shadow: 0 0 0 1px rgba(29,185,84,0.4);
}

.form-control[type="number"] {
    max-width: 120px;
}

.form-control[type="color"] {
    padding: 0;
    height: 36px;
    max-width: 80px;
    border-radius: 18px;
    overflow: hidden;
}

.switch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.switch-label {
    font-size: 14px;
}

.switch-description {
    font-size: 12px;
    color: #888;
}

/* Custom toggle */
.switch-toggle {
    position: relative;
    width: 44px;
    height: 22px;
}

.switch-toggle input {
    display: none;
}

.switch-slider {
    position: absolute;
    inset: 0;
    background: #333;
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.25s;
}

.switch-slider::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.25s;
}

.switch-toggle input:checked + .switch-slider {
    background: #1db954;
}

.switch-toggle input:checked + .switch-slider::before {
    transform: translateX(20px);
}

.settings-actions {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
}

.alert-success, .alert-error {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 10px;
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    border: 1px solid #2ecc71;
    color: #2ecc71;
}

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid #e74c3c;
    color: #e74c3c;
}

@media (max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="settings-wrapper">
    <div class="settings-header">
        <div>
            <h2><i class="fas fa-cog"></i> Cài đặt hệ thống</h2>
            <p class="settings-description">
                Quản lý thông tin website, hành vi trình phát nhạc và cấu hình người dùng.
            </p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="settings-grid">
            <!-- CÀI ĐẶT CHUNG -->
            <div class="settings-card">
                <h3>Thông tin chung</h3>
                <p class="card-subtitle">
                    Cập nhật tên website, tagline và cấu hình danh sách.
                </p>

                <div class="form-group">
                    <label for="site_name">Tên website</label>
                    <input
                        type="text"
                        id="site_name"
                        name="site_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars(get_setting('site_name', SITE_NAME)); ?>"
                        placeholder="Ví dụ: MusicHub"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="site_tagline">Tagline / Mô tả ngắn</label>
                    <input
                        type="text"
                        id="site_tagline"
                        name="site_tagline"
                        class="form-control"
                        value="<?php echo htmlspecialchars(get_setting('site_tagline', 'Nghe nhạc mọi lúc mọi nơi')); ?>"
                        placeholder="Ví dụ: Nghe nhạc mọi lúc mọi nơi"
                    >
                </div>

                <div class="form-group">
                    <label for="items_per_page">Số dòng / trang (admin listing)</label>
                    <input
                        type="number"
                        id="items_per_page"
                        name="items_per_page"
                        class="form-control"
                        min="5"
                        max="100"
                        value="<?php echo (int)get_setting('items_per_page', 20); ?>"
                    >
                    <small>Áp dụng cho danh sách bài hát, nghệ sĩ, người dùng trong trang quản trị.</small>
                </div>
            </div>

            <!-- GIAO DIỆN & PLAYER -->
            <div class="settings-card">
                <h3>Giao diện & Player</h3>
                <p class="card-subtitle">
                    Tùy chỉnh màu sắc và hành vi trình phát nhạc.
                </p>

                <div class="form-group">
                    <label for="primary_color">Màu chủ đạo</label>
                    <input
                        type="color"
                        id="primary_color"
                        name="primary_color"
                        class="form-control"
                        value="<?php echo htmlspecialchars(get_setting('primary_color', '#1db954')); ?>"
                    >
                    <small>Màu brand chính (dùng cho button, icon, v.v).</small>
                </div>

                <div class="form-group">
                    <label for="default_volume">Âm lượng mặc định (%)</label>
                    <input
                        type="number"
                        id="default_volume"
                        name="default_volume"
                        class="form-control"
                        min="0"
                        max="100"
                        value="<?php echo (int)get_setting('default_volume', 70); ?>"
                    >
                    <small>Áp dụng khi người dùng mở web lần đầu (0–100%).</small>
                </div>

                <div class="switch-row">
                    <div>
                        <div class="switch-label">Tự động phát bài tiếp theo</div>
                        <div class="switch-description">
                            Khi hết bài hiện tại, player sẽ tự chuyển sang bài kế tiếp.
                        </div>
                    </div>
                    <label class="switch-toggle">
                        <input
                            type="checkbox"
                            name="autoplay_next"
                            <?php echo get_setting('autoplay_next', '1') === '1' ? 'checked' : ''; ?>
                        >
                        <span class="switch-slider"></span>
                    </label>
                </div>

                <div class="switch-row">
                    <div>
                        <div class="switch-label">Lưu lịch sử nghe nhạc</div>
                        <div class="switch-description">
                            Ghi lại lịch sử để thống kê và gợi ý tốt hơn.
                        </div>
                    </div>
                    <label class="switch-toggle">
                        <input
                            type="checkbox"
                            name="enable_history"
                            <?php echo get_setting('enable_history', '1') === '1' ? 'checked' : ''; ?>
                        >
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>

            <!-- NGƯỜI DÙNG & HỆ THỐNG -->
            <div class="settings-card">
                <h3>Người dùng & Hệ thống</h3>
                <p class="card-subtitle">
                    Quản lý đăng ký tài khoản và trạng thái hệ thống.
                </p>

                <div class="switch-row">
                    <div>
                        <div class="switch-label">Cho phép đăng ký tài khoản mới</div>
                        <div class="switch-description">
                            Tắt nếu bạn muốn khóa việc tạo tài khoản mới.
                        </div>
                    </div>
                    <label class="switch-toggle">
                        <input
                            type="checkbox"
                            name="allow_registration"
                            <?php echo get_setting('allow_registration', '1') === '1' ? 'checked' : ''; ?>
                        >
                        <span class="switch-slider"></span>
                    </label>
                </div>

                <div class="switch-row">
                    <div>
                        <div class="switch-label">Chế độ bảo trì</div>
                        <div class="switch-description">
                            Hiển thị thông báo bảo trì cho người dùng (bạn có thể xử lý ở frontend sau).
                        </div>
                    </div>
                    <label class="switch-toggle">
                        <input
                            type="checkbox"
                            name="maintenance_mode"
                            <?php echo get_setting('maintenance_mode', '0') === '1' ? 'checked' : ''; ?>
                        >
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>

            <!-- GHI CHÚ / THÔNG TIN -->
            <div class="settings-card">
                <h3>Ghi chú</h3>
                <p class="card-subtitle">
                    Một số cài đặt cần thêm code ở frontend / player để sử dụng.
                </p>

                <ul style="font-size: 13px; color: #c0c0c0; padding-left: 16px; list-style: disc;">
                    <li><b>primary_color</b>: bạn có thể đọc từ bảng <code>settings</code> và gán vào CSS biến màu.</li>
                    <li><b>default_volume</b>, <b>autoplay_next</b>, <b>enable_history</b>:
                        đọc trong <code>player.js</code> để set config mặc định cho <code>MusicPlayer</code>.
                    </li>
                    <li><b>maintenance_mode</b>:
                        ở phần frontend, check giá trị này để redirect tới trang “Đang bảo trì”.</li>
                    <li><b>items_per_page</b>:
                        dùng cho các truy vấn phân trang trong admin (LIMIT).</li>
                </ul>
            </div>
        </div>

        <div class="settings-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu cài đặt
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
