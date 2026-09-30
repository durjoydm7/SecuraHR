/**
 * SECURAHR CORE JAVASCRIPT ENGINE
 */
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
});

// Dark/Light Theme Handler
function initTheme() {
    const savedTheme = localStorage.getItem('securahr_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('securahr_theme', newTheme);
}

// Toast Notification Helper
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}