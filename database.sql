-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th12 27, 2025 lúc 11:29 AM
-- Phiên bản máy phục vụ: 8.4.7
-- Phiên bản PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `music_website`
--

DELIMITER $$
--
-- Thủ tục
--
DROP PROCEDURE IF EXISTS `GetPlayStatistics`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPlayStatistics` (IN `period` VARCHAR(10))   BEGIN
    IF period = 'day' THEN
        SELECT DATE(listened_at) as date, COUNT(*) as plays
        FROM listening_history
        WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(listened_at)
        ORDER BY date ASC;
    ELSEIF period = 'week' THEN
        SELECT WEEK(listened_at) as week, YEAR(listened_at) as year, COUNT(*) as plays
        FROM listening_history
        WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
        GROUP BY YEAR(listened_at), WEEK(listened_at)
        ORDER BY year ASC, week ASC;
    ELSEIF period = 'month' THEN
        SELECT MONTH(listened_at) as month, YEAR(listened_at) as year, COUNT(*) as plays
        FROM listening_history
        WHERE listened_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY YEAR(listened_at), MONTH(listened_at)
        ORDER BY year ASC, month ASC;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `GetTopArtists`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTopArtists` (IN `limit_count` INT)   BEGIN
    SELECT a.id, a.name, a.avatar, COUNT(DISTINCT f.id) as total_fans,
           SUM(s.play_count) as total_plays
    FROM artists a
    LEFT JOIN songs s ON a.id = s.artist_id
    LEFT JOIN favorites f ON s.id = f.song_id
    GROUP BY a.id
    ORDER BY total_fans DESC, total_plays DESC
    LIMIT limit_count;
END$$

DROP PROCEDURE IF EXISTS `GetTopSongs`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTopSongs` (IN `limit_count` INT)   BEGIN
    SELECT s.id, s.title, a.name as artist_name, s.play_count, s.image_url
    FROM songs s
    LEFT JOIN artists a ON s.artist_id = a.id
    ORDER BY s.play_count DESC
    LIMIT limit_count;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `albums`
--

DROP TABLE IF EXISTS `albums`;
CREATE TABLE IF NOT EXISTS `albums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `artist_id` int NOT NULL,
  `release_date` date DEFAULT NULL,
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_artist` (`artist_id`),
  KEY `idx_release` (`release_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `albums`
--

INSERT INTO `albums` (`id`, `title`, `artist_id`, `release_date`, `cover_url`, `created_at`, `updated_at`) VALUES
(1, 'm-tp M-TP', 1, '2017-07-01', NULL, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(2, 'Đen', 2, '2019-01-01', NULL, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(3, 'Min & Friends', 3, '2020-05-15', NULL, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(4, 'MONSTAR Album', 4, '2021-03-20', NULL, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(5, 'Trình', 6, '2020-09-01', NULL, '2025-12-20 15:18:56', '2025-12-20 15:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `artists`
--

DROP TABLE IF EXISTS `artists`;
CREATE TABLE IF NOT EXISTS `artists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `artists`
--

INSERT INTO `artists` (`id`, `name`, `bio`, `avatar`, `country`, `created_at`, `updated_at`) VALUES
(1, 'Sơn Tùng M-TP', 'Ca sĩ, rapper, nhạc sĩ người Việt Nam', '/uploads/artists/son-tung.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(2, 'Đen Vâu', 'Rapper, nhạc sĩ người Việt Nam', '/uploads/artists/den-vau.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(3, 'Min', 'Ca sĩ người Việt Nam', '/uploads/artists/min.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(4, 'MONSTAR', 'Nhóm nhạc người Việt Nam', '/uploads/artists/monstar.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(5, 'Jack', 'Ca sĩ, nhạc sĩ người Việt Nam', '/uploads/artists/jack.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(6, 'HIEUTHUHAI', 'Rapper người Việt Nam', '/uploads/artists/hieuthuhai.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(7, 'Bích Phương', 'Ca sĩ, nhạc sĩ người Việt Nam', '/uploads/artists/bich-phuong.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(8, 'Mỹ Tâm', 'Ca sĩ người Việt Nam', '/uploads/artists/my-tam.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(9, 'Hoàng Thùy Linh', 'Ca sĩ, diễn viên người Việt Nam', '/uploads/artists/hoang-thuy-linh.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(10, 'Erik', 'Ca sĩ người Việt Nam', '/uploads/artists/erik.jpg', 'Việt Nam', '2025-12-20 15:18:56', '2025-12-20 15:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `dashboard_stats`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `dashboard_stats`;
CREATE TABLE IF NOT EXISTS `dashboard_stats` (
`total_users` bigint
,`total_songs` bigint
,`total_artists` bigint
,`total_albums` bigint
,`total_plays` decimal(32,0)
,`today_plays` bigint
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `song_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`song_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_song` (`song_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `song_id`, `created_at`) VALUES
(1, 2, 1, '2025-12-20 15:18:56'),
(2, 2, 2, '2025-12-20 15:18:56'),
(3, 2, 8, '2025-12-20 15:18:56'),
(4, 3, 1, '2025-12-20 15:18:56'),
(5, 3, 7, '2025-12-20 15:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `listening_history`
--

DROP TABLE IF EXISTS `listening_history`;
CREATE TABLE IF NOT EXISTS `listening_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `song_id` int NOT NULL,
  `listened_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_song` (`song_id`),
  KEY `idx_listened` (`listened_at`),
  KEY `idx_history_user_date` (`user_id`,`listened_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `listening_history`
--

INSERT INTO `listening_history` (`id`, `user_id`, `song_id`, `listened_at`) VALUES
(1, 2, 1, '2025-12-19 15:18:56'),
(2, 2, 2, '2025-12-19 15:18:56'),
(3, 2, 3, '2025-12-18 15:18:56'),
(4, 3, 1, '2025-12-17 15:18:56'),
(5, 3, 5, '2025-12-16 15:18:56'),
(6, 2, 7, '2025-12-15 15:18:56'),
(7, 3, 8, '2025-12-14 15:18:56'),
(8, 2, 4, '2025-12-20 15:18:56');

--
-- Bẫy `listening_history`
--
DROP TRIGGER IF EXISTS `after_listening_insert`;
DELIMITER $$
CREATE TRIGGER `after_listening_insert` AFTER INSERT ON `listening_history` FOR EACH ROW BEGIN
    UPDATE songs SET play_count = play_count + 1 WHERE id = NEW.song_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `playlists`
--

DROP TABLE IF EXISTS `playlists`;
CREATE TABLE IF NOT EXISTS `playlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_public` (`is_public`),
  KEY `idx_playlist_user_public` (`user_id`,`is_public`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `name`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 2, 'Nhạc Chill', 'Playlist nhạc chill của tôi', 1, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(2, 2, 'Workout Mix', 'Nhạc tập gym', 0, '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(3, 3, 'Top Hits', 'Những bài hát hay nhất', 1, '2025-12-20 15:18:56', '2025-12-20 15:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `playlist_songs`
--

DROP TABLE IF EXISTS `playlist_songs`;
CREATE TABLE IF NOT EXISTS `playlist_songs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `playlist_id` int NOT NULL,
  `song_id` int NOT NULL,
  `position` int DEFAULT '0',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_playlist_song` (`playlist_id`,`song_id`),
  KEY `idx_playlist` (`playlist_id`),
  KEY `idx_song` (`song_id`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `playlist_songs`
--

INSERT INTO `playlist_songs` (`id`, `playlist_id`, `song_id`, `position`, `added_at`) VALUES
(1, 1, 3, 1, '2025-12-20 15:18:56'),
(2, 1, 4, 2, '2025-12-20 15:18:56'),
(3, 1, 5, 3, '2025-12-20 15:18:56'),
(4, 2, 1, 1, '2025-12-20 15:18:56'),
(5, 2, 7, 2, '2025-12-20 15:18:56'),
(6, 3, 8, 1, '2025-12-20 15:18:56'),
(7, 3, 1, 2, '2025-12-20 15:18:56'),
(8, 3, 2, 3, '2025-12-20 15:18:56');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `popular_songs`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `popular_songs`;
CREATE TABLE IF NOT EXISTS `popular_songs` (
`id` int
,`title` varchar(150)
,`duration` varchar(10)
,`play_count` int
,`image_url` varchar(255)
,`file_url` varchar(255)
,`created_at` timestamp
,`artist_id` int
,`artist_name` varchar(100)
,`artist_avatar` varchar(255)
,`album_id` int
,`album_title` varchar(150)
,`album_cover` varchar(255)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `site_visits`
--

DROP TABLE IF EXISTS `site_visits`;
CREATE TABLE IF NOT EXISTS `site_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `page_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visited_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_user` (`user_id`),
  KEY `idx_visited` (`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `songs`
--

DROP TABLE IF EXISTS `songs`;
CREATE TABLE IF NOT EXISTS `songs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `artist_id` int NOT NULL,
  `album_id` int DEFAULT NULL,
  `duration` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format: MM:SS',
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `play_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lyrics` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_artist` (`artist_id`),
  KEY `idx_album` (`album_id`),
  KEY `idx_play_count` (`play_count`),
  KEY `idx_created` (`created_at`),
  KEY `idx_song_artist_play` (`artist_id`,`play_count`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `songs`
--

INSERT INTO `songs` (`id`, `title`, `artist_id`, `album_id`, `duration`, `file_url`, `image_url`, `play_count`, `created_at`, `updated_at`, `lyrics`) VALUES
(1, 'Lạc Trôi', 1, 1, '04:35', '/uploads/songs/lac-troi.mp3', '/uploads/covers/lac-troi.jpg', 15000000, '2025-12-20 15:18:56', '2025-12-26 16:59:20', 'Ah ah\r\nNgười theo hương hoa mây mù giăng lối\r\nLàn sương khói phôi phai đưa bước ai xa rồi\r\nĐơn côi mình ta vấn vương\r\nHồi ức trong men say chiều mưa buồn\r\nNgăn giọt lệ ngừng khiến khoé mi sầu bi\r\nĐường xưa nơi cố nhân từ giã biệt li (cánh hoa rụng rời)\r\nPhận duyên mong manh rẽ lối trong mơ ngày tương phùng\r\nOh tiếng khóc cuốn theo làn gió bay\r\nThuyền qua sông lỡ quên vớt ánh trăng tàn nơi này\r\nTrống vắng bóng ai dần hao gầy hoh\r\nLòng ta xin nguyện khắc ghi trong tim tình nồng mê say\r\nMặc cho tóc mây vương lên đôi môi cay\r\nBâng khuâng mình ta lạc trôi giữa đời\r\nTa lạc trôi giữa trời\r\nĐôi chân lang thang về nơi đâu\r\nBao yêu thương giờ nơi đâu\r\nCâu thơ tình xưa vội phai mờ\r\nTheo làn sương tan biến trong cõi mơ\r\nMưa bụi vươn trên làn mi mắt (mắt)\r\nNgày chia lìa hoa rơi buồn hiu hắt (hắt)\r\nTiếng đàn ai thêm sầu tương tư lặng mình trong chiều hoàng hôn tan vào lời ca\r\nLối mòn đường vắng một mình ta\r\nNắng chiều vàng úa nhuộm ngày qua\r\nXin đừng quay lưng xoá\r\nĐừng mang câu hẹn ước kia rời xa\r\nYên bình nơi nào đây\r\nChôn vùi theo làn mây yeah lala\r\nNgười theo hương hoa mây mù giăng lối\r\nLàn sương khói phôi phai đưa bước ai xa rồi\r\nĐơn côi mình ta vấn vương\r\nHồi ức trong men say chiều mưa buồn\r\nNgăn giọt lệ ngừng khiến khoé mi sầu bi\r\nTừ xưa nơi cố nhân từ giã biệt li (cánh hoa rụng rời)\r\nPhận duyên mong manh rẽ lối trong mưa ngày tương phùng oh\r\nTiếng khóc cuốn theo làn gió bay\r\nThuyền qua sông lỡ quên vớt ánh trăng tàn nơi này\r\nTrống vắng bóng ai dần hao gầy hoh\r\nLòng ta xin nguyện khắc ghi trong tim tình nồng mê say\r\nMặc cho tóc mây vương lên đôi môi cay\r\nBâng khuâng mình ta lạc trôi giữa đời\r\nTa lạc trôi giữa trời ah\r\nTa lạc trôi lạc trôi (lạc trôi)\r\nTa lạc trôi giữa đời\r\nLạc trôi giữa trời\r\nYeah ah ah\r\nTa đang lạc nơi nào (lạc nơi nào)\r\nTa đang lạc nơi nào\r\nLối mòn đường vắng một mình ta\r\nTa đang lạc nơi nào (ai bên cạnh ta ai bên cạnh ta)\r\nNắng chiều vàng úa nhuộm ngày qua\r\nTa đang lạc nơi nào oh'),
(2, 'Nơi Này Có Anh', 1, 1, '05:20', '/uploads/songs/noi-nay-co-anh.mp3', '/uploads/covers/noi-nay-co-anh.jpg', 20000000, '2025-12-20 15:18:56', '2025-12-26 16:57:39', 'Em là ai từ đâu bước đến nơi đây dịu dàng chân phương\r\nEm là ai tựa như ánh nắng ban mai ngọt ngào trong sương\r\nNgắm em thật lâu\r\nCon tim anh yếu mềm\r\nĐắm say từ phút đó\r\nTừng giây trôi yêu thêm\r\nBao ngày qua bình minh đánh thức xua tan bộn bề nơi anh (bộn về nơi anh)\r\nBao ngày qua niềm thương nỗi nhớ bay theo bầu trời trong xanh (bầu trời trong xanh)\r\nLiếc đôi hàng mi (hàng mi)\r\nMong manh anh thẫn thờ (thẫn thờ)\r\nMuốn hôn nhẹ mái tóc\r\nBờ môi em anh mơ\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nGió mang câu tình ca\r\nNgàn ánh sao vụt qua nhẹ ôm lấy em (yêu em thương em con tim anh chân thành)\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nKhép đôi mi thật lâu\r\nNguyện mãi bên cạnh nhau yêu say đắm như ngày đầu\r\nMùa xuân đến bình yên\r\nCho anh những giấc mơ\r\nHạ lưu giữ ngày mưa\r\nNgọt ngào nên thơ\r\nMùa thu lá vàng rơi\r\nĐông sang anh nhớ em\r\nTình yêu bé nhỏ xin\r\nDành tặng riêng em\r\nCòn đó tiếng nói ấy bên tai vấn vương bao ngày qua\r\nÁnh mắt bối rối nhớ thương bao ngày qua\r\nYêu em anh thẫn thờ\r\nCon tim bâng khuâng đâu có ngờ\r\nChẳng bao giờ phải mong chờ\r\nĐợi ai trong chiều hoàng hôn mờ\r\nĐắm chìm hòa vào vần thơ\r\nNgắm nhìn khờ dại mộng mơ\r\nĐừng bước vội vàng rồi làm ngơ\r\nLạnh lùng đó làm bộ dạng thờ ơ\r\nNhìn anh đi em nha\r\nHướng nụ cười cho riêng anh nha\r\nĐơn giản là yêu\r\nCon tim anh lên tiếng thôi\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nGió mang câu tình ca\r\nNgàn ánh sao vụt qua nhẹ ôm lấy em (yêu em thương em con tim anh chân thành)\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nKhép đôi mi thật lâu\r\nNguyện mãi bên cạnh nhau yêu say đắm như ngày đầu\r\nMùa xuân đến bình yên\r\nCho anh những giấc mơ\r\nHạ lưu giữ ngày mưa\r\nNgọt ngào nên thơ\r\nMùa thu lá vàng rơi\r\nĐông sang anh nhớ em\r\nTình yêu bé nhỏ xin\r\nDành tặng riêng em\r\nOh-oh-oh-oh nhớ thương em\r\nOh-oh-oh-oh nhớ thương em lắm\r\nAh phía sau chân trời\r\nCó ai băng qua lối về\r\nCùng em đi trên đoạn đường dài\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nGió mang câu tình ca\r\nNgàn ánh sao vụt qua nhẹ ôm lấy em (yêu em thương em con tim anh chân thành)\r\nCầm tay anh dựa vai anh\r\nKề bên anh nơi này có anh\r\nKhép đôi mi thật lâu\r\nNguyện mãi bên cạnh nhau yêu say đắm như ngày đầu\r\nMùa xuân đến bình yên\r\nCho anh những giấc mơ\r\nHạ lưu giữ ngày mưa\r\nNgọt ngào nên thơ\r\nMùa thu lá vàng rơi\r\nĐông sang anh nhớ em\r\nTình yêu bé nhỏ xin\r\nDành tặng riêng em'),
(3, 'Bài Này Chill Phết', 2, 2, '04:12', '/uploads/songs/bai-nay-chill-phet.mp3', '/uploads/covers/bai-nay-chill-phet.jpg', 12000000, '2025-12-20 15:18:56', '2025-12-26 17:00:28', 'Ngày nắng tươi hồng, trong ngần, tiếng ve\r\nGọi gió thu về, đưa mùa lúa chín đến đây\r\nBay bay làn tóc em, hòa với không trung\r\nCùng mây trắng ngang trời biếc xanh trong lành cánh chim liệng bay\r\nCặp sách mang khéo trên vai, đem bao hoài bão theo\r\nLẹ bước chân bé đến trường, gieo hạt mầm ước mơ\r\nEm mỉm cười hát vang, chợt thế gian như hòa ca\r\nCây xanh rì, tiếng chim véo von là lá la là la\r\nKìa mây, mây ngang đầu, kia núi, núi lô nhô\r\nCùng em trên con đường, đường bé xíu quanh co\r\nBăng qua những ngọn đồi\r\nThấy em nghiêng nghiêng cười trong đôi mắt tròn\r\nVà thế giới cũng nghiêng theo đôi bàn chân em\r\nĐường gập ghềnh, nhọc nhằn đôi bàn chân bước\r\nEm đến trường cùng ánh ban mai (ooh-ooh)\r\nMặt Trời vàng, trải dài hai hàng mây trắng\r\nNúi và đồi ngả bước nương theo\r\nĐó là cách chúng ta, người Việt Nam không bỏ cuộc\r\nTa cố và gắng hàng ngày mà không điều gì ngăn cản được\r\nVà khi cuộc đời muốn làm ta khóc hay muốn làm ta phải bỏ cuộc\r\nThì ta lại nhìn các em, lại học các em, học cách cười thật tươi, ah\r\nNấu cho các em ăn dù ta không là đầu bếp giỏi\r\nCũng là cách ta giúp chính mình bớt nghĩ suy cho đầu hết mỏi\r\nThì cũng muốn trở thành người tốt giúp những đứa trẻ xây dựng quê hương\r\nNên mình nhìn các em để học, học cách cho đi niềm yêu thương\r\nTa nấu lên những ước mơ mà thứ ta nung chính là tri thức\r\nNấu lên những giấc mơ mà củi của ta là mồ hôi, công sức\r\nNgày hôm nay ta nhìn chim bay để một mai này ta ngồi trên nó\r\nVà ngày hôm nay ta nhìn lên mây để một mai này chao lượn trên đó, yah\r\nTrong đôi mắt đó, em thấy bầu trời, em thấy núi đồi, mặt hồ trong veo\r\nMong chân sẽ cứng và đá luôn mềm trên mỗi con đường từng ngày em qua\r\nMặt Trời trong trái tim hồng, vang trong lòng một tiếng gà trưa\r\nMong cho cây lá lên mầm, mong cho trời thuận gió hoà mưa\r\nKìa mây, mây ngang đầu, kia núi, núi lô nhô\r\nCùng em trên con đường, đường bé xíu quanh co\r\nBăng qua những ngọn đồi\r\nThấy em nghiêng nghiêng cười trong đôi mắt tròn\r\nVà thế giới cũng nghiêng theo đôi bàn chân em\r\nMẹ hát ru em vào giấc nồng\r\nLà ước mơ vượt qua bão giông\r\nLà tiếng ve kêu ngày nắng hồng\r\nNgày rất xanh, em hằng ngóng trông'),
(4, 'Hai Triệu Năm', 2, 2, '03:58', '/uploads/songs/hai-trieu-nam.mp3', '/uploads/covers/hai-trieu-nam.jpg', 8000000, '2025-12-20 15:18:56', '2025-12-26 17:00:56', 'Anh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm (yah)\r\nXung quanh anh toàn là nước ay\r\nCơ thể anh đang bị ướt ay\r\nMênh mông toàn là nước ay\r\nÊm ái như chưa từng trước đây\r\nTrăm ngàn con sóng xô (sóng xô ya)\r\nAnh lao vào trong biển cả vì em làm anh nóng khô (nóng khô ya)\r\nAnh ngâm mình trong làn nước để mặn mòi từ da dẻ (mặn mòi từ da dẻ)\r\nTa cần tình yêu vì tình yêu làm cho ta trẻ đúng rồi (ta trẻ ta trẻ ta trẻ)\r\nAnh cũng cần em nhưng không biết em sao\r\nAnh không care lắm và anh quyết đem trao\r\nCho em hết nắng cho em hết đêm sao\r\nNhìn mặt anh đi em nghĩ anh tiếc em sao yo (anh thấy tiếc em đâu yo)\r\nTrăm ngàn con sóng từ mọi nơi mà đổ về\r\nVà đây là cách mà anh đi tìm kiếm sự vỗ về\r\nEm có quá nhiều bí mật anh thì không cần gặng hỏi\r\nEm sâu như là đại dương anh thì không hề lặn giỏi yo yo (anh thì không hề lặn giỏi baby)\r\nAnh soi mình vào gương cho bõ công lau\r\nThấy mặt thấy người sao thấy rõ trong nhau\r\nÁnh mắt nụ cười kia không rõ nông sâu\r\nTa rồi sẽ là ai một câu hỏi nhỏ trong đầu (một câu hỏi nhỏ trong đầu)\r\nTa chỉ là hòn đất hay chỉ là cỏ bông lau (ta chỉ là cỏ bông lau)\r\nNhư là mấy gã em mới bỏ không lâu (như là mấy gã em mới bỏ không lâu)\r\nHay chỉ là đầu thuốc kia cháy đỏ không lâu yo (cháy đỏ không lâu)\r\nYêu em kiểu nông dân yêu em kiểu quê mùa\r\nYêu từ vụ đông xuân đến hè thu thay mùa\r\nNhưng em thì trơn trượt như là con cá chuối (như là con cá chuối)\r\nMuốn níu em trong tay Khá Bảnh cũng khá đuối (Khá Bảnh cũng khá đuối)\r\nEm giống hệt như biển cả em có nhiều bí mật\r\nAnh làm rất nhiều thứ để đồng tiền trong ví chật\r\nNgười ta không quý con ong mà người ta chỉ quý mật\r\nEm hỏi anh nhạc sao hay anh gọi nó là bí thuật yo yo\r\nEm hỏi anh nhạc sao hay anh gọi nó là bí thuật yo\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nNước đã hình thành trong hàng triệu năm (triệu năm)\r\nCát đã hình thành trong hàng triệu năm (triệu năm)\r\nBiển cả hình thành trong hàng triệu năm (triệu năm)\r\nVà em làm anh buồn sau hàng triệu năm (triệu năm)\r\nGặp em từ thể đơn bào rồi tiến hoá (tiến hoá)\r\nXa em từ khi thềm lục địa đầy biến hoá (tha hoá)\r\nMuốn được ôm em qua kỷ Jura\r\nHoá thạch cùng nhau trên những phiến đá (phá đá cùng nhau)\r\nRồi loài người tìm thấy lửa anh lại tìm thấy em (yah)\r\nAnh tưởng rằng mọi thứ sẽ được bùng cháy lên (yah)\r\nMuốn được cùng em trồng rau bên hồ cá (hồ cá)\r\nNhưng tim em lúc đó đang là thời kì đồ đá (đang là thời kì đồ đá)\r\nHey anh đã tin vào em như tin vào thuyết nhật tâm\r\nNhư Ga-li-lê người ta nói anh thật hâm\r\nCó lẽ Đác-win biết biển cả sẽ khô hơn\r\nNhưng anh tin ông ta không biết chúng ta đang tiến hoá để cô đơn (tiến hoá để cô đơn)\r\nVà có lẽ Đác-win biết biển cả sẽ khô hơn (tiến hoá để cô đơn)\r\nNhưng anh tin ông ta không biết chúng ta đang tiến hoá để cô đơn (tiến hoá để cô đơn tiến hoá để cô đơn)\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm\r\nAnh cô đơn giữa tinh không này\r\nMuôn con sóng cuốn xô vào đây\r\nEm cô đơn giữa mênh mông người\r\nVà ta cô đơn đã hai triệu năm'),
(5, 'Vì Yêu Cứ Đâm Đầu', 3, 3, '03:45', '/uploads/songs/vi-yeu-cu-dam-dau.mp3', '/uploads/covers/vi-yeu-cu-dam-dau.jpg', 6000000, '2025-12-20 15:18:56', '2025-12-26 17:01:27', 'Qua nỗi sầu đêm nay\r\nTrăng gối đầu lên mây\r\nThêm chút rượu và men cuốn muộn phiền đi để tình thêm say\r\nĐôi mắt buồn sâu cay\r\nChan chứa tình lâu nay, oh boy\r\nKhông biết ở nơi đâu\r\nKhông biết được bao lâu\r\nChỉ muốn cùng anh đi đến tận cùng nơi đất trời thâm sâu\r\nHuhh-huh-huh-huh-huh\r\nVì yêu cứ đâm đầu (cứ đâm đầu)\r\nCứ đâm đầu (cứ đâm đầu)\r\nNgay lúc nhìn, ngay lúc nhìn, ngay lúc nhìn, ngay lúc nhìn anh đôi phút\r\nTim đã vội, tim đã vội, tim đã vội, tim đã vội hơn đôi chút\r\nSorry em chẳng biết làm sao\r\nĐể thì giờ trôi phí là bao (oh-uh-oh, oh-oh)\r\nEm muốn được trói chặt trong vòng tay của anh\r\nEm muốn được nghe từng hơi thở đang bủa quanh\r\nXin hãy đừng, hãy đừng, hãy đừng, hãy đừng\r\nĐể em đợi (yeah, yeah)\r\nAy\r\nChạm lên bờ môi của em đỏ au\r\nTrò chuyện thật nhiều để cho rõ nhau (yah)\r\nChúng ta hoang dại như là cỏ lau (ah-hah)\r\nBỏ tiền bỏ bạc chứ không bỏ nhau\r\nAnh sẽ nói cho em nghe những điều mà anh ấp ủ\r\nAnh hy vọng lời ca này vỗ về em trong giấc ngủ (yeah)\r\nAnh cũng muốn em biết anh vốn không phải là thiếu gia (ha-ha)\r\nNhững ngày em về trong đời anh nhất định trải chiếu hoa, yah\r\nQua nỗi sầu đêm nay\r\nTrăng gối đầu lên mây\r\nThêm chút rượu và men cuốn muộn phiền đi để tình thêm say\r\nĐôi mắt buồn sâu cay\r\nChan chứa tình lâu nay, oh boy\r\nKhông biết ở nơi đâu\r\nKhông biết được bao lâu\r\nChỉ muốn cùng anh đi đến tận cùng nơi đất trời thâm sâu\r\nHuhh-huh-huh-huh-huh\r\nVì yêu cứ đâm đầu (cứ đâm đầu)\r\nCứ đâm đầu (cứ đâm đầu) (just, just, just)\r\nOoh\r\nCứ lãng đãng trôi như làn mây trắng\r\nChẳng cần lo nghĩ nhiều\r\nChỉ cần em biết điều\r\nVà ooh, khi trăng lên cao em sẽ là ngọn gió cuốn anh feel\r\nCầm chặt tay và nâng ly cùng em nhé (eh)\r\nCho đêm này thêm hé (eh)\r\nChơi vơi trong vòng tay anh, hãy đặt tên em là em bé, oh (oh-uh-oh)\r\nVì đường về còn xa lắm em ơi thấy không mưa còn rơi (mưa còn rơi)\r\nQua đêm nay rồi anh sẽ đưa em về (yeah)\r\nAnh ơi, em hơi say, say\r\nAi sẽ đưa em về? (ối giời)\r\nEm ơi, không may, đêm nay\r\nChưa muốn đưa em về\r\nDon\'t be lonely tonight\r\nBaby, I\'m so high on you (ya)\r\nQua nỗi sầu đêm nay\r\nTrăng gối đầu lên mây\r\nThêm chút rượu và men cuốn muộn phiền đi để tình thêm say (Min)\r\nĐôi mắt buồn sâu cay (JustaTee)\r\nChan chứa tình lâu nay, oh boy (ya)\r\nKhông biết ở nơi đâu (ở nơi đâu)\r\nKhông biết được bao lâu (được bao lâu)\r\nChỉ muốn cùng anh đi đến tận cùng nơi đất trời thâm sâu\r\n(Nơi đất trời thâm sâu)\r\nHuhh-huh-huh-huh-huh (babe)\r\nVì yêu cứ đâm đầu (sao em cứ đâm đầu?)\r\nCứ đâm đầu (sao em cứ đâm đầu?) (ya)\r\nJust, just, just, just\r\nAy\r\nĐen Vâu'),
(6, 'Có Hẹn Với Thanh Xuân', 4, 4, '04:10', '/uploads/songs/co-hen-voi-thanh-xuan.mp3', '/uploads/covers/co-hen-voi-thanh-xuan.jpg', 9000000, '2025-12-20 15:18:56', '2025-12-26 17:01:50', 'Hẹn gặp lại em ngày tháng của sau này\r\nCũng đã đến lúc nghẹn ngào nói lời chào đến mối tình đầu\r\nMột cuốn sách ngọt ngào mà đôi ta từng viết\r\nEm như bông hoa mặt trời có nụ cười đốt cháy lòng người\r\nCó lẽ em là thanh xuân của tôi\r\nTừ ngày mai tôi phải đi\r\nHẹn gặp em trong một khi khác\r\nKỷ niệm đôi ta đành ghi nhớ trong tim\r\nNày người ơi em đừng quên\r\nLần đầu tiên ta bước đến\r\nMình đã chìm vào vùng trời yêu thương\r\nHoh-ooh-whoa-ooh\r\nNếu lỡ mai đây vô tình thấy được nhau\r\nHãy để cho tôi nói với em lời chào\r\nCòn nếu trái tim ta chung nỗi nhớ đong đầy\r\nHẹn gặp lại em ngày tháng của sau này\r\nHah-ah-ah-ah, ah-ah-ah, hah-ah-ah-ah\r\nHah-ah-ah-ah-hoo-hoo, yeah\r\nHah-ah-ah-ah, ah-ah-ah, hah-ah-ah-ah\r\nHẹn gặp lại em ngày tháng của sau này\r\nOh-oh, tôi giờ đang nơi xa, bận lòng nhiều điều về em (worry about you)\r\nEm bình tâm hơn chưa hay là nước mắt nhòe suốt đêm?\r\nMột ngày của em dạo này như thế nào?\r\nThường đi quán quen hay đến nơi ồn ào?\r\nTừ ngày tụi mình kết thúc, bây giờ cuộc sống em ra sao? (Hah-oh)\r\nCòn em thì đã thôi ngừng khóc, ngừng ôm những nỗi chờ mong\r\nKhông còn anh nhưng mà em vẫn okay, yeah\r\nOh yeah, em đã ngủ ngon, không hoài nghi nữa\r\nEm đã cho phép mình được vui\r\nVì em biết anh không muốn thấy em đau\r\nHoh-ooh-whoa-ooh\r\nNếu lỡ mai đây vô tình thấy được nhau\r\nHãy để cho tôi nói với em lời chào (muốn nói với em lời chào)\r\nCòn nếu trái tim ta chung nỗi nhớ đong đầy\r\nHẹn gặp lại em ngày tháng của sau này\r\nEe-yeah\r\nEe-yeah-eh, eh-eh\r\nNếu lỡ mai đây vô tình thấy được nhau (nếu ta có thấy nhau)\r\nHãy để cho tôi nói với em lời chào (muốn nói với em thật nhiều)\r\nCòn nếu trái tim ta (còn nếu) chung nỗi nhớ đong đầy\r\nHẹn gặp lại em ngày tháng của sau này (muốn nói với em thật nhiều)\r\nHah-ah-ah-ah, ah-ah-ah, hah-ah-ah-ah\r\nHah-ah-ah-ah-ah-ah, yeah\r\nHah-ah-ah-ah, ah-ah-ah, hah-ah-ah-ah\r\nHẹn gặp lại em ngày tháng của sau này'),
(7, 'Thiên Lý Ơi', 5, NULL, '04:28', '/uploads/songs/thien-ly-oi.mp3', '/uploads/covers/thien-ly-oi.jpg', 18000000, '2025-12-20 15:18:56', '2025-12-26 17:03:20', 'Ngày hôm nay trời trong xanh\r\nĐẹp như tranh\r\nMình cùng dạo vòng quanh cả thế giới\r\nĐừng vội nhanh\r\nMột hành trình nhật ký yêu thương đời mình\r\nHát vu vơ về tình đầu em ơi\r\nNgày hôm ấy là cô bé tuổi đôi mươi\r\nVậy mà giờ đã lớn trưởng thành hơn\r\nMặc váy cưới\r\nChẳng điều gì dừng bước em tôi\r\nVì người mãi kiêu sa đẹp tuyệt vời\r\nAnh ở vùng quê khu nghèo khó đó\r\nCó trăm điều khó\r\nMuốn lên thành phố nên phải cố\r\nSao cho bụng anh luôn no\r\nThế rồi gặp em\r\nNhững vụn vỡ đã lỡ đêm lại nhớ\r\nNằm mơ gọi tên em\r\nThiên lý ơi\r\nEm có thể ở lại đây không\r\nBiết chăng ngoài trời mưa giông\r\nNhiều cô đơn lắm em\r\nThiên lý ơi\r\nAnh chỉ mong người bình yên thôi\r\nNắm tay ghì chặt đôi môi\r\nRồi ngồi giữa lưng đồi\r\nEm yêu ai\r\nEm đang yêu thương ai\r\nHay em đang cô đơn\r\nChờ mai sau cho tương lai\r\nSao không yêu ngay bây giờ\r\nMang cho anh những ngây thơ\r\nĐêm nay có nằm mơ\r\nBơ vơ như kẻ làm thơ\r\nNgồi một mình để rồi lại ngẩn ngơ\r\nNgười thì về còn người ở lại\r\nMà lòng thì ngẩn ngơ\r\nBầu trời nào mình từng ngọt ngào\r\nRồi khẽ tay nắm tay\r\nÁo em khẽ bay nhẹ lay\r\nAnh ở vùng quê khu nghèo khó đó\r\nCó trăm điều khó\r\nMuốn lên thành phố nên phải cố\r\nSao cho bụng anh luôn no\r\nThế rồi gặp em\r\nNhững vụn vỡ đã lỡ đêm lại nhớ\r\nNằm mơ gọi tên em\r\nMãi cô đơn mất thôi\r\nThiên lý ơi\r\nEm có thể ở lại đây không\r\nBiết chăng ngoài trời mưa giông\r\nNhiều cô đơn lắm em\r\nThiên lý ơi\r\nAnh chỉ mong người bình yên thôi\r\nNắm tay ghị chặt đôi môi\r\nRồi ngồi giữa lưng đồi\r\nNgười là giấc mơ phiêu bồng\r\nLặng lẽ như là gió đông\r\nĐêm lạnh song song\r\nGiờ trời làm má em thêm hồng\r\nMột đời an yên anh thấy nhẹ lòng\r\nTrời ngăn tơ duyên chúng ta thành đôi\r\nThiên lý ơi\r\nEm có thể ở lại đây không\r\nBiết chăng ngoài trời mưa giông\r\nNhiều cô đơn lắm em\r\nThiên lý ơi\r\nAnh chỉ mong người bình yên thôi\r\nNắm tay ghị chặt đôi môi\r\nRồi ngồi giữa lưng đồi'),
(8, 'Trình', 6, 5, '03:52', '/uploads/songs/trinh.mp3', '/uploads/covers/trinh.jpg', 22000000, '2025-12-20 15:18:56', '2025-12-26 16:58:52', 'Fucker\r\nEy ey yeah yeah\r\nỐi zồi ôi ối zồi ôi\r\nTrình là gì mà là trình ai chấm\r\nAnh chỉ biết làm ba mẹ tự hào\r\nXây căn nhà thật to ở một mình hai tấm\r\nỐi zồi ôi ối zồi ôi\r\nCứ lên mạng phán xét tưởng là mình oai lắm\r\nNhìn vào sự nghiệp anh thèm chảy nước miếng\r\nGiống mấy thằng biến thái nó đang rình ai tắm\r\nMua bao nhiêu căn nhà (đếm được đếm được)\r\nNăm bao nhiêu show (đếm được đếm được)\r\nBao nhiêu bài hit (đếm được đếm được)\r\nBao nhiêu trong bank (đếm được đếm được)\r\nMua bao nhiêu căn nhà (đếm được đếm được)\r\nNăm bao nhiêu show (đếm được đếm được)\r\nBao nhiêu là cúp (đếm được đếm được)\r\nCó bao nhiêu fan (không đếm được không đếm được)\r\nHú lần đầu tiên mà nhạc anh được hit\r\nTụi nó nói là do anh may mắn (okey)\r\nRồi anh hit thêm lần thứ hai\r\nThằng này không đẹp trai thì cút\r\nKhi anh hit thêm một lần nữa có ai thấy nó ngang ngang\r\nÝ kiến riêng không hay lắm\r\nHit lần bốn rồi lại hit tới lần năm\r\nÁ á á á con chó này được push\r\nRapper không trình đẹp trai cũng đã quen rồi\r\nRapper làm hề gameshow cũng đã quen rồi\r\nNhạc anh khi xưa OG cũng đã khen rồi\r\nTop trending ừ thì đâu có giá trị cho tới khi có cái tên mày trong đó\r\nKhoe hoài mà tưởng là top năm luôn không đó\r\nBốn năm trước đâu ai tin anh sẽ tìm được chỗ đứng\r\nBốn năm sau anh lại làm nó khó chịu với cái chỗ mà anh đang ngồi\r\nHay anh phải ngưng làm nhạc tình\r\nChuyển qua làm nhạc mai thuý bật cho mấy thằng nhõi nghiến\r\nLàm cap rap nói về cuộc sống giang hồ\r\nSố tiền mà anh nhận được sau khi buôn về mỗi chuyến\r\nNhạc viral mà nó nói không kỹ năng\r\nMấy thằng ngu ngốc ơi làm ơn đi ra khỏi giếng\r\nNếu mà nói idol của mày không cần viral\r\nVậy đi thi làm gì nếu không vì sự nổi tiếng\r\nYah miệng mở ra nói toàn là điêu anh cứ tưởng nó là chiến thần chốt sale (chốt sale)\r\nAnh làm nhạc có nội dung và sâu sắc đứng ở trên sân khấu mà khán giả họ khóc theo (khóc theo)\r\nNói lyrics của anh viết vô nghĩa hay cái não lâu ngày không xài nó mốc meo\r\nBình luận thật sự có đầu óc\r\nNhưng mà óc này là óc heo\r\nỐi zồi ôi ối zồi ôi\r\nTrình là gì mà là trình ai chấm\r\nAnh chỉ biết làm ba mẹ tự hào\r\nXây căn nhà thật to ở một mình hai tấm\r\nỐi zồi ôi ối zồi ôi\r\nCứ lên mạng phán xét tưởng là mình oai lắm\r\nNhìn vào sự nghiệp anh thèm chảy nước miếng\r\nGiống mấy thằng biến thái nó đang rình ai tắm\r\nMua bao nhiêu căn nhà (đếm được đếm được)\r\nNăm bao nhiêu show (đếm được đếm được)\r\nBao nhiêu bài hit (đếm được đếm được)\r\nBao nhiêu trong bank (đếm được đếm được)\r\nMua bao nhiêu căn nhà (đếm được đếm được)\r\nNăm bao nhiêu show (đếm được đếm được)\r\nBao nhiêu là cúp (đếm được đếm được)\r\nCó bao nhiêu fan (không đếm được không đếm được)\r\nMua bao nhiêu căn nhà\r\nNăm bao nhiêu show\r\nBao nhiêu bài hit (bao nhiêu bài hit)\r\nBao nhiêu trong bank (bao nhiêu trong bank)\r\nMua bao nhiêu căn nhà (căn nhà)\r\nNăm bao nhiêu show (show)\r\nBao nhiêu là cúp\r\nEy ey\r\nAnh thì không thích những thằng nghe nhạc thượng đẳng\r\nChỉ đem âm nhạc của tụi anh ra khai thác\r\nGiấu đằng sau lưng là menu lời khen\r\nCho ai trả tiền hoặc là người nó bias\r\nCó mỗi một câu đó là overrated\r\nMiếng mồi quá ngon nên tụi nó phải nhai nát\r\nLên quá nhiều bài chỉ để nói về anh thôi\r\nVậy tại sao là anh đây mà không phải là ai khác\r\nVì anh hút view hot qua từng ngày\r\n365 giống như anh là Isaac\r\nCoi như đánh đổi\r\nHôm qua bước ra khỏi ngân hàng chắc tầm đâu mười lăm tỉ trong my bag\r\nXin phép thoát vai showbiz một hôm\r\nPhải quay trở lại với hiphop vào vai ác\r\nXin phép thoát vai showbiz một hôm\r\nPhải quay trở lại với hiphop vào vai ác\r\nĐi tới mọi nơi như là fan Bigbang\r\nNgười ta gọi anh là VIP\r\nReup nhạc anh được một trăm củ khoai\r\nNhạc nó tự up được một kí khoai mì\r\nFlow của anh đi để lại băng giá thôi\r\nCold and colder như là dân IT\r\nTrong khi tụi nó vẫn còn đang nói nhiều quá\r\nI just do it kiếm nhiều check như Nike\r\nAi muốn so gì\r\nHọc vấn hay là ai đang nhiều bằng\r\nPen hơn nhiều thằng flow hơn nhiều thằng\r\nĐể mà so về nhạc hot thì cũng hơn nhiều thằng\r\nNếu mà muốn nói là hiphop phải kiêu căng là ngông nghênh là ngổ ngáo\r\nVậy thì anh không liều bằng\r\nCòn nếu hiphop là lối sống là văn hóa thì nó lại dễ ăn quá\r\nĐưa anh em anh đi xa cũng đã hơn hơi nhiều thằng (bang)\r\nSold-out mọi show anh lạng qua\r\nNghe đâu là đang năm hạn ha\r\nCái tên của anh lớn quá nhanh\r\nBất lợi của nó là phải ca rạn da\r\nNếu mà tụi mày đang muốn hạ anh xuống\r\nChắc phải đem thêm vài bao đạn K\r\nKhông cần xếp hạng anh hạng mấy\r\nBởi vì anh là một sao hạng\r\nSao hạng\r\nSao hạng\r\nSao hạng\r\nSao sao\r\nSao hạng\r\nSao hạng\r\nSao hạng\r\nSao sao\r\nSao hạng\r\nSao hạng\r\nSao hạng\r\nSao sao\r\nSao sao sao sao sao sao\r\nYah yah\r\nAnh đâu tới đây hạ bệ ai em nghe ở đâu ra vậy (đâu đâu)\r\nAnh chỉ muốn đi con đường mới\r\nJealousy in the air \'cause I\'m on my way\r\nNếu trình cao là không ai biết tên\r\nSân khấu lớn chưa bao giờ được thấy\r\nViệc giỏi nhất cũng không phải để kiếm ra tiền\r\nThì thôi anh anh là thằng overrated\r\nHah\r\nTalk to me nice or don\'t talk at all\r\nThe fuck fuck you wanna say (fuck fuck fuck what the fuck huh)\r\nNhững người đang thắc mắc\r\nSao anh lên nhanh quá đều là những người không đua\r\nNhìn đường anh đi là một thảm cỏ xanh\r\nHọ đâu thấy những ngày trong mưa\r\nVà sẽ còn cao hơn thế nữa\r\nNên đừng hỏi là bây giờ xong chưa\r\nKhông phải tiếng của xe bán kem\r\nĐó là tiếng dây chuyền anh đong đưa ye-ah'),
(9, 'Ngủ Một Mình', 6, NULL, '03:30', '/uploads/songs/ngu-mot-minh.mp3', '/uploads/covers/ngu-mot-minh.jpg', 7000000, '2025-12-20 15:18:56', '2025-12-26 17:04:07', 'Hãy ở lại với anh thêm một ngày nữa thôi\r\nVì anh không muốn phải ngủ một mình đêm nay đâu\r\nBên ngoài và uống say, hay là ta nằm đây cả đêm (y-yeah)\r\nChỉ là anh không muốn phải ngủ một mình đêm nay\r\nYeah-yeah-y-yeah (y-yeah), yeah-yeah-y-yeah-eh\r\nBaby nói cho anh nghe, em hãy nói cho anh nghe những điều mà\r\nĐiều em muốn sau khi đêm nay trôi qua là một trái tim hay những món quà\r\nEm muốn đôi tay anh đặt ở những nơi đâu, anh đã nhắm đôi môi từ những ngày đầu\r\nI\'m needing all your love\r\nNhưng em sẽ chẳng thể thấy anh khi qua ngày mai\r\nBởi vì Thiên Bình đây chẳng thể nào bên ai mãi mãi\r\nHãy hứa không nói cho ai\r\nHình em gửi anh làm sao mà có thể, yeah-yeah\r\nThay những khi mà em đằng sau nằm ôm anh\r\nHãy ở lại với anh thêm một ngày nữa thôi\r\nVì anh không muốn phải ngủ một mình đêm nay đâu\r\nBên ngoài và uống say, hay là ta nằm đây cả đêm (y-yeah)\r\nBởi vì anh không muốn phải ngủ một mình đêm nay\r\nChẳng phải đón hay đưa\r\nCứ việc lên nhà anh như cách em từng đến thôi\r\nĐây đâu phải là lần duy nhất của em ở đây\r\nOoh, dù là ta chẳng phải của nhau\r\nNhưng chỉ mình anh được hôn lên tóc em\r\nChẳng phải quay mặt về nơi khác lúc em thay đồ\r\nAh woah, ah woah-ah-ah\r\nAnh biết điều đó là sai nhưng không cho em gặp ai\r\nPhải ở bên anh đến ngày mai, yeah (oh)\r\nPhải ở đến ngày mai, yeah (mai yeah, mai yeah)\r\nBởi vì chúng ta cũng chỉ là hai con người cô đơn đến với nhau\r\nHãy ở lại với anh thêm một ngày nữa thôi\r\nVì anh không muốn phải ngủ một mình đêm nay đâu\r\nBên ngoài và uống say, hay là ta nằm đây cả đêm (y-yeah)\r\nChỉ là anh không muốn phải ngủ một mình đêm nay\r\nBởi vì anh không muốn phải ngủ một mình đêm nay\r\nChỉ là anh không muốn phải ngủ một mình đêm nay\r\nYeah-yeah-y-yeah (y-yeah), yeah-yeah-y-yeah-y-yeah\r\nBaby nói cho anh nghe, baby nói cho anh nghe những điều là'),
(10, 'Bùa Yêu', 7, NULL, '04:05', '/uploads/songs/bua-yeu.mp3', '/uploads/covers/bua-yeu.jpg', 11000000, '2025-12-20 15:18:56', '2025-12-26 17:04:38', 'Lâu nay em luôn một mình\r\nLâu không quan tâm đến người nào\r\nNhưng tim em đang ồn ào\r\nKhi anh quay sang nói lời chào\r\nHẹn hò ngay với em đi\r\nĐâu có mấy khi\r\nSao không yêu nhau\r\nBây giờ yêu luôn đi\r\nTin không em đang thật lòng\r\nEm nghe đây anh nói đi anh\r\nYêu hay không yêu\r\nKhông yêu hay yêu nói một lời\r\nBên nhau hay thôi\r\nChỉ một lời uh huh\r\nKhông yêu yêu hay không yêu\r\nKhông yêu hay yêu nói một lời thôi\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nEm luôn vui em hiền lành\r\nKhông hay đi chơi nấu ăn ngon\r\nEm may em thêu thùa này\r\nYêu thương ai yêu hết lòng này\r\nChỉ là anh đấy thôi anh\r\nDuy nhất riêng anh\r\nXưa nay bên em\r\nBao người vây xung quanh\r\nTin không em đang thật lòng\r\nEm nghe đây anh nói đi anh\r\nYêu hay không yêu\r\nKhông yêu hay yêu nói một lời\r\nBên nhau hay thôi\r\nChỉ một lời uh huh\r\nKhông yêu yêu hay không yêu\r\nKhông yêu hay yêu nói một lời thôi\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHỡi anh có hay biết rằng\r\nThời gian cứ thế trôi nào có chờ\r\nChúng ta thì cần người ở bên\r\nSẻ chia những phút giây trong đời\r\nYêu hay không yêu\r\nKhông yêu hay yêu nói một lời\r\nKhông yêu yêu hay không yêu\r\nKhông yêu hay yêu nói một lời thôi\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì\r\nHuh huh huh\r\nNếu anh có yêu nói đi ngại gì'),
(11, 'Như Anh Đã Thấy Em', 8, NULL, '04:20', '/uploads/songs/nhu-anh-da-thay-em.mp3', '/uploads/covers/nhu-anh-da-thay-em.jpg', 5000000, '2025-12-20 15:18:56', '2025-12-26 17:05:25', 'Anh ngắm nhìn thêm mây sao và màn đêm long lanh của anh (của anh)\r\nAnh lướt nhẹ trên từng câu hát nơi những giấc mơ đã từng thổn thức với anh\r\nVà đôi khi anh trầm ngâm thật lâu vì nơi sâu nhất trong thâm tâm của anh\r\nAnh vẽ lại những bức tranh ẩn chứa sâu trong sắc màu mỏng manh mỏng manh\r\nĐể nhìn lại tháng năm xa rồi còn mình anh thôi\r\nMà giờ sao chẳng nói nên lời\r\nBóng em hôm nào dần theo làn mây khuất trôi\r\nVới anh những ngày mà mình rong chơi\r\nLà thời gian đẹp nhất trong đời\r\nDù sao tất cả cũng qua rồi\r\nVậy nên hãy xóa hết đi phiền lo đang bủa vây\r\nDù ta sẽ nhiều khi không gặp may\r\nChỉ cần em tin tất cả sẽ tốt thôi và hãy cứ bước đến những nơi thuộc về em\r\nĐể anh lại hát mãi như là khi em ở đây dù ta theo thời gian cũng đổi thay\r\nRồi điều tuyệt nhất sẽ đến với em như anh đã tìm thấy em\r\nĐể nhìn lại tháng năm xa rồi còn mình anh thôi\r\nMà giờ sao chẳng nói nên lời\r\nBóng em hôm nào dần theo làn mây khuất trôi\r\nVới anh những ngày mà mình rong chơi\r\nLà thời gian đẹp nhất trong đời\r\nDù sao tất cả cũng qua rồi\r\nVậy nên hãy xóa hết đi phiền lo đang bủa vây\r\nDù ta sẽ nhiều khi không gặp may\r\nChỉ cần em tin tất cả sẽ tốt thôi và hãy cứ bước đến những nơi thuộc về em\r\nĐể anh được hát mãi như là khi em ở đây dù ta theo thời gian cũng đổi thay\r\nRồi điều tuyệt nhất sẽ đến với em như anh đã tìm thấy em no hoh\r\nVà một lần cuối để mình không cần mạnh mẽ\r\nDù sao ta cũng đã yêu nhiều thế\r\nCó rất nhiều điều mà anh vẫn chưa nói ra\r\nVì lần cuối cùng được nắm tay em bước qua khắp nẻo đường\r\nNgắm hoàng hôn chạm bờ vai em như khoảnh khắc đầu tiên em đến\r\nAnh cất nụ cười người vào trang kỉ niệm như em vẫn còn bên anh\r\nVậy nên hãy xóa hết đi phiền lo đang bủa vây\r\nDù ta sẽ nhiều khi không gặp may\r\nChỉ cần em tin tất cả sẽ tốt thôi và hãy cứ sống hết cho những gì em muốn (tất cả sẽ tốt thôi)\r\nAnh sẽ luôn hát mãi như là khi em ở đây dù ta theo thời gian cũng sẽ đổi thay\r\nĐiều tuyệt nhất sẽ đến với em như anh đã tìm thấy em\r\nTháng năm u sầu cùng màn đêm thâu\r\nDòng kí ức sao đã loang màu\r\nBiết đến khi nào để ta lại trông thấy nhau\r\nVới em những ngày mà mình rong chơi\r\nLà thời gian đẹp nhất trong đời\r\nDù sao tất cả cũng qua rồi'),
(12, 'See Tình', 9, NULL, '03:48', '/uploads/songs/see-tinh.mp3', '/uploads/covers/see-tinh.jpg', 14000000, '2025-12-20 15:18:56', '2025-12-26 17:05:50', 'Uầy uầy uây uây\r\nSao mới gặp lần đầu mà đầu mình quay quay?\r\nAnh ơi anh à\r\nAnh bỏ bùa gì mà lại làm em yêu vậy?\r\nBae bae bae bae\r\nEm nói từ đầu baby can you stay\r\nMai đi coi ngày\r\nXem cưới ngày nào thì nhà mình đông con vậy?\r\nNếu như một câu nói có thể khiến anh vui\r\nSẽ suốt ngày luôn nói không ngừng để anh cười\r\nNếu em làm như thế trông em có hâm không? (Điên-điên-điên lắm)\r\nĐem ngay vô nhà thương\r\nĐem ngay vô nhà thương\r\nĐem ngay vô nhà anh để thương!\r\nGiây phút em gặp anh là em biết em see tình\r\nTình tình tình tang tang tính\r\nTang tình tình tình tang tang tang\r\nGiây phút em gặp anh là em biết em see tình\r\nTình đừng tình toan toan tính\r\nToang tình mình tình tan tan tan tình\r\nYah, yah\r\nAnh tính sao, giờ đây anh tính sao?\r\nYah, yah\r\nAnh tính sao, giờ đây anh tính sao?\r\nTới đâu thì tới, tới đâu thì tới\r\nEm cũng chẳng biết tới đâu\r\nNếu yêu là khó, không yêu cũng khó\r\nEm cũng chẳng biết thế nào\r\nHôm nay tia cực tím xuyên qua trời đêm\r\n(Nhưng) anh như tia cực hiếm xuyên ngay vào tim\r\nẤy ấy ấy chết em rồi\r\nẤy ấy chết thật thôi\r\nNếu như một câu nói có thể khiến anh vui\r\nNói thêm một câu nữa có khi khiến anh buồn\r\nNếu em làm như thế trông em có hâm không? (Điên-điên-điên lắm)\r\nĐem ngay vô nhà thương\r\nĐem ngay vô nhà thương\r\nĐem ngay vô nhà anh để thương!\r\nGiây phút em gặp anh là em biết em see tình\r\nTình tình tình tang tang tính\r\nTang tình tình tình tang tang tang\r\nGiây phút em gặp anh là em biết em see tình\r\nTình đừng tình toan toan tính\r\nToang tình mình tình tan tan tan tình\r\nYah, yah\r\nAnh tính sao, giờ đây anh tính sao?\r\nYah, yah\r\nAnh tính sao, giờ đây anh tính sao?');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@musichub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(2, 'user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'user', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(3, 'user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'user', '2025-12-20 15:18:56', '2025-12-20 15:18:56'),
(4, 'Thanh Hương', 'thanhhuong@gmail.com', '$2y$10$f2c7o0hE2AS9qZ8gktpKJu0DkZj8rhVf4tNSTSzykSTdvJgHFwisC', NULL, 'user', '2025-12-21 03:07:20', '2025-12-21 03:07:20');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `songs`
--
ALTER TABLE `songs` ADD FULLTEXT KEY `idx_title` (`title`);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

DROP VIEW IF EXISTS `dashboard_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `users`) AS `total_users`, (select count(0) from `songs`) AS `total_songs`, (select count(0) from `artists`) AS `total_artists`, (select count(0) from `albums`) AS `total_albums`, (select sum(`songs`.`play_count`) from `songs`) AS `total_plays`, (select count(0) from `listening_history` where (cast(`listening_history`.`listened_at` as date) = curdate())) AS `today_plays` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `popular_songs`
--
DROP TABLE IF EXISTS `popular_songs`;

DROP VIEW IF EXISTS `popular_songs`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `popular_songs`  AS SELECT `s`.`id` AS `id`, `s`.`title` AS `title`, `s`.`duration` AS `duration`, `s`.`play_count` AS `play_count`, `s`.`image_url` AS `image_url`, `s`.`file_url` AS `file_url`, `s`.`created_at` AS `created_at`, `a`.`id` AS `artist_id`, `a`.`name` AS `artist_name`, `a`.`avatar` AS `artist_avatar`, `al`.`id` AS `album_id`, `al`.`title` AS `album_title`, `al`.`cover_url` AS `album_cover` FROM ((`songs` `s` left join `artists` `a` on((`s`.`artist_id` = `a`.`id`))) left join `albums` `al` on((`s`.`album_id` = `al`.`id`))) ORDER BY `s`.`play_count` DESC ;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `listening_history`
--
ALTER TABLE `listening_history`
  ADD CONSTRAINT `listening_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `listening_history_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `playlist_songs`
--
ALTER TABLE `playlist_songs`
  ADD CONSTRAINT `playlist_songs_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_songs_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `site_visits`
--
ALTER TABLE `site_visits`
  ADD CONSTRAINT `site_visits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ràng buộc cho bảng `songs`
--
ALTER TABLE `songs`
  ADD CONSTRAINT `songs_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `songs_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
