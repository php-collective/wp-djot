/**
 * WP Djot - Code Block Enhancements
 *
 * Minimal client-side enhancements for code blocks:
 * - Copy button
 *
 * Note: Syntax highlighting, line numbers, and annotations are handled
 * server-side by Torchlight Engine (Phiki).
 *
 * @package WpDjot
 */

(function () {
    'use strict';

    /**
     * Add copy button to code blocks.
     */
    function addCopyButtons() {
        var preBlocks = document.querySelectorAll('.djot-content pre');

        preBlocks.forEach(function (pre) {
            if (pre.querySelector('.code-copy-btn')) {
                return;
            }

            var code = pre.querySelector('code');
            if (!code) {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrapper';
            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            var btn = document.createElement('button');
            btn.className = 'code-copy-btn';
            btn.setAttribute('aria-label', 'Copy code');
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';

            btn.addEventListener('click', function () {
                var text = code.textContent || '';
                navigator.clipboard.writeText(text).then(function () {
                    btn.classList.add('copied');
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    setTimeout(function () {
                        btn.classList.remove('copied');
                        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
                    }, 2000);
                });
            });

            wrapper.appendChild(btn);
        });
    }

    /**
     * Initialize code block enhancements.
     */
    function init() {
        addCopyButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.wpDjotCodeBlocks = { init: init };
})();
