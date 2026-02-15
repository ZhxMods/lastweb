    </main>
    <!-- End Admin Content -->
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Additional admin JS -->
    <script>
    // Mobile sidebar toggle
    function toggleSidebar() {
        document.getElementById('adminSidebar').classList.toggle('mobile-open');
    }
    
    // Initialize DataTables with default settings
    function initDataTable(selector, options = {}) {
        const defaultOptions = {
            pageLength: 25,
            language: {
                search: "<?= __('search') ?>:",
                lengthMenu: "<?= __('show') ?> _MENU_ <?= __('entries') ?>",
                info: "<?= __('showing') ?> _START_ <?= __('to') ?> _END_ <?= __('of') ?> _TOTAL_ <?= __('entries') ?>",
                infoEmpty: "<?= __('no_entries') ?>",
                infoFiltered: "(<?= __('filtered_from') ?> _MAX_ <?= __('total') ?>)",
                paginate: {
                    first: "<?= __('first') ?>",
                    last: "<?= __('last') ?>",
                    next: "<?= __('next') ?>",
                    previous: "<?= __('previous') ?>"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ...options
        };
        
        return $(selector).DataTable(defaultOptions);
    }
    
    // Show alert message
    function showAlert(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.content-header').after(alertHtml);
        
        setTimeout(() => {
            $('.alert').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Confirm action
    function confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
    
    // AJAX helper with CSRF
    function adminAjax(url, data, successCallback, errorCallback) {
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                ...data,
                csrf_token: '<?= generateCSRFToken() ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (successCallback) successCallback(response);
                } else {
                    if (errorCallback) {
                        errorCallback(response);
                    } else {
                        showAlert(response.message || 'An error occurred', 'danger');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                if (errorCallback) {
                    errorCallback({success: false, message: 'Connection error'});
                } else {
                    showAlert('Connection error. Please try again.', 'danger');
                }
            }
        });
    }
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($additionalJS)): ?>
        <?= $additionalJS ?>
    <?php endif; ?>
</body>
</html>
