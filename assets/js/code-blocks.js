/**
 * WP Djot - Code Block Enhancements
 *
 * Handles line numbers and line highlighting for code blocks.
 * Line numbers are rendered outside the code box, right-aligned.
 *
 * @package WpDjot
 */

(function () {
    'use strict';

    /**
     * Add line numbers gutter to code blocks.
     *
     * Creates a separate gutter element positioned to the left of the code block.
     * Line numbers are right-aligned within the gutter.
     */
    function addLineNumbers() {
        var preBlocks = document.querySelectorAll('.djot-content pre.line-numbers');

        preBlocks.forEach(function (pre) {
            // Skip if already processed
            if (pre.querySelector('.line-numbers-gutter')) {
                return;
            }

            var code = pre.querySelector('code');
            if (!code) {
                return;
            }

            // Get starting line number
            var start = 1;
            if (pre.hasAttribute('data-start')) {
                start = parseInt(pre.getAttribute('data-start'), 10) || 1;
            }

            // Count lines in code
            var codeText = code.textContent || '';
            var lines = codeText.split('\n');
            // Remove last empty line if code ends with newline
            if (lines.length > 1 && lines[lines.length - 1] === '') {
                lines.pop();
            }
            var lineCount = lines.length;

            // Create gutter element
            var gutter = document.createElement('div');
            gutter.className = 'line-numbers-gutter';
            gutter.setAttribute('aria-hidden', 'true');

            // Add line numbers
            for (var i = 0; i < lineCount; i++) {
                var lineNum = document.createElement('span');
                lineNum.className = 'line-num';
                lineNum.textContent = String(start + i);
                gutter.appendChild(lineNum);
            }

            // Wrap pre content for proper layout
            pre.classList.add('has-line-gutter');
            pre.insertBefore(gutter, pre.firstChild);
        });
    }

    /**
     * Add highlighting to specific lines.
     */
    function addLineHighlighting() {
        var preBlocks = document.querySelectorAll('.djot-content pre.has-highlighted-lines');

        preBlocks.forEach(function (pre) {
            // Skip if already processed
            if (pre.hasAttribute('data-highlight-processed')) {
                return;
            }
            pre.setAttribute('data-highlight-processed', 'true');

            var code = pre.querySelector('code');
            if (!code) {
                return;
            }

            // Get highlight lines
            var highlightAttr = pre.getAttribute('data-highlight');
            if (!highlightAttr) {
                return;
            }

            var highlightLines = highlightAttr.split(',').map(function (n) {
                return parseInt(n, 10);
            });

            // Get starting line number for offset
            var start = 1;
            if (pre.hasAttribute('data-start')) {
                start = parseInt(pre.getAttribute('data-start'), 10) || 1;
            }

            // Get code content and split into lines
            var codeHtml = code.innerHTML;
            var lines = codeHtml.split('\n');

            // Remove last empty line if code ends with newline
            var hadTrailingNewline = false;
            if (lines.length > 1 && lines[lines.length - 1] === '') {
                lines.pop();
                hadTrailingNewline = true;
            }

            // Wrap each line in a span, highlighting as needed
            var wrappedLines = lines.map(function (line, index) {
                var lineNumber = start + index;
                var isHighlighted = highlightLines.indexOf(lineNumber) !== -1;
                var className = 'line' + (isHighlighted ? ' highlighted' : '');
                return '<span class="' + className + '">' + line + '</span>';
            });

            code.innerHTML = wrappedLines.join('\n') + (hadTrailingNewline ? '\n' : '');
        });
    }

    /**
     * Initialize code block enhancements.
     */
    function init() {
        addLineHighlighting();
        addLineNumbers();

        // Run highlight.js if available (after our processing)
        if (typeof hljs !== 'undefined') {
            document.querySelectorAll('.djot-content pre code').forEach(function (code) {
                // For blocks with line wrapping, highlight each line
                var lines = code.querySelectorAll('.line');
                if (lines.length > 0) {
                    var language = null;
                    var classList = code.className.split(' ');
                    for (var i = 0; i < classList.length; i++) {
                        if (classList[i].startsWith('language-')) {
                            language = classList[i].replace('language-', '');
                            break;
                        }
                    }

                    if (language) {
                        lines.forEach(function (lineEl) {
                            var text = lineEl.textContent;
                            try {
                                var result = hljs.highlight(text, { language: language, ignoreIllegals: true });
                                lineEl.innerHTML = result.value;
                            } catch (e) {
                                // Keep original content on failure
                            }
                        });
                        code.classList.add('hljs');
                    }
                } else if (!code.classList.contains('hljs')) {
                    // Regular code block without line structure
                    hljs.highlightElement(code);
                }
            });
        }
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for manual triggering
    window.wpDjotCodeBlocks = { init: init };
})();
