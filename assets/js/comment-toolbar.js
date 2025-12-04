/**
 * Djot Comment Toolbar
 *
 * Adds a simple formatting toolbar above the comment textarea.
 */
(function() {
    'use strict';

    const buttons = [
        { label: 'B', title: 'Bold', before: '*', after: '*' },
        { label: 'I', title: 'Italic', before: '_', after: '_' },
        { label: 'Code', title: 'Inline Code', before: '`', after: '`' },
        { label: 'Link', title: 'Link', before: '[', after: '](url)', prompt: true },
        { label: 'Quote', title: 'Blockquote', before: '> ', after: '', line: true },
        { label: '```', title: 'Code Block', before: '```\n', after: '\n```', block: true },
    ];

    function createToolbar(textarea) {
        const toolbar = document.createElement('div');
        toolbar.className = 'djot-comment-toolbar';

        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'djot-toolbar-btn';
            button.textContent = btn.label;
            button.title = btn.title;
            button.addEventListener('click', (e) => {
                e.preventDefault();
                insertFormatting(textarea, btn);
            });
            toolbar.appendChild(button);
        });

        // Add help link
        const help = document.createElement('a');
        help.href = 'https://djot.net/';
        help.target = '_blank';
        help.rel = 'noopener';
        help.className = 'djot-toolbar-help';
        help.textContent = '?';
        help.title = 'Djot Syntax Help';
        toolbar.appendChild(help);

        return toolbar;
    }

    function insertFormatting(textarea, btn) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selected = text.substring(start, end);

        let before = btn.before;
        let after = btn.after;
        let newText;
        let cursorPos;

        if (btn.prompt && btn.label === 'Link') {
            const url = prompt('Enter URL:', 'https://');
            if (!url) return;
            after = '](' + url + ')';
        }

        if (btn.line) {
            // Line-based formatting (quote) - apply to each line
            // Add newline before if not at start of line
            const needsNewlineBefore = start > 0 && text[start - 1] !== '\n';
            const prefix = needsNewlineBefore ? '\n' : '';
            const lines = selected.split('\n');
            const formatted = lines.map(line => before + line).join('\n');
            newText = text.substring(0, start) + prefix + formatted + text.substring(end);
            cursorPos = start + prefix.length + formatted.length;
        } else if (btn.block) {
            // Block formatting - add newlines if needed
            const needsNewlineBefore = start > 0 && text[start - 1] !== '\n';
            const needsNewlineAfter = end < text.length && text[end] !== '\n';
            before = (needsNewlineBefore ? '\n' : '') + before;
            after = after + (needsNewlineAfter ? '\n' : '');
            newText = text.substring(0, start) + before + selected + after + text.substring(end);
            cursorPos = start + before.length + selected.length;
        } else {
            // Inline formatting
            newText = text.substring(0, start) + before + selected + after + text.substring(end);
            cursorPos = selected ? start + before.length + selected.length + after.length : start + before.length;
        }

        textarea.value = newText;
        textarea.focus();

        // Position cursor
        if (selected) {
            textarea.setSelectionRange(cursorPos, cursorPos);
        } else {
            // No selection - place cursor between markers
            const middlePos = start + before.length;
            textarea.setSelectionRange(middlePos, middlePos);
        }

        // Trigger input event for any listeners
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function init() {
        // Find comment textarea
        const textarea = document.getElementById('comment');
        if (!textarea) return;

        // Check if toolbar already exists
        if (textarea.previousElementSibling?.classList.contains('djot-comment-toolbar')) {
            return;
        }

        // Create and insert toolbar
        const toolbar = createToolbar(textarea);
        textarea.parentNode.insertBefore(toolbar, textarea);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
