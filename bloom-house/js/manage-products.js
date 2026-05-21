// ===== Delete Modal =====
function confirmDelete(id, name) {
    var modal = document.getElementById('deleteModal');
    var msg = document.getElementById('deleteModalMsg');
    var btn = document.getElementById('deleteConfirmBtn');

    if (msg) {
        msg.textContent = 'Are you sure you want to delete "' + name + '"?';
    }

    if (btn) {
        btn.href = 'manage-products.php?delete=' + encodeURIComponent(id);
    }

    if (modal) {
        modal.classList.add('open');
        modal.classList.add('active');
        modal.style.display = 'flex';
    }
}

function closeDeleteModal() {
    var modal = document.getElementById('deleteModal');

    if (modal) {
        modal.classList.remove('open');
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

// Close modal when clicking the overlay background
window.addEventListener('click', function (event) {
    var modal = document.getElementById('deleteModal');

    if (modal && event.target === modal) {
        closeDeleteModal();
    }
});

// ===== Toast =====
function showToast(msg) {
    var toast = document.getElementById('toast');
    var toastMsg = document.getElementById('toastMsg');

    if (!toast || !toastMsg) return;

    toastMsg.textContent = msg;
    toast.classList.add('show');

    setTimeout(function () {
        toast.classList.remove('show');
    }, 2800);
}

// Auto-show toast if success message exists in URL
(function () {
    var params = new URLSearchParams(window.location.search);
    var msg = params.get('msg');

    if (msg) {
        showToast(msg);
    }
})();
