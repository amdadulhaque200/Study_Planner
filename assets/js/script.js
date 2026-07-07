document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            // For desktop, we could toggle a collapsed state, but for now we just handle mobile
            if(window.innerWidth <= 768) {
                // In mobile, main content doesn't shift, sidebar overlays or pushes
            } else {
                // Desktop toggle logic if needed
                if (sidebar.style.marginLeft === '-260px') {
                    sidebar.style.marginLeft = '0';
                    mainContent.style.marginLeft = '260px';
                } else {
                    sidebar.style.marginLeft = '-260px';
                    mainContent.style.marginLeft = '0';
                }
            }
        });
    }

    // Auto-hide flash messages after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Form confirmation
    const confirmForms = document.querySelectorAll('form[data-confirm]');
    confirmForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});
