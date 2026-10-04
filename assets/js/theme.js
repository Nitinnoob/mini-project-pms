function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    try {
        localStorage.setItem('pms-theme', isDark ? 'dark' : 'light');
    } catch (e) {}
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }
    window.dispatchEvent(new Event('themeToggled'));
}

(function () {
    const isDark = document.documentElement.classList.contains('dark');
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }
})();
