/* =====================================================================
   FILE: js/main.js
   Vanilla JavaScript only - no frameworks.
   ===================================================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* --------------------- mobile navigation --------------------- */
    var navToggle = document.getElementById('navToggle');
    var mainNav   = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            var open = mainNav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* ----------------------- account menu ------------------------ */
    var chip = document.getElementById('userChip');
    var drop = document.getElementById('userDropdown');
    if (chip && drop) {
        chip.addEventListener('click', function (ev) {
            ev.stopPropagation();
            drop.classList.toggle('open');
        });
        document.addEventListener('click', function () { drop.classList.remove('open'); });
        drop.addEventListener('click', function (ev) { ev.stopPropagation(); });
    }

    /* -------------------- dismissible messages ------------------- */
    document.querySelectorAll('.flash-close').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.parentElement.remove(); });
    });
    setTimeout(function () {
        document.querySelectorAll('.flash').forEach(function (f) {
            f.style.transition = 'opacity .4s, transform .4s';
            f.style.opacity = '0';
            f.style.transform = 'translateY(-8px)';
            setTimeout(function () { f.remove(); }, 400);
        });
    }, 6000);

    /* ------------------------ toast helper ----------------------- */
    function toast(message, type) {
        var host = document.getElementById('toastHost');
        if (!host) {
            host = document.createElement('div');
            host.id = 'toastHost';
            host.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:999;display:flex;flex-direction:column;gap:10px';
            document.body.appendChild(host);
        }
        var box = document.createElement('div');
        var accent = type === 'error' ? '#ff4d6d' : '#4ef58a';
        box.textContent = message;
        box.style.cssText =
            'padding:13px 18px;border-radius:10px;font-size:.9rem;max-width:320px;' +
            'background:#141727;color:#e9ebf5;border-left:3px solid ' + accent + ';' +
            'box-shadow:0 14px 34px rgba(0,0,0,.5);opacity:0;transform:translateX(24px);' +
            'transition:opacity .3s,transform .3s';
        host.appendChild(box);
        requestAnimationFrame(function () { box.style.opacity = '1'; box.style.transform = 'none'; });
        setTimeout(function () {
            box.style.opacity = '0';
            box.style.transform = 'translateX(24px)';
            setTimeout(function () { box.remove(); }, 300);
        }, 3200);
    }

    function updateBadge(selector, count) {
        var link = document.querySelector(selector);
        if (!link) return;
        var badge = link.querySelector('.badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge';
                link.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }

    /* ---------------- add to cart without a reload ---------------- */
    document.querySelectorAll('.js-add-cart').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (btn.classList.contains('disabled')) return;

            var body = new FormData();
            body.append('action', 'add');
            body.append('game_id', btn.dataset.game);
            body.append('quantity', btn.dataset.qty || 1);
            body.append('csrf_token', window.CSRF_TOKEN || '');
            body.append('ajax', '1');

            var original = btn.textContent;
            btn.textContent = 'Adding...';
            btn.classList.add('disabled');

            fetch(window.BASE_URL + 'backend/cart_actions.php', { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.login_required) {
                        window.location.href = window.BASE_URL + 'login.php';
                        return;
                    }
                    toast(data.message, data.ok ? 'success' : 'error');
                    updateBadge('a[href$="cart.php"].icon-btn', data.cart_count);
                    btn.textContent = data.ok ? 'In cart' : original;
                    if (!data.ok) btn.classList.remove('disabled');
                    else setTimeout(function () {
                        btn.textContent = original;
                        btn.classList.remove('disabled');
                    }, 1600);
                })
                .catch(function () {
                    toast('Could not reach the server.', 'error');
                    btn.textContent = original;
                    btn.classList.remove('disabled');
                });
        });
    });

    /* ------------------ wishlist heart toggling ------------------- */
    document.querySelectorAll('.js-wish').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();

            var body = new FormData();
            body.append('action', 'toggle');
            body.append('game_id', btn.dataset.game);
            body.append('csrf_token', window.CSRF_TOKEN || '');
            body.append('ajax', '1');

            fetch(window.BASE_URL + 'backend/wishlist_actions.php', { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.login_required) {
                        window.location.href = window.BASE_URL + 'login.php';
                        return;
                    }
                    btn.classList.toggle('saved', !!data.in_wishlist);
                    btn.innerHTML = data.in_wishlist ? '&#9829;' : '&#9825;';
                    toast(data.message, data.ok ? 'success' : 'error');
                    updateBadge('a[href$="wishlist.php"].icon-btn', data.wishlist_count);
                })
                .catch(function () { toast('Could not reach the server.', 'error'); });
        });
    });

    /* --------------- filters submit as you change them ------------ */
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.querySelectorAll('select, input[type=checkbox], input[type=radio]').forEach(function (field) {
            field.addEventListener('change', function () { filterForm.submit(); });
        });
    }

    /* ------------------ price range live readout ------------------ */
    var maxPrice = document.getElementById('maxPrice');
    var maxOut   = document.getElementById('maxPriceOut');
    if (maxPrice && maxOut) {
        maxPrice.addEventListener('input', function () { maxOut.textContent = '$' + maxPrice.value; });
    }

    /* -------------- confirm before destructive actions ------------ */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            if (!window.confirm(el.dataset.confirm)) ev.preventDefault();
        });
    });

    /* ------------- password match check on the sign up form ------- */
    var regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (ev) {
            var pw = regForm.querySelector('[name=password]').value;
            var cf = regForm.querySelector('[name=confirm_password]').value;
            if (pw.length < 6) {
                ev.preventDefault();
                toast('Password must be at least 6 characters.', 'error');
            } else if (pw !== cf) {
                ev.preventDefault();
                toast('The two passwords do not match.', 'error');
            }
        });
    }

    /* -------------- reveal cards as they scroll in ---------------- */
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var seen = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'none';
                    seen.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.game-grid .game-card').forEach(function (card, i) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(16px)';
            card.style.transition = 'opacity .45s ease ' + (i % 6) * 0.05 + 's, transform .45s ease ' + (i % 6) * 0.05 + 's';
            seen.observe(card);
        });
    }
});
