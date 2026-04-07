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

    document.body.classList.add('motion-ready');

    const revealTargets = Array.from(
        document.querySelectorAll('.hero, .section, .panel, .card, .kpi, .stat, .list-stack .stack-item')
    );

    revealTargets.forEach((element, index) => {
        element.classList.add('reveal-on-scroll');
        element.style.setProperty('--reveal-delay', `${Math.min(index * 35, 280)}ms`);
    });

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            { threshold: 0.14, rootMargin: '0px 0px -8% 0px' }
        );

        revealTargets.forEach((element) => {
            revealObserver.observe(element);
        });
    } else {
        revealTargets.forEach((element) => {
            element.classList.add('is-visible');
        });
    }

    const homeCard = document.querySelector('[data-home-card]');
    if (homeCard && window.matchMedia('(prefers-reduced-motion: reduce)').matches === false) {
        homeCard.addEventListener('mousemove', (event) => {
            const rect = homeCard.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width;
            const y = (event.clientY - rect.top) / rect.height;
            const rotateY = (x - 0.5) * 5;
            const rotateX = (0.5 - y) * 5;
            homeCard.style.transform = `perspective(900px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg)`;
        });

        homeCard.addEventListener('mouseleave', () => {
            homeCard.style.transform = '';
        });
    }

    const countUpElements = Array.from(document.querySelectorAll('[data-count-up]'));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const formatCountValue = (value, suffix) => {
        return `${Math.round(value).toLocaleString()}${suffix}`;
    };

    const startCountUp = (element) => {
        if (element.dataset.countStarted === '1') {
            return;
        }
        element.dataset.countStarted = '1';

        const rawTarget = Number(element.getAttribute('data-count-target') || '0');
        const minValue = Number(element.getAttribute('data-count-min') || '0');
        const target = Math.max(rawTarget, minValue);
        const suffix = element.getAttribute('data-count-suffix') || '';
        const duration = 1400;

        if (reduceMotion || target <= 0) {
            element.textContent = formatCountValue(target, suffix);
            return;
        }

        const startTime = performance.now();
        const step = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            element.textContent = formatCountValue(current, suffix);

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    };

    if (countUpElements.length) {
        if ('IntersectionObserver' in window && reduceMotion === false) {
            const countObserver = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        startCountUp(entry.target);
                        observer.unobserve(entry.target);
                    });
                },
                { threshold: 0.6 }
            );

            countUpElements.forEach((element) => {
                countObserver.observe(element);
            });
        } else {
            countUpElements.forEach((element) => {
                startCountUp(element);
            });
        }
    }

    const showAuthTransition = (message) => {
        const existing = document.querySelector('[data-auth-transition]');
        if (existing) {
            existing.remove();
        }

        const overlay = document.createElement('div');
        overlay.className = 'auth-transition';
        overlay.setAttribute('data-auth-transition', 'true');
        overlay.innerHTML = [
            '<div class="auth-transition-card" role="status" aria-live="polite">',
            '  <div class="auth-transition-spinner" aria-hidden="true"></div>',
            `  <div class="auth-transition-text">${message}</div>`,
            '  <div class="auth-transition-progress"><span></span></div>',
            '</div>',
        ].join('');

        document.body.appendChild(overlay);
        requestAnimationFrame(() => {
            overlay.classList.add('is-visible');
        });
    };

    const loginForm = document.querySelector('[data-login-delay]');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            if (loginForm.dataset.pending === 'true') {
                return;
            }

            event.preventDefault();
            loginForm.dataset.pending = 'true';

            const submitButton = loginForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }

            showAuthTransition('Signing in...');
            setTimeout(() => {
                loginForm.submit();
            }, 3000);
        });
    }

    const logoutLink = document.querySelector('[data-logout-delay]');
    if (logoutLink) {
        logoutLink.addEventListener('click', (event) => {
            event.preventDefault();

            if (logoutLink.dataset.pending === 'true') {
                return;
            }

            logoutLink.dataset.pending = 'true';
            showAuthTransition('You are already logged out.');

            setTimeout(() => {
                window.location.href = logoutLink.href;
            }, 3000);
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
