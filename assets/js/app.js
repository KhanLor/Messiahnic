(() => {
    const root = document.documentElement;
    const storedTheme = localStorage.getItem('messiahnic-theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = storedTheme || (prefersDark ? 'dark' : 'light');

    root.dataset.theme = initialTheme;

    const themeButton = document.querySelector('[data-theme-toggle]');
    const navToggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');

    if (themeButton) {
        themeButton.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            root.dataset.theme = nextTheme;
            localStorage.setItem('messiahnic-theme', nextTheme);
        });
    }

    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            nav.classList.toggle('open');
        });
    }

    const openModalButtons = document.querySelectorAll('[data-open-modal]');
    const closeModalButtons = document.querySelectorAll('[data-close-modal]');

    openModalButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-open-modal');
            const modal = document.querySelector(`[data-modal="${modalId}"]`);
            if (modal) {
                modal.classList.add('modal-open');
                modal.setAttribute('aria-hidden', 'false');
            }
        });
    });

    closeModalButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-modal]');
            if (modal) {
                modal.classList.remove('modal-open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    });
})();
