// ==========================================
// MAIN.JS - MUSIC WEBSITE
// ==========================================

const API_BASE = window.location.origin + '/music-website';

// ==========================================
// MUSIC PLAYER
// ==========================================
class MusicPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentSong = null;
        this.playlist = [];
        this.currentIndex = 0;
        this.isPlaying = false;
        this.init();
    }

    init() {
        // Event listeners
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.next());
        this.audio.addEventListener('loadedmetadata', () => this.updateDuration());
    }

    play(songId) {
        fetch(`${API_BASE}/api/songs.php?id=${songId}`)
            .then(res => res.json())
            .then(song => {
                this.currentSong = song;
                this.audio.src = song.file_url;
                this.audio.play();
                this.isPlaying = true;
                this.updateUI();
                this.trackPlay(songId);
            })
            .catch(err => console.error('Error loading song:', err));
    }

    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.updateUI();
    }

    resume() {
        this.audio.play();
        this.isPlaying = true;
        this.updateUI();
    }

    next() {
        if (this.playlist.length > 0) {
            this.currentIndex = (this.currentIndex + 1) % this.playlist.length;
            this.play(this.playlist[this.currentIndex]);
        }
    }

    previous() {
        if (this.playlist.length > 0) {
            this.currentIndex = (this.currentIndex - 1 + this.playlist.length) % this.playlist.length;
            this.play(this.playlist[this.currentIndex]);
        }
    }

    seek(time) {
        this.audio.currentTime = time;
    }

    setVolume(volume) {
        this.audio.volume = volume / 100;
    }

    updateProgress() {
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        const currentTime = this.formatTime(this.audio.currentTime);
        
        // Update progress bar if exists
        const progressBar = document.querySelector('.progress-bar');
        const currentTimeEl = document.querySelector('.current-time');
        
        if (progressBar) progressBar.style.width = progress + '%';
        if (currentTimeEl) currentTimeEl.textContent = currentTime;
    }

    updateDuration() {
        const duration = this.formatTime(this.audio.duration);
        const durationEl = document.querySelector('.duration');
        if (durationEl) durationEl.textContent = duration;
    }

    updateUI() {
        const playBtn = document.querySelector('.play-pause-btn');
        if (playBtn) {
            playBtn.innerHTML = this.isPlaying 
                ? '<i class="fas fa-pause"></i>' 
                : '<i class="fas fa-play"></i>';
        }

        if (this.currentSong) {
            const titleEl = document.querySelector('.player-song-title');
            const artistEl = document.querySelector('.player-song-artist');
            const imageEl = document.querySelector('.player-song-image');
            
            if (titleEl) titleEl.textContent = this.currentSong.title;
            if (artistEl) artistEl.textContent = this.currentSong.artist_name;
            if (imageEl) imageEl.src = this.currentSong.image_url;
        }
    }

    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    trackPlay(songId) {
        fetch(`${API_BASE}/api/track-play.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({song_id: songId})
        });
    }
}

// Initialize global player
const player = new MusicPlayer();

// ==========================================
// PLAY SONG
// ==========================================
function playSong(songId) {
    player.play(songId);
}




// ==========================================
// TOGGLE FAVORITE 
// ==========================================
function toggleFavorite(songId) {
    // Kiểm tra login (nếu backend trả về lỗi chưa login)
    fetch(`${API_BASE}/api/favorites.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({song_id: songId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Tìm nút bấm tương ứng trong giao diện để đổi màu
            // Lưu ý: Dùng querySelectorAll vì 1 bài hát có thể xuất hiện ở nhiều chỗ
            const btns = document.querySelectorAll(`button[onclick="toggleFavorite(${songId})"]`);
            
            btns.forEach(btn => {
                const icon = btn.querySelector('i');
                if (data.action === 'added') {
                    // Đã thêm: Tim đặc (fas), màu đỏ
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = 'red'; // Sửa màu tại đây
                } else {
                    // Đã xóa: Tim rỗng (far), bỏ màu
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = ''; 
                }
            });
            
            // showNotification(data.message); // Có thể bỏ nếu thấy phiền
        } else {
            showNotification(data.message, 'error');
            // Nếu chưa đăng nhập, có thể chuyển hướng:
            if(data.message.includes('đăng nhập')) window.location.href = 'login.php';
        }
    })
    .catch(err => console.error('Error toggling favorite:', err));
}



// ==========================================
// ADD TO PLAYLIST
// ==========================================
function addToPlaylist(songId) {
    // Show playlist modal
    showPlaylistModal(songId);
}

function showPlaylistModal(songId) {
    // Fetch user playlists
    fetch(`${API_BASE}/api/playlists.php`)
        .then(res => res.json())
        .then(playlists => {
            let html = `
                <div class="modal" id="playlistModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Thêm vào Playlist</h3>
                            <button onclick="closeModal('playlistModal')">&times;</button>
                        </div>
                        <div class="modal-body">
            `;
            
            if (playlists.length > 0) {
                playlists.forEach(playlist => {
                    html += `
                        <div class="playlist-item" onclick="addSongToPlaylist(${playlist.id}, ${songId})">
                            <i class="fas fa-list"></i>
                            <span>${playlist.name}</span>
                        </div>
                    `;
                });
            } else {
                html += '<p>Bạn chưa có playlist nào.</p>';
            }
            
            html += `
                        <button class="btn btn-primary" onclick="createNewPlaylist(${songId})">
                            <i class="fas fa-plus"></i> Tạo playlist mới
                        </button>
                    </div>
                </div>
            </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', html);
        });
}

function addSongToPlaylist(playlistId, songId) {
    fetch(`${API_BASE}/api/playlist-songs.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            playlist_id: playlistId,
            song_id: songId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Đã thêm vào playlist');
            closeModal('playlistModal');
        }
    });
}

// ==========================================
// SEARCH
// ==========================================
let searchTimeout;
function handleSearch(query) {
    clearTimeout(searchTimeout);
    
    if (query.length < 2) return;
    
    searchTimeout = setTimeout(() => {
        fetch(`${API_BASE}/api/search.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(results => {
                displaySearchResults(results);
            });
    }, 300);
}

function displaySearchResults(results) {
    const container = document.querySelector('.search-results');
    if (!container) return;
    
    let html = '';
    
    if (results.songs && results.songs.length > 0) {
        html += '<h3>Bài hát</h3><div class="songs-grid">';
        results.songs.forEach(song => {
            html += `
                <div class="song-card">
                    <div class="song-image">
                        <img src="${song.image_url}" alt="${song.title}">
                        <div class="play-overlay">
                            <button class="play-btn" onclick="playSong(${song.id})">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="song-info">
                        <h3>${song.title}</h3>
                        <p>${song.artist_name}</p>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    }
    
    if (results.artists && results.artists.length > 0) {
        html += '<h3>Nghệ sĩ</h3><div class="artists-grid">';
        results.artists.forEach(artist => {
            html += `
                <div class="artist-card">
                    <a href="artist.php?id=${artist.id}">
                        <img src="${artist.avatar}" alt="${artist.name}">
                        <h4>${artist.name}</h4>
                    </a>
                </div>
            `;
        });
        html += '</div>';
    }
    
    container.innerHTML = html || '<p>Không tìm thấy kết quả</p>';
}

// ==========================================
// NOTIFICATIONS
// ==========================================
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ==========================================
// MODAL
// ==========================================
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.remove();
}

// ==========================================
// ADMIN - MENU TOGGLE
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
});

// ==========================================
// FORM VALIDATION
// ==========================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// ==========================================
// FILE UPLOAD PREVIEW
// ==========================================
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==========================================
// AJAX FORM SUBMIT
// ==========================================
function submitAjaxForm(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Thành công!');
            if (successCallback) successCallback(data);
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

// ==========================================
// CONFIRM DELETE
// ==========================================
function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
    return confirm(message);
}

// ==========================================
// DEBOUNCE FUNCTION
// ==========================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==========================================
// FORMAT NUMBER
// ==========================================
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// ==========================================
// COPY TO CLIPBOARD
// ==========================================
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Đã copy vào clipboard');
    });
}

// ==========================================
// LOADING INDICATOR
// ==========================================
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'globalLoader';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('globalLoader');
    if (loader) loader.remove();
}

// ==========================================
// LAZY LOADING IMAGES
// ==========================================
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ==========================================
// EXPORT
// ==========================================
window.MusicPlayer = player;
window.playSong = playSong;
window.toggleFavorite = toggleFavorite;
window.addToPlaylist = addToPlaylist;



// ==========================================
// AUTO FIX BROKEN IMAGES
// ==========================================
// Tự động thay thế tất cả ảnh bị lỗi bằng ảnh mặc định
document.addEventListener('error', function(e) {
    if (e.target.tagName.toLowerCase() === 'img') {
        // Tránh vòng lặp vô tận nếu ảnh mặc định cũng bị lỗi
        if (e.target.src.includes('default-cover.jpg')) return;
        
        // Đường dẫn đến ảnh mặc định
        e.target.src = '/music-website/assets/images/default-cover.jpg';
        e.target.alt = 'Default Cover';
    }
}, true); // Sử dụng capture phase để bắt được sự kiện error
