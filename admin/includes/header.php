<?php require_once '../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin - <?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="/music-website/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">

                <a href="<?php echo SITE_URL; ?>/admin/" class="logo" style="text-decoration: none; display: flex; align-items: center;">
    
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" 
                        alt="Logo" 
                        style="height: 40px; width: auto; margin-right: 10px; object-fit: contain;">
                    
                    <span><?php echo SITE_NAME; ?></span>
                </a>


                <span class="badge-admin">ADMIN</span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/songs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'songs.php' ? 'active' : ''; ?>">
                            <i class="fas fa-music"></i>
                            <span>Bài hát</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/artists.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'artists.php' ? 'active' : ''; ?>">
                            <i class="fas fa-microphone"></i>
                            <span>Nghệ sĩ</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/albums.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'albums.php' ? 'active' : ''; ?>">
                            <i class="fas fa-compact-disc"></i>
                            <span>Albums</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Người dùng</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/statistics.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Thống kê</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Cài đặt</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Xem website</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Admin">
                    <div>
                        <p class="username"><?php echo $_SESSION['username']; ?></p>
                        <p class="role">Administrator</p>
                    </div>
                </div>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button class="btn-menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo $page_title ?? 'Admin Panel'; ?></h1>
                </div>

                <div class="topbar-right">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm...">
                    </div>

                    <button class="btn-notification">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>

                    <!-- <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Admin">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="#"><i class="fas fa-user"></i> Tài khoản</a>
                            <a href="#"><i class="fas fa-cog"></i> Cài đặt</a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                    </div> -->

                    <div class="user-dropdown" id="userDropdown">
                    <button class="user-btn" id="userDropdownBtn">
                        <img src="<?php echo SITE_URL; ?>/assets/images/default-avatar.png" alt="Admin">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    

                    <div class="dropdown-menu" id="userDropdownMenu">
                        <a href="<?php echo SITE_URL; ?>/admin/profile.php"><i class="fas fa-user"></i> Tài khoản</a>
                        <a href="<?php echo SITE_URL; ?>/admin/settings.php"><i class="fas fa-cog"></i> Cài đặt</a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                    </div>


                </div>



                </div>
            </header>

            <!-- Content Area -->
            <main class="admin-content">