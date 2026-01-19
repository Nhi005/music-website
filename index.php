<?php
$page_title = "Trang chủ";
require_once 'includes/header.php';

// Lấy dữ liệu từ database
$db = getDB();
$liked_songs = [];
if (isset($_SESSION['user_id'])) {
    try {
        $u_id = $_SESSION['user_id'];
        $stmt = $db->query("SELECT song_id FROM favorites WHERE user_id = $u_id");
        $liked_songs = $stmt->fetchAll(PDO::FETCH_COLUMN); // Kết quả sẽ là mảng: [1, 5, 9...]
    } catch(Exception $e) { }
}



// Bài hát trending
try {
    $trending_songs = $db->query("SELECT s.*, a.name as artist_name 
                                   FROM songs s 
                                   LEFT JOIN artists a ON s.artist_id = a.id 
                                   ORDER BY s.play_count DESC 
                                   LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $trending_songs = [];
}

// Phát hành mới
try {
    $new_releases = $db->query("SELECT s.*, a.name as artist_name 
                                FROM songs s 
                                LEFT JOIN artists a ON s.artist_id = a.id 
                                ORDER BY s.created_at DESC 
                                LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $new_releases = [];
}

// Nghệ sĩ nổi bật
try {
    $popular_artists = $db->query("SELECT * FROM artists ORDER BY RAND() LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $popular_artists = [];
}

//Album
try {
    $latest_albums = $db->query("SELECT al.*, a.name as artist_name 
                                 FROM albums al 
                                 LEFT JOIN artists a ON al.artist_id = a.id 
                                 ORDER BY al.created_at DESC 
                                 LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $latest_albums = [];
}



?>


<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Âm nhạc là <span class="highlight">cuộc sống</span></h1>
            <p>Khám phá hàng triệu bài hát từ các nghệ sĩ yêu thích của bạn</p>
            <?php if(!isLoggedIn()): ?>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary btn-large">Bắt đầu ngay - Miễn phí</a>
                <a href="search.php" class="btn btn-outline btn-large">Khám phá ngay</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Trending Songs -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-fire"></i> Trending</h2>
            <a href="search.php?filter=trending" class="see-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="songs-grid">
            <?php if(empty($trending_songs)): ?>
                <p class="text-muted">Chưa có bài hát nào. Vui lòng thêm dữ liệu vào database.</p>
            <?php else: ?>
                <?php foreach($trending_songs as $song): ?>
                <div class="song-card" data-song-id="<?php echo $song['id']; ?>">

                    <div class="song-image">
                        <img src="<?php 
                            echo !empty($song['image_url']) 
                                ? BASE_URL . $song['image_url'] 
                                : BASE_URL . '/assets/images/default-cover.jpg';
                        ?>" 
                        alt="<?php echo htmlspecialchars($song['title']); ?>">

                        <div class="play-overlay">
                            <button class="play-btn" onclick="playSong(<?php echo $song['id']; ?>)">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="song-info">
                        <h3 class="song-title">
                            <a href="player.php?id=<?php echo $song['id']; ?>"><?php echo htmlspecialchars($song['title']); ?></a>
                        </h3>
                        <p class="song-artist">
                            <a href="artist.php?id=<?php echo $song['artist_id']; ?>"><?php echo htmlspecialchars($song['artist_name']); ?></a>
                        </p>
                        <div class="song-stats">
                            <span><i class="fas fa-play-circle"></i> <?php echo number_format($song['play_count']); ?></span>

                            <button class="btn-favorite" onclick="toggleFavorite(<?php echo $song['id']; ?>)">
                                <?php 
                                    // Kiểm tra xem bài hát này có trong danh sách đã like không
                                    $is_liked = in_array($song['id'], $liked_songs); 
                                ?>
                                <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart" 
                                style="<?php echo $is_liked ? 'color: red;' : ''; ?>"></i>
                            </button>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- New Releases -->
<section class="section bg-light">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-compact-disc"></i> Mới phát hành</h2>
            <a href="search.php?filter=new" class="see-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="songs-list">
            <?php if(empty($new_releases)): ?>
                <p class="text-muted">Chưa có bài hát mới.</p>
            <?php else: ?>
                <?php foreach(array_slice($new_releases, 0, 8) as $index => $song): ?>
                <div class="song-row" data-song-id="<?php echo $song['id']; ?>">
                    <div class="song-number"><?php echo $index + 1; ?></div>
                    <div class="song-thumbnail">
                        <img src="<?php 
                        echo !empty($song['image_url']) 
                            ? BASE_URL . $song['image_url'] 
                            : BASE_URL . '/assets/images/default-cover.jpg';
                        ?>" 
                        alt="<?php echo htmlspecialchars($song['title']); ?>">

                        <button class="play-btn-small" onclick="playSong(<?php echo $song['id']; ?>)">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                    <div class="song-details">
                        <h4><?php echo htmlspecialchars($song['title']); ?></h4>
                        <p><?php echo htmlspecialchars($song['artist_name']); ?></p>
                    </div>
                    <div class="song-duration"><?php echo $song['duration']; ?></div>
                    
                    <div class="song-actions">
                        <button onclick="addToPlaylist(<?php echo $song['id']; ?>)" title="Thêm vào playlist">
                            <i class="fas fa-plus"></i>
                        </button>
                        
                        <button onclick="toggleFavorite(<?php echo $song['id']; ?>)" title="Yêu thích">
                            <?php $is_liked = in_array($song['id'], $liked_songs); ?>
                            
                            <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart" 
                            style="<?php echo $is_liked ? 'color: red;' : ''; ?>"></i>
                        </button>
                    </div>


                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Popular Artists -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-star"></i> Nghệ sĩ nổi bật</h2>
            <a href="artists.php" class="see-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <div class="artists-grid">
            <?php if(empty($popular_artists)): ?>
                <p class="text-muted">Chưa có nghệ sĩ nào.</p>
            <?php else: ?>
                <?php foreach($popular_artists as $artist): ?>
                <div class="artist-card">
                    <a href="artist.php?id=<?php echo $artist['id']; ?>">
                        <div class="artist-image">
                             <img src="<?php 
                                    echo !empty($artist['avatar']) 
                                        ? BASE_URL . $artist['avatar'] 
                                        : BASE_URL . '/assets/images/default-artist.jpg';
                                ?>" 
                                 alt="<?php echo htmlspecialchars($artist['name']); ?>">
                        </div>
                        <div class="artist-name">
                            <h3><?php echo htmlspecialchars($artist['name']); ?></h3>
                            <p><?php echo htmlspecialchars($artist['country']); ?></p>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Album Hot -->
<section class="section bg-light">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-compact-disc"></i> Album Hot</h2>
            <a href="albums.php" class="see-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <?php if(empty($latest_albums)): ?>
            <p class="text-muted">Chưa có album nào.</p>
        <?php else: ?>


            <div class="songs-grid">
                <?php foreach($latest_albums as $album): 
                    $album_img = !empty($album['cover_url']) 
                        ? BASE_URL . $album['cover_url'] 
                        : BASE_URL . '/assets/images/default-cover.jpg';
                    
                    // Tạo link sẵn
                    $album_link = "album.php?id=" . $album['id'];
                ?>
                
                <div class="song-card">
                    <div class="song-image">
                        <img src="<?php echo $album_img; ?>" alt="<?php echo htmlspecialchars($album['title']); ?>">
                        
                        <a href="<?php echo $album_link; ?>" class="play-overlay" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
                            <span class="play-btn" style="display:flex; align-items:center; justify-content:center; pointer-events: none;">
                                <i class="fas fa-play"></i>
                            </span>
                        </a>
                    </div>
                    
                    <div class="song-info">
                        <h3 class="song-title">
                            <a href="<?php echo $album_link; ?>">
                                <?php echo htmlspecialchars($album['title']); ?>
                            </a>
                        </h3>
                        <p class="song-artist">
                            <?php echo htmlspecialchars($album['artist_name']); ?>
                        </p>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>


        <?php endif; ?>
    </div>
</section>



<!-- Call to Action -->
<?php if(!isLoggedIn()): ?>
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Sẵn sàng bắt đầu?</h2>
            <p>Tham gia cộng đồng âm nhạc lớn nhất Việt Nam ngay hôm nay</p>
            <a href="register.php" class="btn btn-white btn-large">Đăng ký miễn phí</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>