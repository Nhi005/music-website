<?php 
// Include config nếu chưa có
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// Lấy tên trang hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo SITE_NAME; ?> - Nền tảng nghe nhạc trực tuyến hàng đầu Việt Nam">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" 
                            alt="<?php echo SITE_NAME; ?>" 
                            style="height: 40px; width: auto;">
                        <span><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                            
                
                <!-- Main Navigation -->
                <nav class="main-nav">
                    <ul>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Trang chủ</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/search.php" class="<?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
                                <i class="fas fa-search"></i>
                                <span>Khám phá</span>
                            </a>
                        </li>
                        <?php if(isLoggedIn()): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i>
                                <span>Cá nhân</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Header Right -->
                <div class="header-right">
                    <!-- Search Box -->
                    <div class="search-box">
                        <form action="<?php echo SITE_URL; ?>/search.php" method="GET">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="q" 
                                   placeholder="Tìm bài hát, nghệ sĩ..." 
                                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                                   autocomplete="off">
                        </form>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="user-menu">
                        <?php if(isLoggedIn()): ?>
                            <!-- Logged In -->
                            <div class="dropdown">
                                <button class="user-btn" type="button">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" 
                                         alt="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content">
                                    <a href="<?php echo SITE_URL; ?>/profile.php">
                                        <i class="fas fa-user"></i> Trang cá nhân
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/my_playlists.php?tab=playlists">
                                        <i class="fas fa-list"></i> Playlist của tôi
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/profile.php?tab=favorites">
                                        <i class="fas fa-heart"></i> Yêu thích
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/history.php?tab=history">
                                        <i class="fas fa-history"></i> Lịch sử nghe
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php if(isAdmin()): ?>
                                    <a href="<?php echo SITE_URL; ?>/admin/" target="_blank">
                                        <i class="fas fa-cog"></i> Quản trị
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    <a href="<?php echo SITE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Not Logged In -->
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Đăng nhập</span>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                <span>Đăng ký</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo">
                <i class="fas fa-music"></i>
                <span><?php echo SITE_NAME; ?></span>
            </div>
            <button class="mobile-menu-close" id="mobileMenuClose" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="mobile-nav">
            <ul>
                <li>
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/search.php">
                        <i class="fas fa-search"></i> Khám phá
                    </a>
                </li>
                <?php if(isLoggedIn()): ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/profile.php">
                        <i class="fas fa-user"></i> Cá nhân
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/profile.php?tab=playlists">
                        <i class="fas fa-list"></i> Playlist của tôi
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/profile.php?tab=favorites">
                        <i class="fas fa-heart"></i> Yêu thích
                    </a>
                </li>
                <?php if(isAdmin()): ?>
                <li class="divider"></li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/">
                        <i class="fas fa-cog"></i> Quản trị
                    </a>
                </li>
                <?php endif; ?>
                <li class="divider"></li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <a href="<?php echo SITE_URL; ?>/login.php">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/register.php">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <?php if(isLoggedIn()): ?>
        <div class="mobile-user-info">
            <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Avatar">
            <div>
                <p class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p class="email">Xem trang cá nhân</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Content Start -->
    <main class="main-content">

    <script>
    // Dropdown user menu: click để mở/đóng (không bị "rơ chuột là mất")
    (function(){
        const dropdown = document.querySelector('.user-menu .dropdown');
        if(!dropdown) return;

        const btn = dropdown.querySelector('.user-btn');
        const menu = dropdown.querySelector('.dropdown-content');
        if(!btn || !menu) return;

        function openMenu(){
            dropdown.classList.add('open');
            menu.style.display = 'block';
        }
        function closeMenu(){
            dropdown.classList.remove('open');
            menu.style.display = 'none';
        }
        function toggleMenu(){
            if(dropdown.classList.contains('open')) closeMenu();
            else openMenu();
        }

        // mặc định đóng
        closeMenu();

        // bấm nút -> toggle
        btn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        // click trong menu thì không đóng
        menu.addEventListener('click', function(e){
            e.stopPropagation();
        });

        // click ra ngoài -> đóng
        document.addEventListener('click', function(){
            closeMenu();
        });

        // nhấn ESC -> đóng
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape') closeMenu();
        });
    })();
    </script>
    
    <script>
    // Mobile Menu Toggle
    (function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        
        function openMobileMenu() {
            if(mobileMenu && mobileMenuOverlay) {
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeMobileMenu() {
            if(mobileMenu && mobileMenuOverlay) {
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        if(mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', openMobileMenu);
        }
        
        if(mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }
        
        if(mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        }
        
        // Close on link click
        const mobileNavLinks = document.querySelectorAll('.mobile-nav a');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    })();
    </script>
