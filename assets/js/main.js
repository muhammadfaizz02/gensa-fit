// assets/js/main.js

/* ============================================================
   Helper global — dipakai oleh semua halaman panel
   ============================================================ */
function showModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function hideModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {

    /* ============================================================
       1. Fade-up animation (landing page)
       ============================================================ */
    const fadeEls = document.querySelectorAll('.fade-up');
    if (fadeEls.length) {
        // Fallback: kalau browser tidak support IntersectionObserver,
        // langsung tampilkan semua.
        if (!('IntersectionObserver' in window)) {
            fadeEls.forEach(el => el.classList.add('show'));
        } else {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('show');
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12 });
            fadeEls.forEach(el => obs.observe(el));
        }
    }

    /* ============================================================
       2. Navbar scroll effect (landing page)
       ============================================================ */
    const navbar = document.getElementById('navbar');
    if (navbar) {
        const onScroll = () => {
            if (window.scrollY > 50) {
                navbar.classList.add('bg-slate-950/95', 'backdrop-blur-lg', 'shadow-lg');
            } else {
                navbar.classList.remove('bg-slate-950/95', 'backdrop-blur-lg', 'shadow-lg');
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* ============================================================
       3. Close modal dengan ESC
       ============================================================ */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal"]').forEach(m => {
                m.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }
    });

    /* ============================================================
       4. Auto-hide flash message ([data-flash])
       ============================================================ */
    document.querySelectorAll('[data-flash]').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });

    /* ============================================================
       5. Confirm untuk link/button hapus (data-confirm)
       ============================================================ */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* ============================================================
       6. Counter animation untuk statistik landing (data-count)
       ============================================================ */
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.getAttribute('data-count'), 10) || 0;
        const duration = 1200;
        const step = target / (duration / 16);
        let cur = 0;

        const tick = () => {
            cur += step;
            if (cur >= target) {
                el.textContent = target.toLocaleString('id-ID') + '+';
            } else {
                el.textContent = Math.floor(cur).toLocaleString('id-ID');
                requestAnimationFrame(tick);
            }
        };

        // Mulai animasi hanya kalau elemen sudah masuk viewport
        if ('IntersectionObserver' in window) {
            const obs2 = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        tick();
                        obs2.unobserve(e.target);
                    }
                });
            }, { threshold: 0.5 });
            obs2.observe(el);
        } else {
            tick();
        }
    });

    /* ============================================================
       7. Auto-close modal kalau klik area backdrop
       ============================================================ */
    document.querySelectorAll('[id^="modal"]').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
    });

});