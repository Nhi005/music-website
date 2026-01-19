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
    SELECT a.name, a.avatar, COUNT(f.id) as fans
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
            <span class="stat-change positive"><i class="fas fa-arrow-up"></i> 12% từ tuần trước</span>
        </div>
    </div>

    <div class="stat-card card-users">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Người dùng</h3>
            <p class="stat-number"><?php echo number_format($stats['total_users']); ?></p>
            <span class="stat-change positive"><i class="fas fa-arrow-up"></i> 8% từ tháng trước</span>
        </div>
    </div>

    <div class="stat-card card-songs">
        <div class="stat-icon">
            <i class="fas fa-music"></i>
        </div>
        <div class="stat-info">
            <h3>Bài hát</h3>
            <p class="stat-number"><?php echo number_format($stats['total_songs']); ?></p>
            <span class="stat-change neutral"><i class="fas fa-minus"></i> Không đổi</span>
        </div>
    </div>

    <div class="stat-card card-artists">
        <div class="stat-icon">
            <i class="fas fa-microphone"></i>
        </div>
        <div class="stat-info">
            <h3>Nghệ sĩ</h3>
            <p class="stat-number"><?php echo number_format($stats['total_artists']); ?></p>
            <span class="stat-change positive"><i class="fas fa-arrow-up"></i> 5 mới</span>
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
                <?php foreach($top_songs as $index => $song): ?>
                <div class="top-item">
                    <div class="top-rank <?php echo $index < 3 ? 'rank-top' : ''; ?>">
                        <?php echo $index + 1; ?>
                    </div>
                    <div class="top-info">
                        <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                        <p><?php echo htmlspecialchars($song['artist_name']); ?></p>
                    </div>
                    <div class="top-plays">
                        <i class="fas fa-play-circle"></i>
                        <?php echo number_format($song['play_count']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
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
                <?php foreach($top_artists as $artist): ?>
                <div class="artist-item">
                    <img src="<?php echo $artist['avatar'] ?: '../assets/images/default-artist.jpg'; ?>" alt="">
                    <div class="artist-info">
                        <h4><?php echo htmlspecialchars($artist['name']); ?></h4>
                        <p><i class="fas fa-heart"></i> <?php echo number_format($artist['fans']); ?> fans</p>
                    </div>
                </div>
                <?php endforeach; ?>
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
            <div class="activity-item">
                <div class="activity-icon new">
                    <i class="fas fa-music"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Bài hát mới</strong> được thêm: "Song Name"</p>
                    <span class="activity-time">5 phút trước</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon user">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Người dùng mới</strong> đăng ký: user@email.com</p>
                    <span class="activity-time">1 giờ trước</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon edit">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="activity-content">
                    <p><strong>Cập nhật</strong> thông tin nghệ sĩ: "Artist Name"</p>
                    <span class="activity-time">2 giờ trước</span>
                </div>
            </div>
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
            y: { beginAtZero: true }
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
            backgroundColor: '#1db954'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>