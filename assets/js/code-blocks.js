/**
 * WP Djot - Code Block Enhancements
 *
 * Handles line numbers and line highlighting for code blocks.
 *
 * @package WpDjot
 */

(function () {
    'use strict';

    /**
     * Initialize code block line number offsets.
     *
     * Sets the --line-start CSS custom property based on data-start attribute.
     */
    function initLineNumberOffsets() {
        var preBlocks = document.querySelectorAll('.djot-content pre.line-numbers[data-start]');

        preBlocks.forEach(function (pre) {
            var start = parseInt(pre.getAttribute('data-start'), 10);
            if (!isNaN(start) && start > 0) {
                // CSS counter-reset starts at value before first increment
                // So for line 5, we set counter-reset to 4
                pre.style.setProperty('--line-start', start - 1);
            }
        });
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLineNumberOffsets);
    } else {
        initLineNumberOffsets();
    }
})();
