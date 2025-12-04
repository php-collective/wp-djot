/**
 * Djot Comment Toolbar
 *
 * Adds a formatting toolbar with Write/Preview tabs above the comment textarea.
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

    let previewPane = null;
    let currentTab = 'write';

    function createToolbar(textarea) {
        const container = document.createElement('div');
        container.className = 'djot-comment-container';

        // Tab bar
        const tabBar = document.createElement('div');
        tabBar.className = 'djot-comment-tabs';

        const writeTab = document.createElement('button');
        writeTab.type = 'button';
        writeTab.className = 'djot-tab active';
        writeTab.textContent = 'Write';
        writeTab.addEventListener('click', () => switchTab('write', textarea));

        const previewTab = document.createElement('button');
        previewTab.type = 'button';
        previewTab.className = 'djot-tab';
        previewTab.textContent = 'Preview';
        previewTab.addEventListener('click', () => switchTab('preview', textarea));

        tabBar.appendChild(writeTab);
        tabBar.appendChild(previewTab);

        // Toolbar (formatting buttons)
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

        // Preview pane
        previewPane = document.createElement('div');
        previewPane.className = 'djot-preview-pane djot-content';
        previewPane.style.display = 'none';

        container.appendChild(tabBar);
        container.appendChild(toolbar);

        return container;
    }

    function switchTab(tab, textarea) {
        currentTab = tab;

        // Update tab buttons
        document.querySelectorAll('.djot-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.djot-tab:${tab === 'write' ? 'first-child' : 'last-child'}`).classList.add('active');

        // Update toolbar visibility
        const toolbar = document.querySelector('.djot-comment-toolbar');

        if (tab === 'write') {
            textarea.style.display = '';
            previewPane.style.display = 'none';
            toolbar.style.display = '';
        } else {
            textarea.style.display = 'none';
            previewPane.style.display = 'block';
            toolbar.style.display = 'none';
            renderPreview(textarea.value);
        }
    }

    function renderPreview(content) {
        if (!content.trim()) {
            previewPane.innerHTML = '<p class="djot-preview-empty">Nothing to preview</p>';
            return;
        }

        // Call the REST API to render Djot (uses comment profile for safety)
        previewPane.innerHTML = '<p class="djot-preview-loading">Loading preview...</p>';

        fetch(wpDjotSettings.restUrl + 'wp-djot/v1/preview-comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpDjotSettings.nonce,
            },
            body: JSON.stringify({ content: content }),
        })
        .then(response => response.json())
        .then(data => {
            previewPane.innerHTML = data.html || '<p class="djot-preview-empty">Nothing to preview</p>';
        })
        .catch(() => {
            previewPane.innerHTML = '<p class="djot-preview-error">Preview unavailable</p>';
        });
    }

    function insertFormatting(textarea, btn) {
        // Switch to write tab if in preview
        if (currentTab === 'preview') {
            switchTab('write', textarea);
        }

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
        // Check if REST API settings are available
        if (typeof wpDjotSettings === 'undefined') {
            console.warn('WP Djot: REST API settings not available, preview disabled');
        }

        // Find comment textarea
        const textarea = document.getElementById('comment');
        if (!textarea) return;

        // Check if toolbar already exists
        if (textarea.previousElementSibling?.classList.contains('djot-comment-container')) {
            return;
        }

        // Create and insert toolbar container
        const container = createToolbar(textarea);
        textarea.parentNode.insertBefore(container, textarea);

        // Insert preview pane after textarea
        textarea.parentNode.insertBefore(previewPane, textarea.nextSibling);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
