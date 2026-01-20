/* =====================================================
   Chức năng: xử lý thao tác trang quản trị (Admin)
===================================================== */

/* ========== XOÁ BÀI HÁT ========== */
function deleteSong(songId) {
  if (!confirm("Bạn có chắc muốn xoá bài hát này không?")) return;

  fetch("../admin/delete_song.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "id=" + songId,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Xoá bài hát thành công!");
        location.reload();
      } else {
        alert("Xoá thất bại!");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Có lỗi xảy ra!");
    });
}

/* ========== XOÁ NGHỆ SĨ ========== */
function deleteArtist(artistId) {
  if (!confirm("Bạn có chắc muốn xoá nghệ sĩ này không?")) return;

  fetch("../admin/delete_artist.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "id=" + artistId,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Xoá nghệ sĩ thành công!");
        location.reload();
      } else {
        alert("Xoá thất bại!");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Có lỗi xảy ra!");
    });
}

/* ========== XOÁ ALBUM ========== */
function deleteAlbum(albumId) {
  if (!confirm("Bạn có chắc muốn xoá album này không?")) return;

  fetch("../admin/delete_album.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "id=" + albumId,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Xoá album thành công!");
        location.reload();
      } else {
        alert("Xoá thất bại!");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Có lỗi xảy ra!");
    });
}





/* ========== DROPDOWN FUNCTIONALITY ========== */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin JS loaded');
    
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // User dropdown functionality
    const userBtn = document.querySelector('.user-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (userBtn && dropdownMenu) {
        console.log('User dropdown elements found');
        
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Dropdown clicked');
            
            // Toggle dropdown visibility
            if (dropdownMenu.style.display === 'block') {
                dropdownMenu.style.display = 'none';
            } else {
                dropdownMenu.style.display = 'block';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
        
        // Prevent dropdown close when clicking inside
        // dropdownMenu.addEventListener('click', function(e) {
        //     e.stopPropagation();
        // });
    } else {
        console.log('User dropdown elements NOT found');
        console.log('User button:', userBtn);
        console.log('Dropdown menu:', dropdownMenu);
    }
    
    // Notification button
    const notificationBtn = document.querySelector('.btn-notification');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            alert('Thông báo: Bạn có 3 thông báo mới!');
        });
    }
});



