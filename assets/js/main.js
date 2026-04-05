/**
 * FeyFay Media - Public site JavaScript
 * Responsive navbar: hamburger toggle and close on link click
 */
(function() {
    'use strict';

    if (window.FeyFayToast) return;

    function ensureContainer() {
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        return container;
    }

    function normalizeType(type) {
        var allowed = ['success', 'error', 'info', 'warning'];
        return allowed.indexOf(type) !== -1 ? type : 'info';
    }

    window.FeyFayToast = {
        show: function(message, type, timeoutMs) {
            if (!message) return;
            var container = ensureContainer();
            var toast = document.createElement('div');
            var toastType = normalizeType(String(type || 'info').toLowerCase());
            toast.className = 'toast toast-' + toastType;
            toast.setAttribute('role', 'status');

            var text = document.createElement('div');
            text.className = 'toast-message';
            text.textContent = String(message);
            toast.appendChild(text);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'toast-close';
            closeBtn.setAttribute('aria-label', 'Close notification');
            closeBtn.textContent = 'x';
            closeBtn.addEventListener('click', function() {
                toast.classList.remove('is-visible');
                setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 160);
            });
            toast.appendChild(closeBtn);

            container.appendChild(toast);
            requestAnimationFrame(function() { toast.classList.add('is-visible'); });

            var delay = typeof timeoutMs === 'number' ? timeoutMs : 3800;
            setTimeout(function() {
                toast.classList.remove('is-visible');
                setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 180);
            }, delay);
        }
    };
})();

(function() {
    'use strict';
    var navToggle = document.getElementById('navToggle');
    var mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        function closeNav() {
            mainNav.classList.remove('is-open');
            navToggle.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('nav-open');
        }
        function openNav() {
            mainNav.classList.add('is-open');
            navToggle.classList.add('is-open');
            navToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('nav-open');
        }
        navToggle.addEventListener('click', function() {
            if (mainNav.classList.contains('is-open')) {
                closeNav();
            } else {
                openNav();
            }
        });
        mainNav.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', closeNav);
        });
        document.addEventListener('click', function(e) {
            if (!mainNav.classList.contains('is-open')) return;
            if (mainNav.contains(e.target) || navToggle.contains(e.target)) return;
            closeNav();
        });
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mainNav.classList.contains('is-open')) closeNav();
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 920 && mainNav.classList.contains('is-open')) closeNav();
        });
    }
})();
