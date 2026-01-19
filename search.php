<?php
$page_title = "Tìm kiếm";
require_once 'includes/header.php';
$db = getDB();
$liked_songs = [];
if (isset($_SESSION['user_id'])) {
    try {
        $u_id = $_SESSION['user_id'];
        $stmt = $db->query("SELECT song_id FROM favorites WHERE user_id = $u_id");
        $liked_songs = $stmt->fetchAll(PDO::FETCH_COLUMN); 
    } catch(Exception $e) { }
}

if (session_status() === PHP_SESSION_NONE) session_start();

// ====================== AUTOCOMPLETE (AJAX) - GIỮ NGUYÊN ======================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    $term = trim($_GET['term'] ?? '');

    if ($term === '') {
        echo json_encode([]);
        exit;
    }

    $like = '%' . $term . '%';

    // 1. Tìm bài hát
    $stmt = $db->prepare("SELECT s.id, s.title AS label, 'song' AS type FROM songs s WHERE s.title LIKE ? LIMIT 5");
    $stmt->execute([$like]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Tìm nghệ sĩ
    $stmt = $db->prepare("SELECT a.id, a.name AS label, 'artist' AS type FROM artists a WHERE a.name LIKE ? LIMIT 5");
    $stmt->execute([$like]);
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Tìm album
    $albums = [];
    try {
        $stmt = $db->prepare("SELECT al.id, al.title AS label, 'album' AS type FROM albums al WHERE al.title LIKE ? LIMIT 5");
        $stmt->execute([$like]);
        $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $items = array_merge($songs, $artists, $albums);
    $items = array_slice($items, 0, 10);

    echo json_encode($items);
    exit;
}

// ====================== FORM TÌM KIẾM CHÍNH & PHÂN TRANG ======================
$q      = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

// 1. CẤU HÌNH PHÂN TRANG
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit  = 18; // Số lượng bài hát mỗi trang (bạn có thể sửa số này)
$offset = ($page - 1) * $limit;

// Lưu lịch sử tìm kiếm
if ($q !== '') {
    $_SESSION['search_history'] = $_SESSION['search_history'] ?? [];
    array_unshift($_SESSION['search_history'], $q);
    $_SESSION['search_history'] = array_values(array_unique($_SESSION['search_history']));
    $_SESSION['search_history'] = array_slice($_SESSION['search_history'], 0, 8);
}

$results = [];
$total_pages = 0;
$total_rows = 0;

try {
    // Xây dựng câu query cơ bản
    $where  = "";
    $order  = "ORDER BY s.play_count DESC";
    $params = [];

    if ($filter === 'new')      $order = "ORDER BY s.created_at DESC";
    if ($filter === 'old')      $order = "ORDER BY s.created_at ASC";
    if ($filter === 'trending') $order = "ORDER BY s.play_count DESC";

    if ($q !== '') {
        $where = "WHERE (s.title LIKE ? OR a.name LIKE ? OR al.title LIKE ?)";
        $like  = '%' . $q . '%';
        $params = [$like, $like, $like];
    }

    // 2. ĐẾM TỔNG SỐ KẾT QUẢ (Để tính số trang)
    $sql_count = "
        SELECT COUNT(s.id) 
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        LEFT JOIN albums  al ON s.album_id = al.id
        $where
    ";
    $stmt_count = $db->prepare($sql_count);
    $stmt_count->execute($params);
    $total_rows = $stmt_count->fetchColumn();
    
    // Tính tổng số trang
    $total_pages = ceil($total_rows / $limit);

    // 3. LẤY DỮ LIỆU (Thêm LIMIT và OFFSET)
    $sql = "
        SELECT 
            s.*,
            a.name AS artist_name,
            al.title AS album_title
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        LEFT JOIN albums  al ON s.album_id = al.id
        $where
        $order
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Fallback nếu bảng albums bị lỗi (như code cũ của bạn)
    try {
        $where  = "";
        $order  = "ORDER BY s.play_count DESC";
        $params = [];

        if ($filter === 'new')      $order = "ORDER BY s.created_at DESC";
        if ($filter === 'old')      $order = "ORDER BY s.created_at ASC";
        if ($filter === 'trending') $order = "ORDER BY s.play_count DESC";

        if ($q !== '') {
            $where = "WHERE (s.title LIKE ? OR a.name LIKE ?)";
            $like  = '%' . $q . '%';
            $params = [$like, $like];
        }

        // Đếm lại cho trường hợp fallback
        $stmt_count = $db->prepare("SELECT COUNT(s.id) FROM songs s LEFT JOIN artists a ON s.artist_id = a.id $where");
        $stmt_count->execute($params);
        $total_rows = $stmt_count->fetchColumn();
        $total_pages = ceil($total_rows / $limit);

        // Query fallback có phân trang
        $sql = "
            SELECT s.*, a.name AS artist_name
            FROM songs s
            LEFT JOIN artists a ON s.artist_id = a.id
            $where
            $order
            LIMIT $limit OFFSET $offset
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $results = [];
    }
}

$history = $_SESSION['search_history'] ?? [];

// Hàm build_cover_url (Giữ nguyên logic của bạn)
function build_cover_url($path) {
    $default = 'assets/images/default-cover.jpg';
    if (empty($path) || trim($path) === '') return SITE_URL . '/' . $default;
    if (preg_match('~^https?://~', $path)) return $path;
    $clean_path = ltrim($path, '/');
    if (file_exists($clean_path)) return SITE_URL . '/' . $clean_path;
    return SITE_URL . '/' . $default;
}
?>

<style>
/* ... GIỮ NGUYÊN CSS CŨ CỦA BẠN ... */
.search-section { padding: 60px 0; min-height: calc(100vh - 200px); }
.search-header { margin-bottom: 40px; }
.search-header h2 { font-size: 42px; margin-bottom: 10px; display: flex; align-items: center; gap: 15px; }
.search-form-container { background: var(--bg-light); padding: 30px; border-radius: 16px; margin-bottom: 30px; }
.search-form { display: flex; gap: 12px; flex-wrap: wrap; }
.search-input-wrapper { position: relative; flex: 1; min-width: 300px; }
.search-input-wrapper i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 18px; }
.search-form input[type="text"] { width: 100%; padding: 16px 20px 16px 50px; background: var(--bg-color); border: 2px solid var(--border-color); border-radius: 12px; color: var(--text-color); font-size: 16px; outline: none; }
.search-form select { padding: 16px 20px; background: var(--bg-color); border: 2px solid var(--border-color); border-radius: 12px; color: var(--text-color); font-size: 15px; min-width: 170px; cursor: pointer; }
.search-form .btn { padding: 16px 40px; border-radius: 12px; cursor: pointer; }
.suggest-box { position: relative; max-width: 100%; z-index: 1000; }
.suggest-list { display: none; position: absolute; z-index: 50; left: 0; right: 0; top: 8px; background: var(--bg-light); border: 2px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
.suggest-item { padding: 14px 18px; border-top: 1px solid var(--border-color); cursor: pointer; }
.suggest-item:hover { background: var(--hover-bg); }
.history-tags { display: flex; gap: 10px; flex-wrap: wrap; }
.history-tag { padding: 8px 16px; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 20px; color: var(--text-color); font-size: 14px; text-decoration: none; }
.search-results-header { margin: 30px 0 20px; padding-bottom: 15px; border-bottom: 2px solid var(--border-color); }

/* ... CSS PHÂN TRANG MỚI ... */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 50px;
    flex-wrap: wrap;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.page-link:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: var(--bg-color);
}

.page-link.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.page-link.disabled {
    opacity: 0.5;
    pointer-events: none;
    cursor: default;
}
</style>

<section class="search-section">
  <div class="container">
    
    <div class="search-header">
      <h2><i class="fas fa-search"></i> Tìm kiếm</h2>
    </div>

    <div class="search-form-container">
      <form method="GET" action="search.php" class="search-form">
        <div class="search-input-wrapper">
          <i class="fas fa-search"></i>
          <input
            id="searchInput"
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="Tìm bài hát, nghệ sĩ, album..."
            autocomplete="off"
          />
        </div>

        <select name="filter">
          <option value="all" <?= $filter==='all'?'selected':'' ?>>Tất cả</option>
          <option value="trending" <?= $filter==='trending'?'selected':'' ?>>Trending</option>
          <option value="new" <?= $filter==='new'?'selected':'' ?>>Mới nhất</option>
          <option value="old" <?= $filter==='old'?'selected':'' ?>>Cũ nhất</option>
        </select>

        <button class="btn btn-primary" type="submit">
          <i class="fas fa-search"></i> Tìm
        </button>
      </form>

      <div class="suggest-box"><div id="suggestList" class="suggest-list"></div></div>
      <?php if(!empty($history)): ?>
      <div class="search-history">
        <div class="search-history-title">Lịch sử tìm kiếm</div>
        <div class="history-tags">
          <?php foreach($history as $h): ?>
            <a class="history-tag" href="search.php?q=<?= urlencode($h) ?>&filter=<?= urlencode($filter) ?>">
              <i class="fas fa-clock"></i> <?= htmlspecialchars($h) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if($q !== ''): ?>
    <div class="search-results-header">
      <div class="search-query">
        Kết quả cho: <b>"<?= htmlspecialchars($q) ?>"</b>
        <?php if($filter !== 'all'): ?><span class="filter-info">(Lọc: <?= htmlspecialchars($filter) ?>)</span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if(empty($results)): ?>
      <div class="empty-results">
        <i class="fas fa-search"></i>
        <h3>Không tìm thấy kết quả</h3>
        <p>Thử tìm kiếm với từ khóa khác</p>
      </div>
    <?php else: ?>
      <div class="songs-grid">
        <?php foreach($results as $song):
          $cover = build_cover_url($song['image_url'] ?? '');
          $album = $song['album_title'] ?? ''; 
          $song_img_full = !empty($song['image_url']) ? SITE_URL . $song['image_url'] : SITE_URL . '/assets/images/default-cover.jpg';
        ?>
          <div class="song-card" 
               data-song-id="<?= (int)$song['id'] ?>"
               data-title="<?= htmlspecialchars($song['title']) ?>"
               data-artist="<?= htmlspecialchars($song['artist_name'] ?? 'Unknown') ?>"
               data-image="<?= $song_img_full ?>"
          >
            <div class="song-image">
              <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($song['title']) ?>">
              <div class="play-overlay">
                <button class="play-btn" data-song-id="<?= (int)$song['id'] ?>">
                  <i class="fas fa-play"></i>
                </button>
              </div>
            </div>
            <div class="song-info">
              <h3 class="song-title">
                <a href="player.php?id=<?= (int)$song['id'] ?>">
                  <?= htmlspecialchars($song['title']) ?>
                </a>
              </h3>
              <p class="song-artist">
                <a href="artist.php?id=<?= $song['artist_id'] ?>">
                  <?= htmlspecialchars($song['artist_name'] ?? 'Unknown') ?>
                </a>
              </p>
              <?php if($album): ?>
                <p class="song-artist" style="font-size:13px;margin-top:2px;">
                  Album: <?= htmlspecialchars($album) ?>
                </p>
              <?php endif; ?>

              <div class="song-stats">
                <span>
                  <i class="fas fa-play-circle"></i>
                  <?= number_format((int)($song['play_count'] ?? 0)) ?>
                </span>

                <button class="btn-favorite" onclick="toggleFavorite(<?= $song['id'] ?>)">
                  <?php 
                      // Kiểm tra bài này có trong danh sách like không
                      $is_liked = in_array($song['id'], $liked_songs); 
                  ?>
                  <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart" 
                    style="<?php echo $is_liked ? 'color: red;' : ''; ?>"></i>
              </button>

              <button type="button" 
                      onclick="event.stopPropagation(); openAddToPlaylistModal(<?= $song['id'] ?>)" 
                      title="Thêm vào Playlist"
                      style="background:transparent; border:none; color:#b3b3b3; cursor:pointer; margin-left:10px; font-size: 16px;">
                  <i class="fas fa-plus-circle"></i>
              </button>





              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php 
            // Hàm tạo URL giữ nguyên tham số tìm kiếm
            function get_page_url($p, $q, $filter) {
                return "search.php?q=" . urlencode($q) . "&filter=" . urlencode($filter) . "&page=" . $p;
            }
            
            // Nút Previous
            if ($page > 1) {
                echo '<a href="' . get_page_url($page - 1, $q, $filter) . '" class="page-link"><i class="fas fa-chevron-left"></i></a>';
            } else {
                echo '<span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>';
            }

            // Hiển thị các số trang (Logic hiển thị gọn: 1 ... 4 5 6 ... 10)
            $range = 2; // Số trang hiện xung quanh trang hiện tại
            
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<a href="' . get_page_url($i, $q, $filter) . '" class="page-link ' . $active . '">' . $i . '</a>';
                } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                    echo '<span class="page-link disabled">...</span>';
                }
            }

            // Nút Next
            if ($page < $total_pages) {
                echo '<a href="' . get_page_url($page + 1, $q, $filter) . '" class="page-link"><i class="fas fa-chevron-right"></i></a>';
            } else {
                echo '<span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>';
            }
        ?>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<script>
(function(){
  const input = document.getElementById('searchInput');
  const list  = document.getElementById('suggestList');
  let timer   = null;
  function hide(){ list.style.display = 'none'; list.innerHTML = ''; }
  function show(items){
    if(!items || items.length === 0){ hide(); return; }
    list.innerHTML = items.map(x => `
      <div class="suggest-item" data-label="${(x.label||'').replaceAll('"','&quot;')}" data-type="${x.type}" data-id="${x.id||''}">
        <div class="suggest-item-title">${x.label}</div>
        <div class="suggest-item-type">${x.type==='song'?'🎵 Bài hát':(x.type==='artist'?'👤 Nghệ sĩ':'💿 Album')}</div>
      </div>`).join('');
    list.style.display = 'block';
  }
  input.addEventListener('input', () => {
    const term = input.value.trim();
    clearTimeout(timer);
    if(term.length < 2){ hide(); return; }
    timer = setTimeout(async () => {
      try{
        const res = await fetch(`search.php?ajax=1&term=${encodeURIComponent(term)}`);
        show(await res.json());
      }catch(e){ hide(); }
    }, 250);
  });
  list.addEventListener('click', (e) => {
    const item = e.target.closest('.suggest-item');
    if(!item) return;
    input.value = item.dataset.label;
    hide();
    if(item.dataset.type === 'song' && item.dataset.id && window.playSong) window.playSong(item.dataset.id);
    if(input.form) input.form.submit();
  });
  document.addEventListener('click', (e) => { if(!list.contains(e.target) && e.target !== input) hide(); });
})();

// Click card logic
(function(){
  document.querySelectorAll('.song-card').forEach(card => {
    const id = card.dataset.songId;
    if(!id) return;
    const playBtn = card.querySelector('.play-btn');
    if(playBtn) {
      playBtn.addEventListener('click', function(e){
        e.stopPropagation(); e.preventDefault();
        window.playSong ? window.playSong(id) : (window.location.href = 'player.php?id=' + encodeURIComponent(id));
      });
    }
    card.addEventListener('click', function(e){
      const link = e.target.closest('a');
      if(link && !e.ctrlKey) { e.preventDefault(); if(window.playSong) window.playSong(id); }
    });
  });
})();
</script>

<?php require_once 'includes/footer.php'; ?>