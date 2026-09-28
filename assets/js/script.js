// Web Studio smooth page-exit transition
document.addEventListener('DOMContentLoaded', function () {

    const loader = document.getElementById('pageLoader');

    if (!loader) {
        return;
    }

    document.querySelectorAll('a').forEach(function (a) {

        const href = a.getAttribute('href');

        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('http') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            href.startsWith('javascript:') ||
            a.target === '_blank'
        ) {
            return;
        }

        a.addEventListener('click', function (e) {

            e.preventDefault();

            loader.classList.remove('is-hidden');

            setTimeout(function () {
                window.location.href = href;
            }, 260);

        });

    });

});

// Web Studio image fade-in as each image finishes loading
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('img').forEach(function (img) {

        img.classList.add('img-fade');

        if (img.complete) {
            img.classList.add('img-loaded');
            return;
        }

        img.addEventListener('load', function () {
            img.classList.add('img-loaded');
        });

    });

});

document.querySelectorAll('a[href^="#"]').forEach(function (a) {

    a.addEventListener('click', function (e) {

        e.preventDefault();

        document
            .querySelector(a.getAttribute('href'))
            ?.scrollIntoView({
                behavior: 'smooth'
            });

    });

});


// Web Studio page loader

window.addEventListener('load', function () {

    const loader = document.getElementById('pageLoader');

    if (!loader) {
        return;
    }

    setTimeout(function () {

        loader.classList.add('is-hidden');

    }, 300);

});

// Safety net: never leave the logo loader stuck if one slow file (font, map, video) delays the "load" event
setTimeout(function () {

    const loader = document.getElementById('pageLoader');

    if (loader) {
        loader.classList.add('is-hidden');
    }

}, 4000);

// Re-hide the loader if the page is restored from the back/forward cache
window.addEventListener('pageshow', function (e) {

    const loader = document.getElementById('pageLoader');

    if (loader && e.persisted) {
        loader.classList.add('is-hidden');
    }

});

// Web Studio scroll reveal animations
document.addEventListener('DOMContentLoaded', function () {

    // Cards that get the camera focus-pull reveal (services, gallery, packages, products)
    const slideTargets = document.querySelectorAll(
        '.cards .card, .product-card, .gallery-item, .package, .package-card'
    );

    // Everything else keeps the gentle fade-up reveal
    const fadeTargets = document.querySelectorAll(
        '.section-head, .split > div, .split > a, .contact-details, .contact-box, ' +
        '.map-card, .form-card, .stats, .footer-grid'
    );

    const revealTargets = [];

    slideTargets.forEach(function (el, index) {
        el.classList.add('reveal-left');

        // Staggered entrance. Inside a carousel only the first visible row is
        // staggered, so cards that slide in later appear without a lag.
        let position = index % 6;
        const inCarousel = el.closest('[data-ws-carousel]');

        if (inCarousel && el.parentElement) {
            const sibling = Array.prototype.indexOf.call(el.parentElement.children, el);
            position = sibling < 3 ? sibling : 0;
        }

        // A CSS variable (not transition-delay) so hover never gets delayed.
        el.style.setProperty('--rd', position * 130 + 'ms');
        revealTargets.push(el);
    });

    // Cursor-follow golden spotlight on cards (mouse devices only)
    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        slideTargets.forEach(function (el) {
            el.addEventListener('pointermove', function (e) {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
        });
    }

    // After the entrance finishes, switch the card to its fast "hover" motion.
    function settle(el) {
        if (!el.classList.contains('reveal-left')) return;
        const delay = parseInt(el.style.getPropertyValue('--rd'), 10) || 0;
        setTimeout(function () {
            el.classList.add('reveal-done');
        }, 1150 + delay);
    }

    fadeTargets.forEach(function (el) {
        el.classList.add('reveal');
        revealTargets.push(el);
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    settle(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealTargets.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        revealTargets.forEach(function (el) {
            el.classList.add('is-visible');
            el.classList.add('reveal-done');
        });
    }
});



// Web Studio premium horizontal carousels
// Auto-play + smooth looping + touch swipe + arrows + dots.
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-ws-carousel]').forEach(function (carousel) {

        const viewport = carousel.querySelector('.ws-carousel-viewport');
        const track = carousel.querySelector('.ws-carousel-track');
        const prev = carousel.querySelector('.ws-prev');
        const next = carousel.querySelector('.ws-next');
        const dotsBox = carousel.querySelector('.ws-carousel-dots');

        if (!viewport || !track) return;

        let index = 0;
        let visible = 3;
        let maxIndex = 0;
        let startX = null;
        let timer = null;
        let resumeTimer = null;
        let isPaused = false;

        const AUTOPLAY_MS = 3600;
        const RESUME_DELAY = 1200;

        // Lets the active dot show an autoplay progress bar
        carousel.style.setProperty('--ws-autoplay', AUTOPLAY_MS + 'ms');

        function cards() {
            return Array.from(track.children).filter(function (el) {
                return !el.classList.contains('hidden');
            });
        }

        function gap() {
            const style = getComputedStyle(track);
            return parseFloat(style.columnGap || style.gap || 0) || 0;
        }

        function updateMetrics() {
            if (window.innerWidth <= 650) {
                visible = 1;
            } else if (window.innerWidth <= 900) {
                visible = 2;
            } else {
                visible = 3;
            }

            const list = cards();
            maxIndex = Math.max(0, list.length - visible);

            if (index > maxIndex) {
                index = maxIndex;
            }
        }

        function renderDots() {
            if (!dotsBox) return;

            dotsBox.innerHTML = '';
            const count = maxIndex + 1;

            if (count <= 1) {
                dotsBox.style.display = 'none';
                return;
            }

            dotsBox.style.display = '';

            for (let i = 0; i < count; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Show slide ' + (i + 1));
                dot.className = i === index ? 'is-active' : '';

                dot.addEventListener('click', function () {
                    index = i;
                    render();
                    pauseAndResume();
                });

                dotsBox.appendChild(dot);
            }
        }

        function render() {
            updateMetrics();

            const list = cards();

            if (!list.length) return;

            const card = list[0];
            const step = card.getBoundingClientRect().width + gap();

            track.style.transform =
                'translate3d(' + (-index * step) + 'px, 0, 0)';

            // Cards outside the window are softly dimmed while sliding
            list.forEach(function (c, i) {
                c.classList.toggle('is-out', i < index || i >= index + visible);
            });

            if (prev) {
                prev.disabled = false;
                prev.style.visibility = maxIndex > 0 ? 'visible' : 'hidden';
            }

            if (next) {
                next.disabled = false;
                next.style.visibility = maxIndex > 0 ? 'visible' : 'hidden';
            }

            if (dotsBox) {
                Array.from(dotsBox.children).forEach(function (dot, i) {
                    dot.classList.toggle('is-active', i === index);
                });
            }
        }

        function refresh() {
            updateMetrics();
            renderDots();
            render();
        }

        function goNext() {
            if (maxIndex <= 0) return;

            if (index >= maxIndex) {
                // Smoothly return to the first slide after reaching the end.
                index = 0;
            } else {
                index++;
            }

            render();
        }

        function goPrev() {
            if (maxIndex <= 0) return;

            if (index <= 0) {
                index = maxIndex;
            } else {
                index--;
            }

            render();
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            carousel.classList.remove('is-playing');
        }

        function startAutoplay() {
            stopAutoplay();

            if (isPaused || maxIndex <= 0) return;

            timer = setInterval(function () {
                goNext();
            }, AUTOPLAY_MS);

            // restart the dot progress in sync with the new timer
            carousel.classList.remove('is-playing');
            void carousel.offsetWidth;
            carousel.classList.add('is-playing');
        }

        function pauseAndResume() {
            isPaused = true;
            stopAutoplay();

            if (resumeTimer) {
                clearTimeout(resumeTimer);
            }

            resumeTimer = setTimeout(function () {
                isPaused = false;
                startAutoplay();
            }, RESUME_DELAY);
        }

        if (prev) {
            prev.addEventListener('click', function () {
                goPrev();
                pauseAndResume();
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                goNext();
                pauseAndResume();
            });
        }

        // Touch / swipe support.
        viewport.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            pauseAndResume();
        }, { passive: true });

        viewport.addEventListener('touchend', function (e) {
            if (startX === null) return;

            const diff = e.changedTouches[0].clientX - startX;
            startX = null;

            if (Math.abs(diff) > 40) {
                if (diff < 0) {
                    goNext();
                } else {
                    goPrev();
                }
            }

            pauseAndResume();
        }, { passive: true });

        // Pause while the visitor is reading a card.
        carousel.addEventListener('mouseenter', function () {
            isPaused = true;
            stopAutoplay();
        });

        carousel.addEventListener('mouseleave', function () {
            isPaused = false;
            startAutoplay();
        });

        carousel.addEventListener('focusin', function () {
            isPaused = true;
            stopAutoplay();
        });

        carousel.addEventListener('focusout', function () {
            isPaused = false;
            startAutoplay();
        });

        track.addEventListener('ws-carousel-refresh', function () {
            refresh();
            startAutoplay();
        });

        window.addEventListener('resize', function () {
            refresh();
            startAutoplay();
        });

        refresh();
        startAutoplay();
    });
});


// =========================================================
// Web Studio - site-wide premium motion (every page)
// scroll progress, header shadow, staggered reveals, count-up,
// calendar wave, button ripple, success confetti
// =========================================================
document.addEventListener('DOMContentLoaded', function () {

    const reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Scroll progress bar + header shadow ---------- */
    (function () {
        const bar = document.createElement('div');
        bar.className = 'wsx-progress';
        bar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(bar);

        const header = document.querySelector('.site-header');
        let ticking = false;

        function update() {
            const root = document.documentElement;
            const max = root.scrollHeight - root.clientHeight;
            const p = max > 0 ? root.scrollTop / max : 0;

            bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, p)) + ')';

            if (header) {
                header.classList.toggle('is-scrolled', root.scrollTop > 12);
            }

            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }, { passive: true });

        window.addEventListener('resize', update);
        update();
    })();

    /* ---------- Button ripple ---------- */
    document.addEventListener('pointerdown', function (e) {
        const btn = e.target.closest('.btn, .text-link, .ph-btn');

        if (!btn || getComputedStyle(btn).overflow !== 'hidden') return;

        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 2.2;
        const dot = document.createElement('span');

        dot.className = 'wsx-ripple';
        dot.style.width = dot.style.height = size + 'px';
        dot.style.left = (e.clientX - rect.left - size / 2) + 'px';
        dot.style.top = (e.clientY - rect.top - size / 2) + 'px';

        btn.appendChild(dot);
        setTimeout(function () { dot.remove(); }, 750);
    });

    if (reduceMotion) return;

    /* ---------- Package feature lines stagger ---------- */
    document.querySelectorAll('.package-features').forEach(function (list) {
        Array.prototype.forEach.call(list.children, function (li, i) {
            li.style.setProperty('--li', Math.min(i, 9));
        });
    });

    /* ---------- Staggered reveal groups ----------
       [selector, effect, stagger in ms] */
    const GROUPS = [
        ['.narrow > h2, .narrow > p', 'up', 130],
        ['.stats > div', 'pop', 150],
        ['.detail-box', 'left', 110],
        ['.contact-map', 'up', 0],
        ['.quick-buttons a', 'up', 100],
        ['.package-search-wrapper', 'up', 0],
        ['.no-package-result', 'up', 0],
        ['.cart-item', 'up', 110],
        ['.cart-summary', 'right', 0],
        ['.pay-summary-card', 'left', 0],
        ['.pay-form-card', 'right', 0],
        ['.pay-steps', 'up', 0],
        ['.bank-box', 'up', 0],
        ['.bank-row', 'left', 90],
        ['.checkout-note', 'up', 0],
        ['.booking-intro', 'up', 0],
        ['.booking-step', 'up', 140],
        ['.choice-card', 'pop', 70],
        ['.selected-summary', 'up', 0],
        ['.booking-card', 'up', 0],
        ['.my-booking-card', 'up', 110],
        ['.empty-bookings', 'pop', 0],
        ['.details-header', 'up', 0],
        ['.details-card', 'up', 110],
        ['.details-message', 'up', 0],
        ['.calendar-top', 'up', 0],
        ['.calendar-legend', 'up', 0],
        ['.result-card', 'pop', 0],
        ['.container > .card', 'pop', 0]
    ];

    const handled = new Set();
    const targets = [];
    const perParent = new Map();

    function skip(el) {
        return handled.has(el) ||
            el.classList.contains('reveal') ||
            el.classList.contains('reveal-left') ||
            el.closest('[data-ws-carousel]');
    }

    GROUPS.forEach(function (g) {
        document.querySelectorAll(g[0]).forEach(function (el) {
            if (skip(el)) return;

            const key = g[0];
            const bucket = perParent.get(el.parentElement) || {};
            const n = bucket[key] || 0;
            bucket[key] = n + 1;
            perParent.set(el.parentElement, bucket);

            el.classList.add('wsx', 'wsx-' + g[1]);
            el.style.setProperty('--rd', Math.min(n, 8) * g[2] + 'ms');

            handled.add(el);
            targets.push(el);
        });
    });

    /* Calendar: diagonal wave across the month */
    document.querySelectorAll('.calendar-day').forEach(function (day, i) {
        if (skip(day)) return;

        const wave = (i % 7) + Math.floor(i / 7);

        day.classList.add('wsx', 'wsx-pop');
        day.style.setProperty('--rd', wave * 45 + 'ms');

        handled.add(day);
        targets.push(day);
    });

    function finish(el) {
        const delay = parseInt(el.style.getPropertyValue('--rd'), 10) || 0;

        setTimeout(function () {
            el.classList.remove('wsx', 'wsx-up', 'wsx-pop', 'wsx-left', 'wsx-right', 'wsx-on');
            el.style.removeProperty('--rd');
        }, 1300 + delay);
    }

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('wsx-on');
                finish(entry.target);
                obs.unobserve(entry.target);
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

        targets.forEach(function (el) { io.observe(el); });
    } else {
        targets.forEach(function (el) {
            el.classList.remove('wsx', 'wsx-up', 'wsx-pop', 'wsx-left', 'wsx-right');
        });
    }

    /* ---------- Count-up numbers (100+, 5+ ...) ---------- */
    document.querySelectorAll('.stats b').forEach(function (b) {
        const m = b.textContent.trim().match(/^(\d[\d,]*)(\D*)$/);
        if (!m) return;

        const end = parseInt(m[1].replace(/,/g, ''), 10);
        const suffix = m[2];

        b.textContent = '0' + suffix;

        const io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                obs.unobserve(entry.target);

                const start = performance.now();
                const dur = 1600;

                (function tick(now) {
                    const t = Math.min(1, (now - start) / dur);
                    const eased = 1 - Math.pow(1 - t, 4);

                    b.textContent = Math.round(end * eased) + suffix;

                    if (t < 1) requestAnimationFrame(tick);
                })(start);
            });
        }, { threshold: 0.6 });

        io.observe(b);
    });

    /* ---------- Success confetti ---------- */
    const success = document.querySelector('.result-success');

    if (success) {
        const box = document.createElement('div');
        const colors = ['#d5a52a', '#f3d27a', '#0c2344', '#176d50', '#ffffff'];

        box.className = 'wsx-confetti';
        box.setAttribute('aria-hidden', 'true');

        for (let i = 0; i < 30; i++) {
            const piece = document.createElement('i');
            const angle = (Math.PI * 2 * i) / 30 + Math.random() * 0.4;
            const dist = 90 + Math.random() * 150;

            piece.style.background = colors[i % colors.length];
            piece.style.setProperty('--dx', Math.cos(angle) * dist + 'px');
            piece.style.setProperty('--dy', (Math.sin(angle) * dist + 60) + 'px');
            piece.style.setProperty('--r', (Math.random() * 720 - 360) + 'deg');
            piece.style.setProperty('--d', (0.5 + Math.random() * 0.25) + 's');

            box.appendChild(piece);
        }

        success.appendChild(box);
        setTimeout(function () { box.remove(); }, 2800);
    }
});