<?php
require_once '../includes/config.php';

// Kiểm tra quyền admin
if(!isAdmin()) {
    redirect('../login.php');
}

$page_title = "Thống kê";
$db = getDB();

// Error handling wrapper
function safeQuery($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) { return []; }
}

function safeQuerySingle($db, $query, $params = []) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch(PDOException $e) { return []; }
}

// Hàm Export CSV
function exportStatisticsCSV($db, $period) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="thong-ke-' . $period . '-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
    fputcsv($output, ['BÁO CÁO THỐNG KÊ - ' . strtoupper($period)]);
    fputcsv($output, ['Ngày xuất:', date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    
    // Top Songs
    fputcsv($output, ['TOP 10 BÀI HÁT']);
    fputcsv($output, ['STT', 'Tên bài hát', 'Nghệ sĩ', 'Lượt nghe']);
    $top_songs = $db->query("SELECT s.title, a.name as artist_name, s.play_count FROM songs s LEFT JOIN artists a ON s.artist_id = a.id ORDER BY s.play_count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach($top_songs as $index => $song) {
        fputcsv($output, [$index + 1, $song['title'], $song['artist_name'], $song['play_count']]);
    }
    fclose($output);
    exit;
}

// Xử lý export
if(isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $period = $_GET['period'] ?? 'all';
    if($export_type == 'csv') exportStatisticsCSV($db, $period);
    exit;
}

// Lấy tham số filter
$period = $_GET['period'] ?? 'all'; 
$start_date = null;
$end_date = date('Y-m-d');

switch($period) {
    case 'day': $start_date = date('Y-m-d'); break;
    case 'week': $start_date = date('Y-m-d', strtotime('-7 days')); break;
    case 'month': $start_date = date('Y-m-d', strtotime('-30 days')); break;
    case 'year': $start_date = date('Y-m-d', strtotime('-365 days')); break;
    default: $start_date = null; break;
}

// 1. Thống kê số bài hát theo nghệ sĩ
$songs_by_artist_query = "SELECT a.name as artist_name, COUNT(s.id) as song_count FROM artists a LEFT JOIN songs s ON a.id = s.artist_id";
if($start_date) $songs_by_artist_query .= " WHERE s.created_at >= :start_date OR s.created_at IS NULL";
$songs_by_artist_query .= " GROUP BY a.id, a.name ORDER BY song_count DESC";
$stmt = $db->prepare($songs_by_artist_query);
if($start_date) $stmt->bindParam(':start_date', $start_date);
$stmt->execute();
$songs_by_artist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Thống kê theo thể loại (Mapping giả lập)
$songs_by_genre = [];
try {
    $genre_query = "SELECT CASE WHEN a.name IN ('Đen Vâu', 'HIEUTHUHAI') THEN 'Rap' ELSE 'Pop' END as genre, COUNT(s.id) as song_count FROM songs s LEFT JOIN artists a ON s.artist_id = a.id";
    if($start_date) $genre_query .= " WHERE s.created_at >= :start_date";
    $genre_query .= " GROUP BY genre ORDER BY song_count DESC";
    $stmt = $db->prepare($genre_query);
    if($start_date) $stmt->bindParam(':start_date', $start_date);
    $stmt->execute();
    $songs_by_genre = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $songs_by_genre = []; }

// 3. Lượt nghe
$listens_query = "SELECT DATE(listened_at) as date, COUNT(*) as listens FROM listening_history";
if($start_date) $listens_query .= " WHERE DATE(listened_at) >= :start_date";
$listens_query .= " GROUP BY DATE(listened_at) ORDER BY date ASC";
$stmt = $db->prepare($listens_query);
if($start_date) $stmt->bindParam(':start_date', $start_date);
$stmt->execute();
$listens_by_date = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. User Growth
$user_growth_query = "SELECT DATE(created_at) as date, COUNT(*) as users FROM users";
if($start_date) $user_growth_query .= " WHERE DATE(created_at) >= :start_date";
$user_growth_query .= " GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $db->prepare($user_growth_query);
if($start_date) $stmt->bindParam(':start_date', $start_date);
$stmt->execute();
$user_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Tổng visit
$total_visits_query = "SELECT COUNT(*) as total_plays, COUNT(DISTINCT user_id) as unique_users FROM listening_history";
if($start_date) $total_visits_query .= " WHERE DATE(listened_at) >= :start_date";
$stmt = $db->prepare($total_visits_query);
if($start_date) $stmt->bindParam(':start_date', $start_date);
$stmt->execute();
$total_visits = $stmt->fetch(PDO::FETCH_ASSOC);

// Số liệu tổng
$total_stats = [
    'total_songs' => safeQuerySingle($db, "SELECT COUNT(*) as total FROM songs")['total'] ?? 0,
    'total_artists' => safeQuerySingle($db, "SELECT COUNT(*) as total FROM artists")['total'] ?? 0,
];

require_once 'includes/header.php';
?>

<style>
    .filter-section {
        background: linear-gradient(135deg, var(--admin-card) 0%, #242424 100%);
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .filter-group { display: flex; gap: 15px; align-items: center; }
    .filter-buttons { background: rgba(255,255,255,0.05); padding: 4px; border-radius: 8px; }
    .btn-filter {
        padding: 8px 16px;
        color: var(--admin-text-muted);
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        display: inline-block;
        transition: all 0.3s ease;
    }
    .btn-filter.active { background: var(--admin-primary); color: white; }
    .btn-filter:hover { background: rgba(29, 185, 84, 0.1); }
    .btn-export {
        padding: 10px 20px;
        background: rgba(29, 185, 84, 0.1);
        color: var(--admin-primary);
        border: 1px solid var(--admin-primary);
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-export:hover { background: var(--admin-primary); color: white; }
    
    .stats-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .stats-table th { text-align: left; padding: 12px; border-bottom: 2px solid #333; color: #aaa; font-weight: 600; }
    .stats-table td { padding: 12px; border-bottom: 1px solid #222; }
    .stats-table tbody tr:hover { background: rgba(255,255,255,0.02); }
</style>

<div class="filter-section">
    <div class="filter-group">
        <label style="color: #fff; font-weight: 600;">Lọc theo thời gian:</label>
        <div class="filter-buttons">
            <a href="?period=day" class="btn-filter <?php echo $period == 'day' ? 'active' : ''; ?>">Ngày</a>
            <a href="?period=week" class="btn-filter <?php echo $period == 'week' ? 'active' : ''; ?>">Tuần</a>
            <a href="?period=month" class="btn-filter <?php echo $period == 'month' ? 'active' : ''; ?>">Tháng</a>
            <a href="?period=year" class="btn-filter <?php echo $period == 'year' ? 'active' : ''; ?>">Năm</a>
            <a href="?period=all" class="btn-filter <?php echo $period == 'all' ? 'active' : ''; ?>">Tất cả</a>
        </div>
    </div>
    <div class="export-buttons">
        <a href="?export=csv&period=<?php echo $period; ?>" class="btn-export">
            <i class="fas fa-file-csv"></i> Xuất CSV
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="stat-card card-plays">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3>Lượt truy cập</h3>
            <p class="stat-number"><?php echo number_format($total_visits['total_plays'] ?? 0); ?></p>
            <span class="stat-change positive">Kỳ này</span>
        </div>
    </div>
    <div class="stat-card card-users">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Người dùng hoạt động</h3>
            <p class="stat-number"><?php echo number_format($total_visits['unique_users'] ?? 0); ?></p>
            <span class="stat-change positive">Kỳ này</span>
        </div>
    </div>
    <div class="stat-card card-songs">
        <div class="stat-icon"><i class="fas fa-music"></i></div>
        <div class="stat-info">
            <h3>Tổng bài hát</h3>
            <p class="stat-number"><?php echo number_format($total_stats['total_songs']); ?></p>
            <span class="stat-change neutral">Tổng số bài hát</span>
        </div>
    </div>
    <div class="stat-card card-artists">
        <div class="stat-icon"><i class="fas fa-microphone"></i></div>
        <div class="stat-info">
            <h3>Tổng nghệ sĩ</h3>
            <p class="stat-number"><?php echo number_format($total_stats['total_artists']); ?></p>
            <span class="stat-change positive">Tổng số nghệ sĩ</span>
        </div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Biểu đồ lượt nghe</h3>
        </div>
        <div class="card-body">
            <canvas id="listensChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-user-plus"></i> Tăng trưởng người dùng</h3>
        </div>
        <div class="card-body">
            <canvas id="userGrowthChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-microphone"></i> Bài hát theo nghệ sĩ</h3>
        </div>
        <div class="card-body">
            <canvas id="songsByArtistChart"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="card-header">
            <h3><i class="fas fa-compact-disc"></i> Bài hát theo thể loại</h3>
        </div>
        <div class="card-body">
            <canvas id="songsByGenreChart"></canvas>
        </div>
    </div>
</div>

<div class="chart-card" style="margin-top: 24px;">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Chi tiết theo Nghệ sĩ</h3>
    </div>
    <div class="card-body">
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Nghệ sĩ</th>
                    <th>Số bài hát</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($songs_by_artist)): ?>
                    <tr><td colspan="2" style="text-align: center; color: #a0a0a0;">Chưa có dữ liệu</td></tr>
                <?php else: ?>
                    <?php foreach(array_slice($songs_by_artist, 0, 10) as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['artist_name']); ?></td>
                        <td><?php echo number_format($item['song_count']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const spotifyGreen = '#1db954';
const spotifyColors = ['#1db954', '#1ed760', '#1aa34a', '#169c44', '#0f7a33'];

// Data PHP -> JS
const listensData = <?php echo json_encode($listens_by_date); ?>;
const userGrowthData = <?php echo json_encode($user_growth); ?>;
const songsByArtistData = <?php echo json_encode(array_slice($songs_by_artist, 0, 10)); ?>;
const songsByGenreData = <?php echo json_encode($songs_by_genre); ?>;

// 1. Listens Chart
new Chart(document.getElementById('listensChart'), {
    type: 'line',
    data: {
        labels: listensData.map(d => d.date),
        datasets: [{
            label: 'Lượt nghe',
            data: listensData.map(d => parseInt(d.listens)),
            borderColor: spotifyGreen,
            backgroundColor: 'rgba(29, 185, 84, 0.1)',
            tension: 0.4, 
            fill: true
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
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

// 2. User Growth Chart
new Chart(document.getElementById('userGrowthChart'), {
    type: 'bar',
    data: {
        labels: userGrowthData.map(d => d.date),
        datasets: [{
            label: 'Người dùng mới',
            data: userGrowthData.map(d => parseInt(d.users)),
            backgroundColor: spotifyGreen,
            borderRadius: 6
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
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

// 3. Songs by Artist (Doughnut)
new Chart(document.getElementById('songsByArtistChart'), {
    type: 'doughnut',
    data: {
        labels: songsByArtistData.map(d => d.artist_name),
        datasets: [{
            data: songsByArtistData.map(d => parseInt(d.song_count)),
            backgroundColor: spotifyColors,
            borderColor: '#121212',
            borderWidth: 2
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        plugins: { 
            legend: { 
                position: 'right', 
                labels: { color: '#a0a0a0', padding: 10 }
            } 
        } 
    }
});

// 4. Songs by Genre (Pie)
new Chart(document.getElementById('songsByGenreChart'), {
    type: 'pie',
    data: {
        labels: songsByGenreData.map(d => d.genre),
        datasets: [{
            data: songsByGenreData.map(d => parseInt(d.song_count)),
            backgroundColor: spotifyColors,
            borderColor: '#121212',
            borderWidth: 2
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false, 
        plugins: { 
            legend: { 
                position: 'right', 
                labels: { color: '#a0a0a0', padding: 10 }
            } 
        } 
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>