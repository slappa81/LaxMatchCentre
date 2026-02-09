(function() {
    'use strict';

    const frontendData = window.lmcFrontendData || {};
    const ajaxUrl = frontendData.ajaxUrl || '';
    const ajaxNonce = frontendData.nonce || '';

    function scrollCarouselToEnd(carousel) {
        requestAnimationFrame(function() {
            carousel.scrollLeft = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
        });
    }

    function initCarouselSection(section) {
        const carousel = section.querySelector('.lmc-carousel');
        if (!carousel) {
            return;
        }

        const controls = section.querySelectorAll('.lmc-carousel-btn');
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

        scrollCarouselToEnd(carousel);
    }

    function initBlockCarousels(block) {
        const sections = block.querySelectorAll('.lmc-carousel-section');
        if (sections.length) {
            sections.forEach(initCarouselSection);
            return;
        }

        initCarouselSection(block);
    }

    function initAllCarousels() {
        const blocks = document.querySelectorAll('.lmc-results-upcoming-block');
        if (!blocks.length) {
            return;
        }

        blocks.forEach(initBlockCarousels);
    }

    function getBlockAttributes(block) {
        const rawAttributes = block.getAttribute('data-lmc-block-attrs');
        if (!rawAttributes) {
            return {};
        }

        try {
            return JSON.parse(rawAttributes);
        } catch (error) {
            return {};
        }
    }

    function updateBlock(block, compId) {
        if (!ajaxUrl || !ajaxNonce) {
            return;
        }

        const blockType = block.getAttribute('data-lmc-block-type');
        if (!blockType) {
            return;
        }

        const attributes = getBlockAttributes(block);
        if (blockType === 'team-results' || blockType === 'team-upcoming') {
            if (attributes.allowCompSync === false) {
                return;
            }
        }
        const params = new URLSearchParams();
        params.append('action', 'lmc_render_block');
        params.append('nonce', ajaxNonce);
        params.append('blockType', blockType);
        params.append('compId', compId || '');
        params.append('attributes', JSON.stringify(attributes));

        if (!block.querySelector('.lmc-loading-placeholder')) {
            const placeholder = document.createElement('div');
            placeholder.className = 'lmc-loading-placeholder';
            const spinner = document.createElement('span');
            spinner.className = 'lmc-loading-spinner';
            placeholder.appendChild(spinner);
            placeholder.appendChild(document.createTextNode('Loading...'));
            block.prepend(placeholder);
        }

        block.classList.add('lmc-block-loading');

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: params.toString()
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || !data.success || !data.data || !data.data.html) {
                    block.classList.remove('lmc-block-loading');
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.innerHTML = data.data.html.trim();
                const newBlock = wrapper.firstElementChild;
                if (!newBlock) {
                    block.classList.remove('lmc-block-loading');
                    return;
                }

                block.replaceWith(newBlock);

                if (newBlock.classList.contains('lmc-results-upcoming-block')) {
                    initBlockCarousels(newBlock);
                }
            })
            .catch(function() {
                block.classList.remove('lmc-block-loading');
            });
    }

    function updateBlocksForCompetition(compId) {
        const blocks = document.querySelectorAll('[data-lmc-block-type][data-lmc-block-attrs]');
        if (!blocks.length) {
            return;
        }

        blocks.forEach(function(block) {
            updateBlock(block, compId);
        });
    }

    function initCompetitionSelectors() {
        const selectors = document.querySelectorAll('[data-lmc-competition-select]');
        if (!selectors.length) {
            return;
        }

        selectors.forEach(function(select) {
            select.addEventListener('change', function(event) {
                updateBlocksForCompetition(event.target.value);
            });
        });
    }

    function initFrontEnd() {
        initAllCarousels();
        initCompetitionSelectors();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFrontEnd);
    } else {
        initFrontEnd();
    }
})();
