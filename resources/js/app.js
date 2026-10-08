document.documentElement.classList.add('js');

// Mobile navigation toggle
const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');

navToggle?.addEventListener('click', () => {
    const open = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', String(!open));
    navMenu.classList.toggle('hidden', open);
});

// Header background once the page is scrolled
const header = document.querySelector('[data-header]');
const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 20);
updateHeader();
window.addEventListener('scroll', updateHeader, { passive: true });

// Reveal elements as they enter the viewport
const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.15 },
);

document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));

// Count up numeric figures
document.querySelectorAll('[data-count]').forEach((el) => {
    const target = Number(el.dataset.count);
    if (!Number.isFinite(target) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const counter = new IntersectionObserver(([entry]) => {
        if (!entry.isIntersecting) {
            return;
        }
        counter.disconnect();

        const start = performance.now();
        const tick = (now) => {
            const progress = Math.min((now - start) / 1600, 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3)));
            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };
        requestAnimationFrame(tick);
    });
    counter.observe(el);
});
