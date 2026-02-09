(function() {
    'use strict';

    const frontendData = window.lmcFrontendData || {};
    const ajaxUrl = frontendData.ajaxUrl || '';
    const ajaxNonce = frontendData.nonce || '';
    const blockCache = new Map();
    const restoreTimestamps = new Map();
    let anchorCounter = 0;
    let blockContainer = null;
    let blockIndexCounter = 0;

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

        ensureBlockAnchor(block);

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
                    showBlockError(block, 'Unable to load data for this competition.');
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.innerHTML = data.data.html.trim();
                const newBlock = wrapper.firstElementChild;
                if (!newBlock) {
                    block.classList.remove('lmc-block-loading');
                    showBlockError(block, 'Unable to load data for this competition.');
                    return;
                }

                if (newBlock.getAttribute('data-lmc-block-type') !== blockType) {
                    block.classList.remove('lmc-block-loading');
                    showBlockError(block, 'Unable to load data for this competition.');
                    return;
                }

                const updatedBlock = replaceBlockContent(block, newBlock);
                if (!updatedBlock) {
                    block.classList.remove('lmc-block-loading');
                    showBlockError(block, 'Unable to load data for this competition.');
                    return;
                }

                updatedBlock.classList.remove('lmc-block-loading');
                cacheBlockHtml(updatedBlock);

                if (updatedBlock.classList.contains('lmc-results-upcoming-block')) {
                    initBlockCarousels(updatedBlock);
                }
            })
            .catch(function() {
                block.classList.remove('lmc-block-loading');
                showBlockError(block, 'Unable to load data for this competition.');
            });
    }

    function applyBlockUpdate(block, newBlock) {
        if (!block || !newBlock) {
            return false;
        }

        block.className = newBlock.className;

        const newType = newBlock.getAttribute('data-lmc-block-type');
        if (newType) {
            block.setAttribute('data-lmc-block-type', newType);
        }

        const newAttrs = newBlock.getAttribute('data-lmc-block-attrs');
        if (newAttrs) {
            block.setAttribute('data-lmc-block-attrs', newAttrs);
        }

        block.innerHTML = newBlock.innerHTML;
        if (newBlock.hasAttribute('data-lmc-block-anchor-ref')) {
            block.setAttribute('data-lmc-block-anchor-ref', newBlock.getAttribute('data-lmc-block-anchor-ref'));
        }
        if (newBlock.hasAttribute('data-lmc-block-index')) {
            block.setAttribute('data-lmc-block-index', newBlock.getAttribute('data-lmc-block-index'));
        }
        return true;
    }

    function replaceBlockContent(block, newBlock) {
        if (!block || !newBlock) {
            return null;
        }

        if (block.isConnected) {
            return applyBlockUpdate(block, newBlock) ? block : null;
        }

        const anchorId = block.getAttribute('data-lmc-block-anchor-ref');
        if (anchorId) {
            const anchor = document.querySelector('[data-lmc-block-anchor="' + anchorId + '"]');
            if (anchor && anchor.parentNode) {
                newBlock.setAttribute('data-lmc-block-anchor-ref', anchorId);
                anchor.parentNode.insertBefore(newBlock, anchor.nextSibling);
                return newBlock;
            }
        }

        return null;
    }

    function ensureBlockAnchor(block) {
        if (!block || !block.isConnected) {
            return;
        }

        let anchorId = block.getAttribute('data-lmc-block-anchor-ref');
        if (!anchorId) {
            anchorId = 'lmc-block-anchor-' + (++anchorCounter);
            block.setAttribute('data-lmc-block-anchor-ref', anchorId);
        }

        const existing = document.querySelector('[data-lmc-block-anchor="' + anchorId + '"]');
        if (existing) {
            return;
        }

        const anchor = document.createElement('span');
        anchor.setAttribute('data-lmc-block-anchor', anchorId);
        anchor.style.display = 'none';
        block.parentNode.insertBefore(anchor, block);
    }

    function cacheBlockHtml(block) {
        if (!block) {
            return;
        }

        const anchorId = block.getAttribute('data-lmc-block-anchor-ref');
        if (!anchorId) {
            return;
        }

        const blockIndex = block.getAttribute('data-lmc-block-index') || '';
        blockCache.set(anchorId, {
            html: block.outerHTML,
            index: blockIndex
        });
    }

    function restoreMissingBlocks() {
        blockCache.forEach(function(entry, anchorId) {
            const anchor = document.querySelector('[data-lmc-block-anchor="' + anchorId + '"]');
            const existing = document.querySelector('[data-lmc-block-anchor-ref="' + anchorId + '"]');
            if (existing) {
                return;
            }

            const now = Date.now();
            const lastRestore = restoreTimestamps.get(anchorId) || 0;
            if (now - lastRestore < 2000) {
                return;
            }

            restoreTimestamps.set(anchorId, now);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = entry.html.trim();
            const restored = wrapper.firstElementChild;
            if (!restored) {
                return;
            }

            restored.setAttribute('data-lmc-block-anchor-ref', anchorId);
            if (entry.index) {
                restored.setAttribute('data-lmc-block-index', entry.index);
            }

            if (anchor && anchor.parentNode) {
                anchor.parentNode.insertBefore(restored, anchor.nextSibling);
                return;
            }

            insertRestoredBlock(restored, entry.index);
        });
    }

    function showBlockError(block, message) {
        if (!block) {
            return;
        }

        const existing = block.querySelector('.lmc-no-data.lmc-block-error');
        if (existing) {
            return;
        }

        const error = document.createElement('p');
        error.className = 'lmc-no-data lmc-block-error';
        error.textContent = message;
        block.appendChild(error);
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
        seedBlockCache();
        observeBlockRemoval();
        initCompetitionSelectors();
    }

    function seedBlockCache() {
        const blocks = document.querySelectorAll('[data-lmc-block-type][data-lmc-block-attrs]');
        if (!blocks.length) {
            return;
        }

        blockContainer = findCommonAncestor(blocks);

        blocks.forEach(function(block) {
            ensureBlockAnchor(block);
            assignBlockIndex(block);
            cacheBlockHtml(block);
        });
    }

    function assignBlockIndex(block) {
        if (!block) {
            return;
        }

        if (!block.hasAttribute('data-lmc-block-index')) {
            blockIndexCounter += 1;
            block.setAttribute('data-lmc-block-index', String(blockIndexCounter));
        }
    }

    function findCommonAncestor(nodes) {
        if (!nodes || !nodes.length) {
            return null;
        }

        let ancestor = nodes[0].parentElement;
        if (!ancestor) {
            return null;
        }

        while (ancestor && !allNodesContained(ancestor, nodes)) {
            ancestor = ancestor.parentElement;
        }

        return ancestor || nodes[0].parentElement;
    }

    function allNodesContained(container, nodes) {
        if (!container) {
            return false;
        }

        for (let i = 0; i < nodes.length; i += 1) {
            if (!container.contains(nodes[i])) {
                return false;
            }
        }

        return true;
    }

    function insertRestoredBlock(block, index) {
        const container = blockContainer || document.body;
        if (!container) {
            return;
        }

        if (!index) {
            container.appendChild(block);
            return;
        }

        const existingBlocks = Array.from(container.querySelectorAll('[data-lmc-block-index]'));
        const targetIndex = parseInt(index, 10);
        if (Number.isNaN(targetIndex)) {
            container.appendChild(block);
            return;
        }

        for (let i = 0; i < existingBlocks.length; i += 1) {
            const existingIndex = parseInt(existingBlocks[i].getAttribute('data-lmc-block-index') || '', 10);
            if (!Number.isNaN(existingIndex) && existingIndex > targetIndex) {
                container.insertBefore(block, existingBlocks[i]);
                return;
            }
        }

        container.appendChild(block);
    }

    function observeBlockRemoval() {
        if (typeof MutationObserver === 'undefined') {
            return;
        }

        const observer = new MutationObserver(function() {
            restoreMissingBlocks();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFrontEnd);
    } else {
        initFrontEnd();
    }
})();
