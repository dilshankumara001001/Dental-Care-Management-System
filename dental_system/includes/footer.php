    </div><!-- /container-fluid -->
</div><!-- /page-content-wrapper -->

</div><!-- /wrapper -->

<!-- Toast + Spinner -->
<div class="toast-container"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
<script>
// Override confirmDelete globally with custom modal
window.confirmDelete = function(msg) {
    // Will be handled by link click handler
    return true;
};

// Enhanced delete handler
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href*="delete="], button[data-delete]').forEach(el => {
        const href = el.getAttribute('href') || el.dataset.delete;
        const name = el.dataset.name || el.closest('tr')?.querySelector('b')?.innerText || 'this item';
        
        el.onclick = function(e) {
            e.preventDefault();
            customConfirm(
                `Delete <b>${name}</b>? This action cannot be undone.`,
                () => { window.location.href = href; },
                { title: 'Confirm Delete', confirmText: '<i class="bi bi-trash"></i> Delete', type: 'danger' }
            );
            return false;
        };
    });
});
</script>
</body>
</html>