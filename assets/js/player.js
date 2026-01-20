/**
 * MusicHub Player - Standalone Music Player
 * Fixed: Hỗ trợ lấy dữ liệu từ data attributes để tránh lỗi Unknown Song
 */

class MusicPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentSong = null;
        this.playlist = [];
        this.currentIndex = 0;
        this.isPlaying = false;
        this.isShuffle = false;
        this.repeatMode = 'off';
        this.volume = 0.7;
        
        this.init();
    }
    
    init() {
        this.audio.volume = this.volume;
        
        // Bind audio events
        this.audio.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        this.audio.addEventListener('timeupdate', () => this.onTimeUpdate());
        this.audio.addEventListener('ended', () => this.onEnded());
        this.audio.addEventListener('error', (e) => this.onError(e));
        this.audio.addEventListener('play', () => this.onPlay());
        this.audio.addEventListener('pause', () => this.onPause());
        
        // Create mini player
        this.createMiniPlayer();
        
        // Bind all play buttons on page
        this.bindAllPlayButtons();
        
        // Load saved state
        this.loadState();
    }
    
    // Tự động tạo mini player
    createMiniPlayer() {
        if (document.getElementById('miniPlayer')) return;
        
        const miniPlayerHTML = `
            <div id="miniPlayer" class="mini-player" style="display: none;">
                <div class="mini-player-content">
                    <div class="mini-player-song">
                        <img id="miniPlayerCover" src="" alt="Cover">
                        <div class="mini-player-info">
                            <div class="mini-player-title" id="miniPlayerTitle">-</div>
                            <div class="mini-player-artist" id="miniPlayerArtist">-</div>
                        </div>
                    </div>
                    
                    <div class="mini-player-controls">
                        <button class="mini-control-btn" id="miniPrevBtn" title="Previous">
                            <i class="fas fa-backward"></i>
                        </button>
                        <button class="mini-control-btn mini-play-btn" id="miniPlayBtn" title="Play/Pause">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="mini-control-btn" id="miniNextBtn" title="Next">
                            <i class="fas fa-forward"></i>
                        </button>
                    </div>
                    
                    <div class="mini-player-progress">
                        <div class="mini-progress-bar" id="miniProgressBar">
                            <div class="mini-progress-fill" id="miniProgressFill"></div>
                        </div>
                        <div class="mini-time-display">
                            <span id="miniCurrentTime">0:00</span>
                            <span id="miniDuration">0:00</span>
                        </div>
                    </div>
                    
                    <div class="mini-player-volume">
                        <button class="mini-control-btn" id="miniVolumeBtn" title="Volume">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <input type="range" class="mini-volume-slider" id="miniVolumeSlider" min="0" max="100" value="70">
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', miniPlayerHTML);
        
        // Add CSS
        this.addMiniPlayerStyles();
        
        // Bind mini player controls
        this.bindMiniPlayerControls();
    }
    
    // Thêm CSS cho mini player
    addMiniPlayerStyles() {
        if (document.getElementById('miniPlayerStyles')) return;
        
        const styles = `
            <style id="miniPlayerStyles">
                .mini-player {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(180deg, #282828 0%, #181818 100%);
                    border-top: 1px solid #404040;
                    z-index: 9999;
                    box-shadow: 0 -4px 20px rgba(0,0,0,0.5);
                }

                .mini-player-content {
                    display: grid;
                    grid-template-columns: 1fr 2fr 1fr;
                    align-items: center;
                    padding: 12px 20px;
                    gap: 20px;
                    max-width: 1400px;
                    margin: 0 auto;
                }

                .mini-player-song {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    min-width: 0;
                }

                .mini-player-song img {
                    width: 56px;
                    height: 56px;
                    border-radius: 4px;
                    object-fit: cover;
                    background: #333;
                }

                .mini-player-info {
                    min-width: 0;
                    flex: 1;
                }

                .mini-player-title {
                    font-weight: 600;
                    font-size: 14px;
                    color: #fff;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .mini-player-artist {
                    font-size: 12px;
                    color: #b3b3b3;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .mini-player-controls {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 16px;
                }

                .mini-control-btn {
                    width: 32px;
                    height: 32px;
                    border: none;
                    background: transparent;
                    color: #fff;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: all 0.3s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .mini-control-btn:hover {
                    color: #1db954;
                    transform: scale(1.1);
                }

                .mini-play-btn {
                    width: 40px;
                    height: 40px;
                    background: #fff;
                    color: #000;
                }

                .mini-play-btn:hover {
                    transform: scale(1.05);
                    background: #f0f0f0;
                }

                .mini-player-progress {
                    flex: 1;
                }

                .mini-progress-bar {
                    height: 4px;
                    background: #404040;
                    border-radius: 2px;
                    cursor: pointer;
                    margin-bottom: 4px;
                }

                .mini-progress-fill {
                    height: 100%;
                    background: #1db954;
                    border-radius: 2px;
                    width: 0%;
                    transition: width 0.1s;
                }

                .mini-time-display {
                    display: flex;
                    justify-content: space-between;
                    font-size: 11px;
                    color: #b3b3b3;
                }

                .mini-player-volume {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    justify-content: flex-end;
                }

                .mini-volume-slider {
                    width: 100px;
                    height: 4px;
                    -webkit-appearance: none;
                    background: #404040;
                    border-radius: 2px;
                    outline: none;
                }

                .mini-volume-slider::-webkit-slider-thumb {
                    -webkit-appearance: none;
                    width: 12px;
                    height: 12px;
                    background: #fff;
                    border-radius: 50%;
                    cursor: pointer;
                }

                .mini-volume-slider::-moz-range-thumb {
                    width: 12px;
                    height: 12px;
                    background: #fff;
                    border-radius: 50%;
                    cursor: pointer;
                    border: none;
                }

                .song-row.playing,
                .song-card.playing {
                    background: rgba(29, 185, 84, 0.1) !important;
                    border-left: 3px solid #1db954;
                }

                @media (max-width: 768px) {
                    .mini-player-content {
                        grid-template-columns: 1fr;
                        gap: 12px;
                    }
                    
                    .mini-player-volume {
                        display: none;
                    }
                    
                    .mini-player-progress {
                        order: -1;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    // Bind tất cả nút play trên trang
    bindAllPlayButtons() {
        // Tìm tất cả các nút play
        const playButtons = document.querySelectorAll('.play-btn, .play-btn-small, .play-overlay button');
        
        playButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Lấy ID bài hát từ data attribute hoặc onclick
                const songId = button.dataset.songId || 
                               button.getAttribute('onclick')?.match(/\d+/)?.[0];
                
                if (songId) {
                    this.loadSongById(songId);
                } else {
                    // Thử lấy thông tin từ card cha
                    const card = button.closest('.song-card, .song-row');
                    if (card) {
                        const cardSongId = card.dataset.songId;
                        if (cardSongId) {
                            this.loadSongById(cardSongId);
                        }
                    }
                }
            });
        });
        
        // Bind cho các link title
        const songTitles = document.querySelectorAll('.song-title a, .song-details h4, .song-title-text a');
        songTitles.forEach(title => {
            title.addEventListener('click', (e) => {
                const card = title.closest('.song-card, .song-row');
                if (card) {
                    e.preventDefault();
                    const songId = card.dataset.songId;
                    if (songId) {
                        this.loadSongById(songId);
                    }
                }
            });
        });
    }
    
    // Load bài hát theo ID (ĐÃ SỬA CHỖ NÀY ĐỂ FIX LỖI)
    loadSongById(songId) {
        console.log('Loading song ID:', songId);
        
        // Tìm element chứa thông tin bài hát (card hoặc row)
        const card = document.querySelector(`[data-song-id="${songId}"]`);
        
        if (card) {
            // 1. ƯU TIÊN: Lấy thông tin từ data attributes (nếu có) - Chính xác nhất
            let title = card.dataset.title;
            let artist = card.dataset.artist;
            let image = card.dataset.image;

            // 2. FALLBACK: Nếu không có data attribute, mới đi tìm (cào) từ HTML
            if (!title) {
                // Thêm .song-title-text h4 (cho trang artist) và .song-title a
                title = card.querySelector('.song-title, .song-details h4, .song-title-text h4, .song-title-text a')?.textContent?.trim();
            }
            if (!artist) {
                artist = card.querySelector('.song-artist, .song-details p')?.textContent?.trim();
            }
            if (!image) {
                image = card.querySelector('img')?.src;
            }
            
            // Nếu vẫn không tìm thấy nghệ sĩ (vd: trang artist list không có tên ca sĩ ở row)
            // Có thể set mặc định là "Unknown Artist" hoặc lấy từ biến toàn cục nếu cần
            if (!artist) artist = 'Unknown Artist';

            // Tạo object bài hát
            const songData = {
                id: songId,
                title: title || 'Unknown Song',
                artist_name: artist,
                image_url: image ? image.replace(window.location.origin, '') : '/assets/images/default-cover.jpg',
                file_url: `/uploads/songs/${this.generateFileName(title || songId)}.mp3`
            };
            
            console.log('Song data loaded:', songData);
            
            this.loadSong(songData);
            this.play();
        } else {
            // Fallback: Gọi API nếu không tìm thấy card trên giao diện
            this.loadSongFromAPI(songId);
        }
    }
    
    // Load bài hát từ API (fallback)
    loadSongFromAPI(songId) {
        const baseUrl = window.location.origin + '/music-website';
        
        fetch(`${baseUrl}/api/songs.php?id=${songId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.song) {
                    this.loadSong(data.song);
                    this.play();
                } else {
                    console.error('Song not found');
                    // alert('Không tìm thấy bài hát');
                }
            })
            .catch(error => {
                console.error('Error loading song:', error);
                // alert('Lỗi khi tải bài hát');
            });
    }
    
    // Generate file name từ title
    generateFileName(title) {
        // Convert Vietnamese to ASCII and make URL friendly
        const str = title.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        return str;
    }
    
    // Load a song
    loadSong(songData) {
        this.currentSong = songData;
        
        // Construct full URL
        const baseUrl = window.location.origin + '/music-website';
        let audioUrl = songData.file_url;
        
        // Nếu chưa có protocol thì thêm baseUrl
        if (!audioUrl.startsWith('http')) {
            audioUrl = baseUrl + audioUrl;
        }
        
        this.audio.src = audioUrl;
        console.log('Loading audio:', audioUrl);
        
        this.audio.load();
        this.updateSongInfo();
        this.showMiniPlayer();
        this.highlightCurrentSong();
        this.saveState();
    }
    
    // Play
    play() {
        if (this.currentSong) {
            this.audio.play()
                .then(() => {
                    this.isPlaying = true;
                    this.updatePlayButton();
                    this.trackPlay();
                })
                .catch(error => {
                    console.error('Playback error:', error);
                    console.warn('Không thể phát nhạc. File:', this.audio.src);
                });
        }
    }
    
    // Pause
    pause() {
        this.audio.pause();
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    // Toggle play/pause
    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    // Show mini player
    showMiniPlayer() {
        const miniPlayer = document.getElementById('miniPlayer');
        if (miniPlayer) {
            miniPlayer.style.display = 'block';
        }
    }
    
    // Bind mini player controls
    bindMiniPlayerControls() {
        const miniPlayBtn = document.getElementById('miniPlayBtn');
        if (miniPlayBtn) {
            miniPlayBtn.addEventListener('click', () => this.togglePlay());
        }
        
        const miniPrevBtn = document.getElementById('miniPrevBtn');
        if (miniPrevBtn) {
            miniPrevBtn.addEventListener('click', () => this.previous());
        }
        
        const miniNextBtn = document.getElementById('miniNextBtn');
        if (miniNextBtn) {
            miniNextBtn.addEventListener('click', () => this.next());
        }
        
        const miniVolumeSlider = document.getElementById('miniVolumeSlider');
        if (miniVolumeSlider) {
            miniVolumeSlider.addEventListener('input', (e) => {
                this.setVolume(e.target.value / 100);
            });
        }
        
        const miniVolumeBtn = document.getElementById('miniVolumeBtn');
        if (miniVolumeBtn) {
            miniVolumeBtn.addEventListener('click', () => this.toggleMute());
        }
        
        const miniProgressBar = document.getElementById('miniProgressBar');
        if (miniProgressBar) {
            miniProgressBar.addEventListener('click', (e) => this.seek(e));
        }
    }
    
    // Next song (placeholder)
    next() {
        alert('Chức năng phát tiếp đang được phát triển');
    }
    
    // Previous song (placeholder)
    previous() {
        if (this.audio.currentTime > 3) {
            this.audio.currentTime = 0;
        } else {
            alert('Chức năng phát lại đang được phát triển');
        }
    }
    
    // Set volume
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        this.audio.volume = this.volume;
        this.updateVolumeUI();
        this.saveState();
    }
    
    // Toggle mute
    toggleMute() {
        if (this.audio.volume > 0) {
            this.audio.dataset.prevVolume = this.audio.volume;
            this.setVolume(0);
        } else {
            const prevVolume = parseFloat(this.audio.dataset.prevVolume) || 0.7;
            this.setVolume(prevVolume);
        }
    }
    
    // Seek
    seek(e) {
        const progressBar = e.currentTarget;
        const rect = progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        const seekTime = percent * this.audio.duration;
        
        if (!isNaN(seekTime)) {
            this.audio.currentTime = seekTime;
        }
    }
    
    // Event handlers
    onLoadedMetadata() {
        this.updateDuration();
    }
    
    onTimeUpdate() {
        this.updateProgress();
        this.updateCurrentTime();
    }
    
    onEnded() {
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    onPlay() {
        this.isPlaying = true;
        this.updatePlayButton();
    }
    
    onPause() {
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    onError(e) {
        console.error('Audio error:', e);
        console.warn('File nhạc không tồn tại:', this.audio.src);
    }
    
    // UI Updates
    updateSongInfo() {
        if (!this.currentSong) return;
        
        const title = document.getElementById('miniPlayerTitle');
        if (title) title.textContent = this.currentSong.title;
        
        const artist = document.getElementById('miniPlayerArtist');
        if (artist) artist.textContent = this.currentSong.artist_name || 'Unknown';
        
        const cover = document.getElementById('miniPlayerCover');
        if (cover) {
            let imageUrl = this.currentSong.image_url || '/assets/images/default-cover.jpg';
            if (imageUrl.startsWith('http')) {
                cover.src = imageUrl;
            } else {
                if (imageUrl.startsWith('/music-website')) {
                    cover.src = window.location.origin + imageUrl;
                } else {
                    cover.src = window.location.origin + '/music-website' + imageUrl;
                }
            }
        }
        
        document.title = `${this.currentSong.title} - ${this.currentSong.artist_name || 'Unknown'} | MusicHub`;
    }
    
    updatePlayButton() {
        const miniPlayBtn = document.getElementById('miniPlayBtn');
        if (miniPlayBtn) {
            const icon = miniPlayBtn.querySelector('i');
            if (icon) {
                icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
            }
        }
        
        // Update all play buttons
        document.querySelectorAll('.play-btn, .play-btn-small').forEach(btn => {
            const songId = btn.dataset.songId || btn.closest('[data-song-id]')?.dataset.songId;
            if (songId && this.currentSong && songId == this.currentSong.id) {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
                }
            }
        });
    }
    
    updateProgress() {
        const fill = document.getElementById('miniProgressFill');
        if (fill && this.audio.duration) {
            const percent = (this.audio.currentTime / this.audio.duration) * 100;
            fill.style.width = percent + '%';
        }
    }
    
    updateCurrentTime() {
        const el = document.getElementById('miniCurrentTime');
        if (el) {
            el.textContent = this.formatTime(this.audio.currentTime);
        }
    }
    
    updateDuration() {
        const el = document.getElementById('miniDuration');
        if (el) {
            el.textContent = this.formatTime(this.audio.duration);
        }
    }
    
    updateVolumeUI() {
        const slider = document.getElementById('miniVolumeSlider');
        if (slider) {
            slider.value = this.volume * 100;
        }
        
        const btn = document.getElementById('miniVolumeBtn');
        if (btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                if (this.volume === 0) {
                    icon.className = 'fas fa-volume-mute';
                } else if (this.volume < 0.5) {
                    icon.className = 'fas fa-volume-down';
                } else {
                    icon.className = 'fas fa-volume-up';
                }
            }
        }
    }
    
    highlightCurrentSong() {
        document.querySelectorAll('.song-row, .song-card').forEach(el => {
            el.classList.remove('playing');
        });
        
        if (this.currentSong) {
            const card = document.querySelector(`[data-song-id="${this.currentSong.id}"]`);
            if (card) {
                card.classList.add('playing');
            }
        }
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }
    
    trackPlay() {
        if (!this.currentSong) return;
        const baseUrl = window.location.origin + '/music-website';

        // 1. Tăng lượt nghe (Code cũ - Giữ nguyên)
        fetch(`${baseUrl}/api/play-song.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ song_id: this.currentSong.id })
        }).catch(error => console.error('Error tracking play:', error));

        // 2. LƯU LỊCH SỬ NGHE (Thêm đoạn này vào ngay bên dưới)
        fetch(`${baseUrl}/api/save_history.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ song_id: this.currentSong.id })
        }).catch(error => console.error('Error saving history:', error));
    }
    
    saveState() {
        try {
            const state = {
                currentSong: this.currentSong,
                volume: this.volume,
                currentTime: this.audio.currentTime
            };
            localStorage.setItem('musicPlayerState', JSON.stringify(state));
        } catch (e) {
            console.error('Error saving state:', e);
        }
    }
    
    loadState() {
        try {
            const savedState = localStorage.getItem('musicPlayerState');
            if (savedState) {
                const state = JSON.parse(savedState);
                if (state.volume !== undefined) {
                    this.setVolume(state.volume);
                }
            }
        } catch (e) {
            console.error('Error loading state:', e);
        }
    }
}

// Initialize player
let player;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlayer);
} else {
    initPlayer();
}

function initPlayer() {
    player = new MusicPlayer();
    window.musicPlayer = player;
    console.log('Music Player initialized');
}

// Global functions
window.playSong = function(songId) {
    if (window.musicPlayer) {
        window.musicPlayer.loadSongById(songId);
    }
};

window.toggleFavorite = function(songId) {
    const baseUrl = window.location.origin + '/music-website';
    fetch(`${baseUrl}/api/favorites.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ song_id: songId, action: 'toggle' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`[onclick*="toggleFavorite(${songId})"]`);
            if (btn) {
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                }
            }
        } else {
            // alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => console.error('Error:', error));
};

window.addToPlaylist = function(songId) {
    alert('Chức năng thêm vào playlist đang được phát triển');
};