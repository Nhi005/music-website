</main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Nền tảng nghe nhạc trực tuyến hàng đầu Việt Nam</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Khám phá</h4>
                    <ul>
                        <li><a href="#">BXH Nhạc Mới</a></li>
                        <li><a href="#">Chủ đề & Thể loại</a></li>
                        <li><a href="#">Top 100</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Thông tin</h4>
                    <ul>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#">Liên hệ</a></li>
                        <li><a href="#">Điều khoản sử dụng</a></li>
                        <li><a href="#">Chính sách bảo mật</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Hỗ trợ</h4>
                    <ul>
                        <li><a href="#">Trung tâm trợ giúp</a></li>
                        <li><a href="#">Báo lỗi</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <div id="addToPlaylistModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center;">
        <div style="background: #282828; padding: 25px; border-radius: 8px; width: 350px; max-width: 90%; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #3e3e3e; padding-bottom: 10px;">
                <i class="fas fa-list"></i> Thêm vào Playlist
            </h3>
            
            <p style="font-size: 14px; color: #b3b3b3; margin-bottom: 5px;">Chọn Playlist của bạn:</p>
            
            <select id="playlistSelect" style="width: 100%; padding: 12px; margin-bottom: 20px; background: #3e3e3e; color: white; border: 1px solid #555; border-radius: 4px; outline: none;">
                <option value="">Đang tải...</option>
            </select>

            <input type="hidden" id="selectedSongIdForPlaylist">

            <div style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button onclick="closeAddModal()" style="background: transparent; color: #b3b3b3; border: 1px solid #555; padding: 8px 16px; cursor: pointer; border-radius: 20px; transition: 0.2s;">Hủy</button>
                <button onclick="confirmAddToPlaylist()" style="background: #1db954; color: white; border: none; padding: 8px 24px; border-radius: 20px; font-weight: bold; cursor: pointer; transition: 0.2s;">Lưu</button>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/player.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

    <script>
    // 1. Hàm mở Modal (Gọi hàm này khi bấm nút + ở bài hát)
    function openAddToPlaylistModal(songId) {
        const modal = document.getElementById('addToPlaylistModal');
        const select = document.getElementById('playlistSelect');
        
        // Lưu song_id vào input ẩn để lát nữa dùng
        document.getElementById('selectedSongIdForPlaylist').value = songId;
        
        // Hiện modal
        modal.style.display = 'flex';
        select.innerHTML = '<option>Đang tải danh sách...</option>';

        // Gọi API lấy danh sách playlist
        // Lưu ý: api/playlists.php mặc định (không có action) sẽ trả về danh sách playlist
        fetch('<?php echo SITE_URL; ?>/admin/api/playlists.php') 
            .then(res => res.json())
            .then(data => {
                if (data.success && data.playlists && data.playlists.length > 0) {
                    let html = '';
                    data.playlists.forEach(pl => {
                        html += `<option value="${pl.id}">${pl.name}</option>`;
                    });
                    select.innerHTML = html;
                } else {
                    select.innerHTML = '<option value="">Bạn chưa tạo Playlist nào</option>';
                }
            })
            .catch(err => {
                console.error(err);
                // Thử lại với đường dẫn tương đối nếu đường dẫn tuyệt đối lỗi
                fetch('api/playlists.php')
                .then(res => res.json())
                .then(data => {
                     if (data.success && data.playlists.length > 0) {
                        let html = '';
                        data.playlists.forEach(pl => html += `<option value="${pl.id}">${pl.name}</option>`);
                        select.innerHTML = html;
                     }
                });
            });
    }

    // 2. Hàm tắt Modal
    function closeAddModal() {
        document.getElementById('addToPlaylistModal').style.display = 'none';
    }

    // 3. Hàm gửi yêu cầu thêm bài hát
    function confirmAddToPlaylist() {
        const playlistId = document.getElementById('playlistSelect').value;
        const songId = document.getElementById('selectedSongIdForPlaylist').value;

        if (!playlistId) {
            alert('Vui lòng chọn hoặc tạo mới một playlist trước!');
            return;
        }

        let formData = new FormData();
        formData.append('playlist_id', playlistId);
        formData.append('song_id', songId);

        // Gọi API add_song
        // Chúng ta thử 2 đường dẫn phổ biến để tránh lỗi 404
        let apiUrl = '<?php echo SITE_URL; ?>/admin/api/playlists.php?action=add_song';
        
        // Nếu đang ở thư mục gốc mà gọi vào admin/api bị lỗi thì dùng đường dẫn tương đối
        // Tuy nhiên tốt nhất bạn nên check xem file api/playlists.php của bạn nằm chính xác ở đâu.
        // Dựa trên code cũ bạn gửi, nó nằm ở thư mục api ngang hàng với includes hay nằm trong admin?
        // Giả sử cấu trúc là: root/api/playlists.php (dựa trên context cũ)
        
        fetch('api/playlists.php?action=add_song', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                closeAddModal();
            }
        })
        .catch(err => {
            console.error('Lỗi:', err);
            alert('Có lỗi xảy ra khi kết nối tới server.');
        });
    }

    // Đóng modal khi click ra ngoài vùng trắng
    window.onclick = function(event) {
        const modal = document.getElementById('addToPlaylistModal');
        if (event.target == modal) {
            closeAddModal();
        }
    }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const baseUrl = '<?php echo SITE_URL; ?>';

        function openFullPlayer(e) {
            e.preventDefault();
            if (!window.musicPlayer || !window.musicPlayer.currentSong) return;

            const songId = window.musicPlayer.currentSong.id;
            if (!songId) return;

            if (typeof window.musicPlayer.saveState === 'function') {
                window.musicPlayer.saveState();
            }

            window.location.href = baseUrl + '/player.php?id=' + songId;
        }

        document.addEventListener('click', function (e) {
            const titleEl = e.target.closest('#miniPlayerTitle');
            if (titleEl) {
                openFullPlayer(e);
            }
        });

        const style = document.createElement('style');
        style.textContent = `
            #miniPlayerTitle {
                cursor: pointer;
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);
    });
    </script>

</body>
</html>