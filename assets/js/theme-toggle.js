/**
 * SHOESSTORE - Dark / Light Mode Switcher
 * Instant switching, anti-flicker state, localStorage sync, high-contrast readability
 */

(function() {
    'use strict';

    let isTogglingTheme = false;

    // 1. Get initial or saved theme
    function getStoredTheme() {
        return localStorage.getItem('app_theme') || 'light';
    }

    // 2. Set theme across DOM & UI elements
    window.setAppTheme = function(theme) {
        if (theme !== 'dark' && theme !== 'light') {
            theme = 'light';
        }

        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('app_theme', theme);

        // Update Toggle Buttons Icon
        const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
        toggleBtns.forEach(btn => {
            const moonIcon = btn.querySelector('.theme-icon-moon');
            const sunIcon = btn.querySelector('.theme-icon-sun');

            if (theme === 'dark') {
                if (moonIcon) moonIcon.style.display = 'none';
                if (sunIcon) sunIcon.style.display = 'inline-block';
                btn.setAttribute('title', 'Chuyển sang chế độ Sáng');
                btn.setAttribute('aria-label', 'Chuyển sang chế độ Sáng');
            } else {
                if (moonIcon) moonIcon.style.display = 'inline-block';
                if (sunIcon) sunIcon.style.display = 'none';
                btn.setAttribute('title', 'Chuyển sang chế độ Tối');
                btn.setAttribute('aria-label', 'Chuyển sang chế độ Tối');
            }
        });

        // Update dropdown menu badge labels
        const themeLabels = document.querySelectorAll('.theme-mode-label');
        themeLabels.forEach(label => {
            if (theme === 'dark') {
                label.textContent = 'Tối';
                label.className = 'badge bg-warning text-dark rounded-pill theme-mode-label fw-bold';
            } else {
                label.textContent = 'Sáng';
                label.className = 'badge bg-secondary rounded-pill theme-mode-label fw-bold';
            }
        });

        // Dispatch themeChanged event
        window.dispatchEvent(new CustomEvent('appThemeChanged', { detail: { theme: theme } }));
    };

    // 3. Global toggle function (Safe from double-firing)
    window.toggleTheme = function() {
        if (isTogglingTheme) return;
        isTogglingTheme = true;
        setTimeout(() => { isTogglingTheme = false; }, 250);

        const current = document.documentElement.getAttribute('data-theme') || localStorage.getItem('app_theme') || 'light';
        const next = (current === 'dark') ? 'light' : 'dark';
        window.setAppTheme(next);

        if (window.showVoucherToast) {
            showVoucherToast(next === 'dark' ? '🌙 Đã bật giao diện Ban Đêm (Tối)' : '☀️ Đã bật giao diện Ban Ngày (Sáng)', 'info');
        }
    };

    // 4. Initialize on DOM Ready
    function initTheme() {
        const savedTheme = getStoredTheme();
        window.setAppTheme(savedTheme);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // 5. Global Event Delegation for all theme buttons (No duplicate events)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.theme-toggle-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            window.toggleTheme();
        }
    });

})();
