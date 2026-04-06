// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {

    // ── Mobile nav toggle ──────────────────────────────────────
    const toggle    = document.getElementById('mobileToggle');
    const mobileNav = document.getElementById('mobileNav');
    if (toggle && mobileNav) {
        toggle.addEventListener('click', () => {
            mobileNav.classList.toggle('is-open');
            toggle.textContent = mobileNav.classList.contains('is-open') ? '✕' : '☰';
        });
    }

    // ── Каталог dropdown — при клик ───────────────────────────
    const catalogTrigger = document.querySelector('.nav-dropdown__trigger');
    const catalogMenu    = document.querySelector('.nav-dropdown__menu');

    if (catalogTrigger && catalogMenu) {
        catalogTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            catalogMenu.classList.toggle('open');

            // Затвори user menu ако е отворен
            userDropdown?.classList.remove('open');
        });
    }

    // ── User dropdown — при клик ──────────────────────────────
    const userBtn      = document.querySelector('.header__user-btn');
    const userDropdown = document.querySelector('.header__dropdown');

    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');

            // Затвори catalog menu ако е отворен
            catalogMenu?.classList.remove('open');
        });
    }

    // ── Затвори всички при клик извън ─────────────────────────
    document.addEventListener('click', (e) => {
        if (catalogMenu && !catalogTrigger?.contains(e.target)) {
            catalogMenu.classList.remove('open');
        }
        if (userDropdown && !userBtn?.contains(e.target)) {
            userDropdown.classList.remove('open');
        }
    });

    // ── Затвори при Escape ────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            catalogMenu?.classList.remove('open');
            userDropdown?.classList.remove('open');
        }
    });

    // ── Confirm delete ─────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Сигурен ли си?')) {
                e.preventDefault();
            }
        });
    });

    // ── Cart qty auto-submit ───────────────────────────────────
    document.querySelectorAll('.cart-qty-input').forEach(input => {
        input.addEventListener('change', () => {
            input.closest('form')?.submit();
        });
    });

    // ── Flash auto-dismiss ─────────────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s ease';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ── Scroll-reveal product cards ────────────────────────────
    const observer = new IntersectionObserver(
        entries => entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity   = '1';
                e.target.style.transform = 'translateY(0)';
            }
        }),
        { threshold: 0.1 }
    );
    document.querySelectorAll('.product-card, .cat-card').forEach(el => {
        el.style.opacity   = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity .4s ease, transform .4s ease';
        observer.observe(el);
    });

    // ── Live Search Suggestions ────────────────────────────────
    const searchInput    = document.getElementById('headerSearch');
    const suggestionsBox = document.getElementById('searchSuggestions');
    const SEARCH_URL     = document.querySelector('meta[name="site-url"]')?.content
                           || window.location.origin + '/djstore';

    if (searchInput && suggestionsBox) {
        let searchTimer = null;

        searchInput.addEventListener('input', function() {
            const q = this.value.trim();
            clearTimeout(searchTimer);

            if (q.length < 2) {
                suggestionsBox.classList.remove('show');
                suggestionsBox.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(() => {
                fetch(SEARCH_URL + '/search.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';

                        if (data.length === 0) {
                            suggestionsBox.innerHTML =
                                '<div class="suggestion-empty">Няма намерени продукти</div>';
                        } else {
                            data.forEach(item => {
                                const a     = document.createElement('a');
                                a.href      = item.url;
                                a.className = 'suggestion-item';
                                a.innerHTML = `
                                    <img class="suggestion-item__img"
                                         src="${item.image}" alt="${item.name}">
                                    <div class="suggestion-item__info">
                                        <div class="suggestion-item__name">${item.name}</div>
                                        <div class="suggestion-item__brand">${item.brand}</div>
                                    </div>
                                    <span class="suggestion-item__price">${item.price}</span>
                                `;
                                suggestionsBox.appendChild(a);
                            });

                            const all       = document.createElement('a');
                            all.href        = SEARCH_URL + '/pages/catalog.php?search=' + encodeURIComponent(q);
                            all.className   = 'suggestion-all';
                            all.textContent = 'Виж всички резултати →';
                            suggestionsBox.appendChild(all);
                        }

                        suggestionsBox.classList.add('show');
                    })
                    .catch(() => suggestionsBox.classList.remove('show'));
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) &&
                !suggestionsBox.contains(e.target)) {
                suggestionsBox.classList.remove('show');
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                suggestionsBox.classList.remove('show');
                this.blur();
            }
        });
    }

});