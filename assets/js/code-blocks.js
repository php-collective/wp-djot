/**
 * WP Djot - Code Block Enhancements
 *
 * Handles line numbers and line highlighting for code blocks.
 * Must run BEFORE highlight.js to set up proper highlighting.
 *
 * @package WpDjot
 */

(function () {
    'use strict';

    /**
     * Add line number elements to code blocks.
     *
     * Injects line number spans into each .line element for blocks with .line-numbers class.
     */
    function addLineNumbers() {
        var preBlocks = document.querySelectorAll('.djot-content pre.line-numbers');

        preBlocks.forEach(function (pre) {
            var start = 1;
            if (pre.hasAttribute('data-start')) {
                start = parseInt(pre.getAttribute('data-start'), 10) || 1;
            }

            var lines = pre.querySelectorAll('code .line');
            lines.forEach(function (line, index) {
                // Skip if already has line number
                if (line.querySelector('.line-num')) {
                    return;
                }
                var numSpan = document.createElement('span');
                numSpan.className = 'line-num';
                numSpan.textContent = start + index;
                line.insertBefore(numSpan, line.firstChild);
            });
        });
    }

    /**
     * Highlight code blocks with line structure.
     *
     * For blocks with .line spans, we need to highlight each line individually
     * to preserve the line structure. Regular blocks use hljs.highlightElement().
     */
    function highlightWithLineStructure() {
        if (typeof hljs === 'undefined') {
            return;
        }

        // Find all code blocks with line structure
        var lineBlocks = document.querySelectorAll('.djot-content pre code .line');
        var processedPres = new Set();

        lineBlocks.forEach(function (line) {
            var pre = line.closest('pre');
            if (processedPres.has(pre)) {
                return;
            }
            processedPres.add(pre);

            var code = pre.querySelector('code');
            var language = null;

            // Extract language from class
            var classList = code.className.split(' ');
            for (var i = 0; i < classList.length; i++) {
                if (classList[i].startsWith('language-')) {
                    language = classList[i].replace('language-', '');
                    break;
                }
            }

            if (!language) {
                return;
            }

            // Highlight each line individually
            var lines = code.querySelectorAll('.line');
            lines.forEach(function (lineEl) {
                var text = lineEl.textContent;
                try {
                    var result = hljs.highlight(text, { language: language, ignoreIllegals: true });
                    lineEl.innerHTML = result.value;
                } catch (e) {
                    // If highlighting fails, keep original content
                }
            });

            // Mark as highlighted
            code.classList.add('hljs');
        });

        // For regular code blocks (without line structure), use highlightElement
        document.querySelectorAll('.djot-content pre code').forEach(function (code) {
            var pre = code.closest('pre');
            if (processedPres.has(pre)) {
                return;
            }
            if (!code.classList.contains('hljs')) {
                hljs.highlightElement(code);
            }
        });
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            highlightWithLineStructure();
            // Add line numbers AFTER highlighting so they survive innerHTML replacement
            addLineNumbers();
        });
    } else {
        highlightWithLineStructure();
        addLineNumbers();
    }

    // Expose for manual triggering if needed
    window.wpDjotCodeBlocks = {
        init: function () {
            highlightWithLineStructure();
            addLineNumbers();
        }
    };
})();
