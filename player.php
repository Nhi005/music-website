<?php 
// player.php
require_once 'includes/config.php';
$page_title = "Nghe nhạc";
require_once 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

// Lấy id bài hát từ URL (khi click từ mini-player sẽ có ?id=...)
$song_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin bài hát hiện tại
$current_song = null;
if ($song_id > 0) {
    $stmt = $db->prepare("
        SELECT s.*, a.name AS artist_name, a.avatar AS artist_avatar
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        WHERE s.id = ?
    ");
    $stmt->execute([$song_id]);
    $current_song = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Nếu không có id hoặc id sai => lấy bài đầu tiên
if (!$current_song) {
    $stmt = $db->query("
        SELECT s.*, a.name AS artist_name, a.avatar AS artist_avatar
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        ORDER BY s.id ASC
        LIMIT 1
    ");
    $current_song = $stmt->fetch(PDO::FETCH_ASSOC);
    $song_id = $current_song ? (int)$current_song['id'] : 0;
}

// Lấy queue: một số bài khác để liệt kê bên phải
$queue = [];
if ($current_song) {
    $stmt = $db->prepare("
        SELECT s.*, a.name AS artist_name, a.avatar AS artist_avatar
        FROM songs s
        LEFT JOIN artists a ON s.artist_id = a.id
        WHERE s.id <> ?
        ORDER BY s.play_count DESC
        LIMIT 20
    ");
    $stmt->execute([$song_id]);
    $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Chuẩn bị dữ liệu cho JS (bài hát đang xem)
$js_song = null;
if ($current_song) {
    $cover = $current_song['image_url'] ?: 'assets/images/default-cover.jpg';
    if (!preg_match('~^https?://~', $cover)) {
        $cover = SITE_URL . '/' . ltrim($cover, '/');
    }

    $file = $current_song['file_url'];
    if ($file && !preg_match('~^https?://~', $file)) {
        $file = SITE_URL . '/' . ltrim($file, '/');
    }

    $js_song = [
        'id'          => (int)$current_song['id'],
        'title'       => $current_song['title'],
        'artist_name' => $current_song['artist_name'] ?? '',
        'image_url'   => $cover,
        'file_url'    => $file,
    ];
}
?>

<style>
/* ====== LAYOUT PLAYER PAGE ====== */
.player-wrapper {
    max-width: 1200px;
    margin: 24px auto 90px;
    display: grid;
    grid-template-columns: 2fr 1.4fr;
    gap: 24px;
}

/* Bên trái: player lớn */
.player-main {
    background: #111;
    border-radius: 18px;
    padding: 24px 26px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.6);
}

.player-main-header {
    display: flex;
    gap: 22px;
    align-items: center;
}

.player-main-cover img {
    width: 220px;
    height: 220px;
    object-fit: cover;
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.7);
}

.player-main-meta h1 {
    font-size: 26px;
    margin-bottom: 6px;
}

.player-main-meta .artist {
    color: #b3b3b3;
    margin-bottom: 10px;
}

.player-main-actions {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.player-main-actions button {
    border-radius: 999px;
    border: none;
    padding: 6px 14px;
    font-size: 13px;
    cursor: pointer;
    background: #222;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.player-main-actions button:hover {
    background: #333;
}

/* Lyrics */
.player-lyrics {
    margin-top: 20px;
    padding: 14px 16px;
    background:#0d0d0d;
    border-radius: 14px;
    max-height: 260px;
    overflow-y: auto;
    font-size: 14px;
    line-height: 1.5;
    color: #ddd;
}

/* Bên phải: queue */
.player-queue {
    background:#111;
    border-radius:18px;
    padding:18px 18px 8px;
    box-shadow:0 10px 30px rgba(0,0,0,0.45);
    max-height: calc(100vh - 140px);
    overflow-y:auto;
}

.player-queue h3 {
    margin-bottom:12px;
}

.queue-item {
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px;
    border-radius:10px;
    cursor:pointer;
    transition:0.2s;
}

.queue-item:hover {
    background:#1a1a1a;
}

.queue-item.active {
    background:rgba(29,185,84,0.18);
    border-left:3px solid #1db954;
}

.queue-item img {
    width:46px;
    height:46px;
    border-radius:6px;
    object-fit:cover;
}

.queue-info {
    flex:1;
    min-width:0;
}

.queue-title {
    font-size:14px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.queue-artist {
    font-size:12px;
    color:#aaa;
}

.queue-index {
    font-size:12px;
    color:#777;
    width:18px;
    text-align:right;
}

/* Responsive */
@media (max-width: 900px) {
    .player-wrapper {
        grid-template-columns: 1fr;
    }
    .player-main-header {
        flex-direction: column;
        align-items:flex-start;
    }
    .player-main-cover img {
        width: 100%;
        max-width: 280px;
        height: auto;
    }
}
</style>

<section class="section">
    <div class="container">

        <?php if (!$current_song): ?>
            <p class="text-muted">Không tìm thấy bài hát.</p>
        <?php else: ?>

        <div class="player-wrapper"
             id="playerPage"
             data-song='<?php echo json_encode($js_song, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'>

            <!-- PLAYER LỚN BÊN TRÁI -->
            <div class="player-main">
                <div class="player-main-header">
                    <div class="player-main-cover">
                        <img id="bigCover"
                             src="<?php echo htmlspecialchars($js_song['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($current_song['title']); ?>">
                    </div>
                    <div class="player-main-meta">
                        <div style="font-size:13px;color:#b3b3b3;margin-bottom:4px;">Bài hát</div>
                        <h1 id="bigTitle"><?php echo htmlspecialchars($current_song['title']); ?></h1>
                        <div class="artist" id="bigArtist">
                            <?php echo htmlspecialchars($current_song['artist_name'] ?? 'Unknown artist'); ?>
                        </div>

                        <div class="player-main-actions">
                            <button type="button">
                                <i class="fas fa-heart"></i> Yêu thích
                            </button>
                            <button type="button">
                                <i class="fas fa-share"></i> Chia sẻ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lyrics -->
                <div class="player-lyrics" id="lyricsBox">
                    <b>Lời bài hát</b><br><br>
                    <?php if (!empty($current_song['lyrics'])): ?>
                        <?php echo nl2br(htmlspecialchars($current_song['lyrics'])); ?>
                    <?php else: ?>
                        <p>Đang cập nhật lời bài hát cho <i><?php echo htmlspecialchars($current_song['title']); ?></i>.</p>
                        <p>Nếu muốn, bạn có thể thêm cột <code>lyrics</code> vào bảng <code>songs</code> và hiển thị nội dung ở đây.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- QUEUE BÊN PHẢI -->
            <aside class="player-queue">
                <h3>Danh sách phát</h3>
                <div id="queueList">
                    <?php
                    $i = 1;

                    // helper build URL
                    $buildUrl = function ($path, $default) {
                        if (!$path || trim($path) === '') $path = $default;
                        if (preg_match('~^https?://~', $path)) return $path;
                        return SITE_URL . '/' . ltrim($path, '/');
                    };

                    // Bài hiện tại trước
                    $cover  = $buildUrl($current_song['image_url'] ?? '', 'assets/images/default-cover.jpg');
                    $file   = $buildUrl($current_song['file_url'] ?? '', '');
                    $lyrics = $current_song['lyrics'] ?? '';
                    ?>
                    <div class="queue-item active"
                         data-id="<?php echo (int)$js_song['id']; ?>"
                         data-title="<?php echo htmlspecialchars($js_song['title'], ENT_QUOTES); ?>"
                         data-artist="<?php echo htmlspecialchars($js_song['artist_name'], ENT_QUOTES); ?>"
                         data-image="<?php echo htmlspecialchars($cover, ENT_QUOTES); ?>"
                         data-file="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>"
                         data-lyrics="<?php echo htmlspecialchars($lyrics, ENT_QUOTES); ?>">
                        <div class="queue-index"><?php echo $i++; ?></div>
                        <img src="<?php echo htmlspecialchars($cover); ?>" alt="">
                        <div class="queue-info">
                            <div class="queue-title"><?php echo htmlspecialchars($js_song['title']); ?></div>
                            <div class="queue-artist"><?php echo htmlspecialchars($js_song['artist_name']); ?></div>
                        </div>
                    </div>

                    <?php foreach ($queue as $row): 
                        $cover  = $buildUrl($row['image_url'] ?? '', 'assets/images/default-cover.jpg');
                        $file   = $buildUrl($row['file_url'] ?? '', '');
                        $lyrics = $row['lyrics'] ?? '';
                    ?>
                        <div class="queue-item"
                             data-id="<?php echo (int)$row['id']; ?>"
                             data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>"
                             data-artist="<?php echo htmlspecialchars($row['artist_name'] ?? '', ENT_QUOTES); ?>"
                             data-image="<?php echo htmlspecialchars($cover, ENT_QUOTES); ?>"
                             data-file="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>"
                             data-lyrics="<?php echo htmlspecialchars($lyrics, ENT_QUOTES); ?>">
                            <div class="queue-index"><?php echo $i++; ?></div>
                            <img src="<?php echo htmlspecialchars($cover); ?>" alt="">
                            <div class="queue-info">
                                <div class="queue-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                <div class="queue-artist"><?php echo htmlspecialchars($row['artist_name'] ?? ''); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>

        <?php endif; ?>
    </div>
</section>

<script>
// ====== JS cho trang player.php (sử dụng lại window.musicPlayer) ======
document.addEventListener('DOMContentLoaded', function () {
    const pageEl = document.getElementById('playerPage');
    if (!pageEl) return;

    const songFromPhp = pageEl.dataset.song ? JSON.parse(pageEl.dataset.song) : null;

    const bigTitle  = document.getElementById('bigTitle');
    const bigArtist = document.getElementById('bigArtist');
    const bigCover  = document.getElementById('bigCover');
    const lyricsBox = document.getElementById('lyricsBox');
    const queueList = document.getElementById('queueList');

    function waitForPlayer(callback) {
        if (window.musicPlayer && window.musicPlayer.audio) {
            callback(window.musicPlayer);
        } else {
            setTimeout(() => waitForPlayer(callback), 80);
        }
    }

    waitForPlayer(function (mp) {
        // Nếu player chưa có bài hoặc khác bài trong URL thì load bài từ PHP
        if (songFromPhp && (!mp.currentSong || mp.currentSong.id != songFromPhp.id)) {
            mp.loadSong(songFromPhp);
            mp.play();
        }

        function syncBigUI(song) {
            song = song || mp.currentSong || songFromPhp;
            if (!song) return;

            bigTitle.textContent  = song.title || 'Unknown Song';
            bigArtist.textContent = song.artist_name || 'Unknown Artist';
            if (song.image_url) {
                bigCover.src = song.image_url;
            }

            // Active queue
            document.querySelectorAll('.queue-item').forEach(el => {
                const sid = el.dataset.id;
                el.classList.toggle('active', sid && song.id && String(sid) === String(song.id));
            });
        }

        syncBigUI();

        // Click vào từng bài trong queue
        if (queueList) {
            queueList.addEventListener('click', function (e) {
                const item = e.target.closest('.queue-item');
                if (!item) return;

                const song = {
                    id: parseInt(item.dataset.id),
                    title: item.dataset.title || 'Unknown Song',
                    artist_name: item.dataset.artist || 'Unknown Artist',
                    image_url: item.dataset.image || '',
                    file_url: item.dataset.file || ''
                };

                // Đổi giao diện bên trái
                bigTitle.textContent  = song.title;
                bigArtist.textContent = song.artist_name;
                if (song.image_url) bigCover.src = song.image_url;

                // Lyrics
                const rawLyrics = item.dataset.lyrics || '';
                if (rawLyrics.trim() !== '') {
                    const safe = rawLyrics
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;')
                        .replace(/\n/g, '<br>');
                    lyricsBox.innerHTML = '<b>Lời bài hát</b><br><br>' + safe;
                } else {
                    lyricsBox.innerHTML =
                        '<b>Lời bài hát</b><br><br>' +
                        '<p>Đang cập nhật lời bài hát cho <i>' + song.title + '</i>.</p>';
                }

                // Active class
                document.querySelectorAll('.queue-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');

                // Gọi mini-player phát nhạc
                if (window.musicPlayer) {
                    mp.loadSong(song);   // truyền đầy đủ dữ liệu => không còn "Unknown Song"
                    mp.play();
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>