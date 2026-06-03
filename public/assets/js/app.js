/**
 * HEMS Core — App JavaScript
 */
(function($) {
    'use strict';

    // ============ SIDEBAR TOGGLE ============
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
            }
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
    }
    // Restore sidebar state
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 992) {
        document.body.classList.add('sidebar-collapsed');
    }

    // ============ DARK MODE ============
    window.toggleDarkMode = function() {
        const html = document.documentElement;
        const current = html.getAttribute('data-bs-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
    };
    // Restore theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);

    // ============ CSRF TOKEN FOR AJAX ============
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': csrfMeta.content }
        });
    }

    // ============ DATATABLES DEFAULTS ============
    if ($.fn.DataTable) {
        $.extend($.fn.DataTable.defaults, {
            pageLength: 25,
            language: {
                search: '',
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_ to _END_ of _TOTAL_',
                paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
            },
            dom: "<'row mb-3'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>"
        });
    }

    // ============ SELECT2 INIT ============
    window.initSelect2 = function(selector, options = {}) {
        $(selector).select2(Object.assign({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select...',
            allowClear: true
        }, options));
    };

    // ============ SWEETALERT HELPERS ============
    window.confirmAction = function(options) {
        return Swal.fire({
            title: options.title || 'Are you sure?',
            text: options.text || '',
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1a56db',
            cancelButtonColor: '#64748b',
            confirmButtonText: options.confirmText || 'Yes, proceed',
            cancelButtonText: 'Cancel'
        });
    };

    window.showToast = function(icon, title) {
        Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 3000, timerProgressBar: true });
    };

    // ============ NOTIFICATION POLLING ============
    function pollNotifications() {
        const badge = document.getElementById('notifBadge');
        if (!badge) return;
        $.get(window.BASE_URL + '/notifications/unread', function(data) {
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }).fail(function() {});
    }
    // Poll every 30s
    setInterval(pollNotifications, 30000);
    pollNotifications();

    // ============ AUTO-DISMISS ALERTS ============
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ============ FORM CONFIRMATION ============
    document.querySelectorAll('form[data-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmAction({ title: this.dataset.confirm || 'Confirm action?' }).then(result => {
                if (result.isConfirmed) this.submit();
            });
        });
    });

})(jQuery);

// Base URL for AJAX
window.BASE_URL = window.BASE_URL || (document.querySelector('meta[name="csrf-token"]')?.closest('head')?.querySelector('title')?.textContent?.includes('HEMS') ? '' : '');

