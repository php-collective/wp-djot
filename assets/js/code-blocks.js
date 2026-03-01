/**
 * WP Djot - Code Block Enhancements
 *
 * VitePress-style code block features:
 * - Diff highlighting (++/--)
 * - Focus mode
 * - Error/Warning highlights
 * - Copy button
 *
 * Note: Line numbers are handled via CSS using data-line attributes from djot-php.
 * Line highlighting is also handled server-side by djot-php.
 *
 * @package WpDjot
 */

(function () {
    'use strict';

    /**
     * Process inline markers in code content.
     * Returns processed lines with marker classes.
     * Handles both raw and HTML-encoded brackets.
     */
    function processInlineMarkers(code) {
        var codeHtml = code.innerHTML;

        // Normalize HTML entities for brackets
        codeHtml = codeHtml.replace(/&#091;/g, '[').replace(/&#093;/g, ']');
        codeHtml = codeHtml.replace(/&#x5B;/g, '[').replace(/&#x5D;/g, ']');

        var lines = codeHtml.split('\n');
        var hasFocus = false;
        var hasMarkers = false;

        // First pass: check if any focus markers exist
        lines.forEach(function (line) {
            if (line.indexOf('[!code focus]') !== -1) {
                hasFocus = true;
            }
            if (line.indexOf('[!code') !== -1) {
                hasMarkers = true;
            }
        });

        if (!hasMarkers) {
            return null;
        }

        // Remove last empty line if code ends with newline
        var hadTrailingNewline = false;
        if (lines.length > 1 && lines[lines.length - 1] === '') {
            lines.pop();
            hadTrailingNewline = true;
        }

        // Process each line
        var wrappedLines = lines.map(function (line, index) {
            var classes = ['line'];
            var cleanLine = line;

            // Check for markers and add appropriate classes
            // Support both // and # comment styles
            var markerPattern = /(?:\/\/|#)\s*\[!code\s+(\+\+|--|focus|error|warning|highlight)\]/;
            var match = line.match(markerPattern);

            if (match) {
                var marker = match[1];
                cleanLine = line.replace(markerPattern, '');

                if (marker === '++') {
                    classes.push('diff', 'add');
                } else if (marker === '--') {
                    classes.push('diff', 'remove');
                } else if (marker === 'focus') {
                    classes.push('focus');
                } else if (marker === 'error') {
                    classes.push('error');
                } else if (marker === 'warning') {
                    classes.push('warning');
                } else if (marker === 'highlight') {
                    classes.push('highlighted');
                }
            }

            // If focus mode and this line is not focused, dim it
            if (hasFocus && classes.indexOf('focus') === -1) {
                classes.push('dimmed');
            }

            // Add data-line for CSS line numbers (1-indexed)
            return '<span class="' + classes.join(' ') + '" data-line="' + (index + 1) + '">' + cleanLine + '</span>';
        });

        return {
            html: wrappedLines.join('\n') + (hadTrailingNewline ? '\n' : ''),
            hasFocus: hasFocus
        };
    }

    /**
     * Process inline code markers (diff, focus, error, warning).
     */
    function processCodeMarkers() {
        var preBlocks = document.querySelectorAll('.djot-content pre');

        preBlocks.forEach(function (pre) {
            if (pre.hasAttribute('data-markers-processed')) {
                return;
            }

            var code = pre.querySelector('code');
            if (!code) {
                return;
            }

            // Check if already has line structure from djot-php
            if (code.querySelector('.line')) {
                return;
            }

            var result = processInlineMarkers(code);
            if (result) {
                pre.setAttribute('data-markers-processed', 'true');
                code.innerHTML = result.html;
                if (result.hasFocus) {
                    pre.classList.add('has-focus');
                }
            }
        });
    }

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
     * Apply syntax highlighting.
     */
    function applySyntaxHighlighting() {
        if (typeof hljs === 'undefined') {
            return;
        }

        document.querySelectorAll('.djot-content pre code').forEach(function (code) {
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
                hljs.highlightElement(code);
            }
        });
    }

    /**
     * Initialize code block enhancements.
     */
    function init() {
        processCodeMarkers();
        addCopyButtons();
        applySyntaxHighlighting();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.wpDjotCodeBlocks = { init: init };
})();
