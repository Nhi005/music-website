<?php
require_once '../includes/config.php';

// Kiểm tra quyền admin
if(!isAdmin()) {
    redirect('../login.php');
}

$page_title = "Dashboard";
$db = getDB();

// Lấy thống kê tổng quan
$stats = [
    'total_plays' => $db->query("SELECT SUM(play_count) as total FROM songs")->fetch()['total'] ?? 0,
    'total_users' => $db->query("SELECT COUNT(*) as total FROM users")->fetch()['total'] ?? 0,
    'total_songs' => $db->query("SELECT COUNT(*) as total FROM songs")->fetch()['total'] ?? 0,
    'total_artists' => $db->query("SELECT COUNT(*) as total FROM artists")->fetch()['total'] ?? 0,
];

// Lượt nghe tuần trước
$last_week_plays = $db->query("
    SELECT COUNT(*) as total
    FROM listening_history
    WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 2 WEEK)
    AND listened_at < DATE_SUB(NOW(), INTERVAL 1 WEEK)
")->fetch()['total'] ?? 1;

$current_week_plays = $db->query("
    SELECT COUNT(*) as total
    FROM listening_history
    WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
")->fetch()['total'] ?? 0;

$plays_change = $last_week_plays > 0 ? round((($current_week_plays - $last_week_plays) / $last_week_plays) * 100) : 0;

// Người dùng tháng trước
$last_month_users = $db->query("
    SELECT COUNT(*) as total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
    AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)
")->fetch()['total'] ?? 1;

$current_month_users = $db->query("
    SELECT COUNT(*) as total
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
")->fetch()['total'] ?? 0;

$users_change = $last_month_users > 0 ? round((($current_month_users - $last_month_users) / $last_month_users) * 100) : 0;

// Lượt nghe theo ngày (7 ngày gần nhất)
$plays_by_day = $db->query("
    SELECT DATE(listened_at) as date, COUNT(*) as plays
    FROM listening_history
    WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(listened_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Top 10 bài hát
$top_songs = $db->query("
    SELECT s.title, a.name as artist_name, s.play_count
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    ORDER BY s.play_count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Top nghệ sĩ
$top_artists = $db->query("
    SELECT a.name, a.avatar, COUNT(DISTINCT f.id) as fans
    FROM artists a
    LEFT JOIN songs s ON a.id = s.artist_id
    LEFT JOIN favorites f ON s.id = f.song_id
    GROUP BY a.id
    ORDER BY fans DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Người dùng mới theo tuần
$new_users = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as users
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY WEEK(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Hoạt động gần đây
$recent_activities = $db->query("
    (SELECT 'song' as type, s.title as name, s.created_at as time
     FROM songs s
     ORDER BY s.created_at DESC
     LIMIT 5)
    
    UNION ALL
    
    (SELECT 'user' as type, u.username as name, u.created_at as time
     FROM users u
     ORDER BY u.created_at DESC
     LIMIT 5)
    
    ORDER BY time DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="dashboard-grid">
    <!-- Stat Cards -->
    <div class="stat-card card-plays">
        <div class="stat-icon">
            <i class="fas fa-play-circle"></i>
        </div>
        <div class="stat-info">
            <h3>Tổng lượt nghe</h3>
            <p class="stat-number"><?php echo number_format($stats['total_plays']); ?></p>
            <span class="stat-change <?php echo $plays_change >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $plays_change >= 0 ? 'up' : 'down'; ?>"></i> 
                <?php echo abs($plays_change); ?>% từ tuần trước
            </span>
        </div>
    </div>

    <div class="stat-card card-users">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Người dùng</h3>
            <p class="stat-number"><?php echo number_format($stats['total_users']); ?></p>
            <span class="stat-change <?php echo $users_change >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $users_change >= 0 ? 'up' : 'down'; ?>"></i> 
                <?php echo abs($users_change); ?>% từ tháng trước
            </span>
        </div>
    </div>

    <div class="stat-card card-songs">
        <div class="stat-icon">
            <i class="fas fa-music"></i>
        </div>
        <div class="stat-info">
            <h3>Bài hát</h3>
            <p class="stat-number"><?php echo number_format($stats['total_songs']); ?></p>
            <span class="stat-change neutral">
                <i class="fas fa-minus"></i> Tổng số bài hát
            </span>
        </div>
    </div>

    <div class="stat-card card-artists">
        <div class="stat-icon">
            <i class="fas fa-microphone"></i>
        </div>
        <div class="stat-info">
            <h3>Nghệ sĩ</h3>
            <p class="stat-number"><?php echo number_format($stats['total_artists']); ?></p>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> Tổng số nghệ sĩ
            </span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Lượt nghe theo ngày</h3>
            <div class="filter-buttons">
                <button class="btn-filter active" data-period="day">Ngày</button>
                <button class="btn-filter" data-period="week">Tuần</button>
                <button class="btn-filter" data-period="month">Tháng</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="playsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-user-plus"></i> Người dùng mới</h3>
        </div>
        <div class="card-body">
            <canvas id="usersChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Lists -->
<div class="top-lists-grid">
    <div class="top-list-card">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Top 10 Bài Hát</h3>
            <a href="songs.php" class="btn-link">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="card-body">
            <div class="top-list">
                <?php if(empty($top_songs)): ?>
                    <p style="padding: 20px; color: #a0a0a0; text-align: center;">Chưa có dữ liệu</p>
                <?php else: ?>
                    <?php foreach($top_songs as $index => $song): ?>
                    <div class="top-item">
                        <div class="top-rank <?php echo $index < 3 ? 'rank-top' : ''; ?>">
                            <?php echo $index + 1; ?>
                        </div>
                        <div class="top-info">
                            <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                            <p><?php echo htmlspecialchars($song['artist_name'] ?? 'Unknown'); ?></p>
                        </div>
                        <div class="top-plays">
                            <i class="fas fa-play-circle"></i>
                            <?php echo number_format($song['play_count']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="top-list-card">
        <div class="card-header">
            <h3><i class="fas fa-star"></i> Nghệ Sĩ Được Yêu Thích</h3>
            <a href="artists.php" class="btn-link">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="card-body">
            <div class="top-artists-list">
                <?php if(empty($top_artists)): ?>
                    <p style="padding: 20px; color: #a0a0a0; text-align: center;">Chưa có dữ liệu</p>
                <?php else: ?>
                    <?php foreach($top_artists as $artist): ?>
                    <div class="artist-item">
                        <img src="<?php echo !empty($artist['avatar']) ? BASE_URL . $artist['avatar'] : BASE_URL . '/assets/images/default-artist.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($artist['name']); ?>">
                        <div class="artist-info">
                            <h4><?php echo htmlspecialchars($artist['name']); ?></h4>
                            <p><i class="fas fa-heart"></i> <?php echo number_format($artist['fans']); ?> fans</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="activity-card">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Hoạt động gần đây</h3>
    </div>
    <div class="card-body">
        <div class="activity-list">
            <?php if(empty($recent_activities)): ?>
                <p style="padding: 20px; color: #a0a0a0; text-align: center;">Chưa có hoạt động</p>
            <?php else: ?>
                <?php foreach($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['type'] == 'song' ? 'new' : 'user'; ?>">
                        <i class="fas fa-<?php echo $activity['type'] == 'song' ? 'music' : 'user-plus'; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <p>
                            <strong><?php echo $activity['type'] == 'song' ? 'Bài hát mới' : 'Người dùng mới'; ?></strong> 
                            <?php echo $activity['type'] == 'song' ? 'được thêm' : 'đăng ký'; ?>: 
                            "<?php echo htmlspecialchars($activity['name']); ?>"
                        </p>
                        <span class="activity-time">
                            <?php 
                            $time_diff = time() - strtotime($activity['time']);
                            if($time_diff < 3600) {
                                echo floor($time_diff / 60) . ' phút trước';
                            } elseif($time_diff < 86400) {
                                echo floor($time_diff / 3600) . ' giờ trước';
                            } else {
                                echo floor($time_diff / 86400) . ' ngày trước';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// Plays Chart
const playsData = <?php echo json_encode($plays_by_day); ?>;
const playsCtx = document.getElementById('playsChart').getContext('2d');
new Chart(playsCtx, {
    type: 'line',
    data: {
        labels: playsData.map(d => d.date),
        datasets: [{
            label: 'Lượt nghe',
            data: playsData.map(d => d.plays),
            borderColor: '#1db954',
            backgroundColor: 'rgba(29, 185, 84, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { color: '#a0a0a0' },
                grid: { color: '#2a2a2a' }
            },
            x: {
                ticks: { color: '#a0a0a0' },
                grid: { color: '#2a2a2a' }
            }
        }
    }
});

// Users Chart
const usersData = <?php echo json_encode($new_users); ?>;
const usersCtx = document.getElementById('usersChart').getContext('2d');
new Chart(usersCtx, {
    type: 'bar',
    data: {
        labels: usersData.map(d => d.date),
        datasets: [{
            label: 'Người dùng mới',
            data: usersData.map(d => d.users),
            backgroundColor: '#1db954',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: { color: '#a0a0a0' },
                grid: { color: '#2a2a2a' }
            },
            x: {
                ticks: { color: '#a0a0a0' },
                grid: { display: false }
            }
        }
    }
});

// Filter buttons functionality
document.querySelectorAll('.btn-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // TODO: Implement filter logic
        console.log('Filter by:', this.dataset.period);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>