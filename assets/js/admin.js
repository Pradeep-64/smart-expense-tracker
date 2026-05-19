document.addEventListener('DOMContentLoaded', () => {
    const themeKey = 'admin-theme';
    const toggle = document.getElementById('themeToggle');
    const saved = localStorage.getItem(themeKey);
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    if (toggle) {
        toggle.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem(themeKey, 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem(themeKey, 'dark');
            }
        });
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
    }

    document.querySelectorAll('.animate-count').forEach((el, i) => {
        el.style.animationDelay = `${i * 0.05}s`;
    });
});

function printReport() {
    window.print();
}
