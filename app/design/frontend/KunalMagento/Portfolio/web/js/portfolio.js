document.addEventListener('DOMContentLoaded', function () {
    // Smooth scroll for anchor nav
    var navLinks = document.querySelectorAll('.kp-anchor-nav a');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var targetId = link.getAttribute('href');
            if (!targetId || targetId.charAt(0) !== '#') {
                return;
            }
            var target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        });
    });

    // Sections + animate elements
    var animateEls = document.querySelectorAll('.kp-animate');
    var sections = document.querySelectorAll('header#intro, .kp-section');

    var navMap = {};
    navLinks.forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && href.charAt(0) === '#') {
            navMap[href.substring(1)] = link;
        }
    });

    // Years counter
    var heroAnimated = false;
    var yearsEl = document.querySelector('.kp-years-count');
    var yearsTarget = yearsEl ? parseInt(yearsEl.getAttribute('data-target') || '0', 10) : 0;

    function startYearsCounter() {
        if (!yearsEl || !yearsTarget || heroAnimated) return;
        heroAnimated = true;

        var current = 0;
        var duration = 800; // ms
        var start = performance.now();

        function tick(now) {
            var progress = Math.min((now - start) / duration, 1);
            current = Math.floor(progress * yearsTarget);
            yearsEl.textContent = current.toString();

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                yearsEl.textContent = yearsTarget.toString();
            }
        }

        requestAnimationFrame(tick);
    }

    // Start counter once on load
    startYearsCounter();

    // Fade-in + active nav
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var el = entry.target;

                if (entry.isIntersecting) {
                    el.classList.add('is-visible');
                }

                var id = el.getAttribute('id');
                if (id && navMap[id] && entry.isIntersecting) {
                    navLinks.forEach(function (l) {
                        l.classList.remove('is-active');
                    });
                    navMap[id].classList.add('is-active');
                }
            });
        }, {
            threshold: 0.2
        });

        animateEls.forEach(function (el) {
            io.observe(el);
        });
    } else {
        // Fallback: just show all sections
        animateEls.forEach(function (el) {
            el.classList.add('is-visible');
        });
    }
});
