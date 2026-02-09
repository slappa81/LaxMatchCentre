(function() {
    'use strict';

    function initCarousel(block) {
        const carousel = block.querySelector('.lmc-carousel');
        if (!carousel) {
            return;
        }

        const controls = block.querySelectorAll('.lmc-carousel-btn');
        if (!controls.length) {
            return;
        }

        controls.forEach(function(button) {
            button.addEventListener('click', function() {
                const direction = button.getAttribute('data-direction');
                const offset = carousel.clientWidth;
                const delta = direction === 'prev' ? -offset : offset;
                carousel.scrollBy({ left: delta, behavior: 'smooth' });
            });
        });
    }

    function initAllCarousels() {
        const blocks = document.querySelectorAll('.lmc-results-upcoming-block');
        if (!blocks.length) {
            return;
        }

        blocks.forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllCarousels);
    } else {
        initAllCarousels();
    }
})();
