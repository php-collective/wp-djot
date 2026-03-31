/**
 * Djot Serializer for Tiptap/ProseMirror
 *
 * Converts a Tiptap/ProseMirror JSON document to Djot markup.
 *
 * @example
 * ```js
 * import { serializeToDjot } from 'djot-grammars/tiptap'
 *
 * const editor = new Editor({ ... })
 *
 * // Get Djot output
 * const djotText = serializeToDjot(editor.getJSON())
 * ```
 */

/**
 * Serialize a Tiptap/ProseMirror JSON document to Djot markup
 *
 * @param {Object} doc - The document JSON from editor.getJSON()
 * @returns {string} Djot markup
 */
export function serializeToDjot(doc) {
    let output = '';

    function serializeNode(node, depth = 0) {
        if (!node) return;

        switch (node.type) {
            case 'doc':
                (node.content || []).forEach((child, i) => {
                    serializeNode(child, depth);
                    // Add blank line between all blocks to keep them separate
                    if (i < (node.content || []).length - 1) {
                        output += '\n';
                    }
                });
                break;

            case 'paragraph':
                output += serializeInline(node.content) + '\n';
                break;

            case 'heading':
                output += '#'.repeat(node.attrs?.level || 1) + ' ' + serializeInline(node.content) + '\n';
                break;

            case 'bulletList':
            case 'orderedList':
            case 'taskList':
                // Check if list is "loose" - only from the parsed attribute
                // Having nested lists does NOT make a list loose in Djot
                const isLoose = node.attrs?.loose || false;
                let num = node.attrs?.start || 1;
                (node.content || []).forEach((item, i) => {
                    const indent = '  '.repeat(depth);
                    if (node.type === 'bulletList') {
                        output += indent + '- ';
                    } else if (node.type === 'orderedList') {
                        output += indent + num + '. ';
                        num++;
                    } else if (node.type === 'taskList') {
                        const checked = item.attrs?.checked ? 'x' : ' ';
                        output += indent + '- [' + checked + '] ';
                    }
                    serializeListItem(item, depth, isLoose);
                    // Add blank line between items in loose lists
                    if (isLoose && i < (node.content || []).length - 1) {
                        output += '\n';
                    }
                });
                break;

            case 'blockquote':
                // Serialize each child block with proper blank line separation
                (node.content || []).forEach((child, i) => {
                    const childText = serializeNodeToString(child);
                    // Prefix each line with >
                    childText.split('\n').forEach(line => {
                        output += '> ' + line + '\n';
                    });
                    // Add blank line between blocks (> followed by empty line)
                    if (i < (node.content || []).length - 1) {
                        output += '>\n';
                    }
                });
                break;

            case 'codeBlock':
                // If we have the original Djot source, use it (for round-trip support)
                if (node.attrs?.djotSrc) {
                    output += node.attrs.djotSrc;
                    // Ensure it ends with newline
                    if (!node.attrs.djotSrc.endsWith('\n')) {
                        output += '\n';
                    }
                } else {
                    // Use languageRaw (with Torchlight options) if available, otherwise language
                    const lang = node.attrs?.languageRaw || node.attrs?.language || '';
                    const codeContent = (node.content || []).map(c => c.text || '').join('');
                    // Find a safe fence that doesn't conflict with the content
                    const fence = findSafeCodeFence(codeContent);
                    // Djot uses space between ``` and language
                    output += fence + (lang ? ' ' + lang : '') + '\n';
                    output += codeContent + '\n';
                    output += fence + '\n';
                }
                break;

            case 'djotMermaid':
                // Mermaid diagrams - use djotSrc if available
                if (node.attrs?.djotSrc) {
                    output += node.attrs.djotSrc;
                    if (!node.attrs.djotSrc.endsWith('\n')) {
                        output += '\n';
                    }
                } else {
                    output += '``` mermaid\n';
                    output += (node.content || []).map(c => c.text || '').join('') + '\n';
                    output += '```\n';
                }
                break;

            case 'djotEmbed':
                // Output the original Djot source directly
                if (node.attrs?.djotSrc) {
                    output += node.attrs.djotSrc + '\n';
                }
                break;

            case 'horizontalRule':
                output += '---\n';
                break;

            case 'hardBreak':
                output += '\\\n';
                break;

            case 'image':
                const imgAlt = node.attrs?.alt || '';
                const imgSrc = node.attrs?.src || '';
                output += '![' + imgAlt + '](' + imgSrc + ')\n';
                break;

            case 'table':
                serializeTable(node);
                break;

            case 'definitionList':
                serializeDefinitionList(node);
                break;

            case 'djotDiv':
                const divClass = node.attrs?.class || '';
                output += ':::' + (divClass ? ' ' + divClass : '') + '\n';
                // Serialize children with blank line separation (like doc level)
                (node.content || []).forEach((child, i) => {
                    serializeNode(child, depth);
                    // Add blank line between all blocks to keep them separate
                    if (i < (node.content || []).length - 1) {
                        output += '\n';
                    }
                });
                output += ':::\n';
                break;

            case 'djotCodeGroup':
                // If we have the original source, use it
                if (node.attrs?.djotSrc) {
                    output += node.attrs.djotSrc + '\n';
                } else {
                    // Try to reconstruct from HTML
                    output += serializeCodeGroupFromHtml(node.attrs?.htmlContent || '');
                }
                break;

            case 'djotTabs':
                // If we have the original source, use it
                if (node.attrs?.djotSrc) {
                    output += node.attrs.djotSrc + '\n';
                } else {
                    // Try to reconstruct from HTML
                    output += serializeTabsFromHtml(node.attrs?.htmlContent || '');
                }
                break;

            default:
                // Log unknown node types for debugging
                console.warn('Serializer: Unknown node type:', node.type, node);
                // Try to serialize children if present
                if (node.content) {
                    (node.content || []).forEach(child => serializeNode(child, depth));
                }
                break;
        }
    }

    function serializeTable(table) {
        const rows = table.content || [];
        if (rows.length === 0) return;

        // Check for preserved column widths from round-trip
        const preservedColWidths = table.attrs?.colWidths || null;

        // First pass: collect all cell texts and find max widths per column
        const allRowTexts = rows.map(row => {
            const cells = row.content || [];
            return cells.map(cell => {
                const content = (cell.content || [])
                    .map(p => serializeInline(p.content))
                    .join(' ');
                return content;
            });
        });

        // Calculate max width per column across all rows (fallback)
        const calculatedColWidths = [];
        allRowTexts.forEach(rowTexts => {
            rowTexts.forEach((text, colIndex) => {
                calculatedColWidths[colIndex] = Math.max(calculatedColWidths[colIndex] || 3, text.length);
            });
        });

        // Use preserved widths if available, otherwise calculated
        const colWidths = preservedColWidths || calculatedColWidths;

        // Second pass: output rows with separator after header
        allRowTexts.forEach((cellTexts, rowIndex) => {
            output += '| ' + cellTexts.join(' | ') + ' |\n';

            // Add separator after header row - use preserved or calculated widths
            if (rowIndex === 0) {
                const separator = colWidths.map((width, i) => {
                    // For round-trip: use preserved widths exactly as they were
                    // For new tables: use calculated content widths (min 3)
                    if (preservedColWidths) {
                        // Preserved widths - use them directly for accurate round-trip
                        return '-'.repeat(Math.max(3, width));
                    }
                    // No preserved widths - calculate from content
                    return '-'.repeat(Math.max(3, calculatedColWidths[i] || 3));
                }).join('|');
                output += '|' + separator + '|\n';
            }
        });
    }

    function serializeDefinitionList(dl) {
        const children = dl.content || [];
        let afterDescription = false;
        children.forEach(child => {
            if (child.type === 'definitionTerm') {
                // Add blank line before term if we just finished a description
                if (afterDescription) {
                    output += '\n';
                }
                output += ': ' + serializeInline(child.content) + '\n';
                afterDescription = false;
            } else if (child.type === 'definitionDescription') {
                output += '\n';
                (child.content || []).forEach(block => {
                    if (block.type === 'paragraph') {
                        output += '  ' + serializeInline(block.content) + '\n';
                    } else {
                        // For other block types, serialize with indentation
                        const blockText = serializeNodeToString(block);
                        blockText.split('\n').filter(l => l).forEach(line => {
                            output += '  ' + line + '\n';
                        });
                    }
                });
                afterDescription = true;
            }
        });
    }

    function serializeNodeToString(node) {
        const oldOutput = output;
        output = '';
        serializeNode(node);
        const result = output;
        output = oldOutput;
        return result.trim();
    }

    function serializeListItem(item, depth, parentIsLoose) {
        const content = item.content || [];
        content.forEach((child, i) => {
            if (child.type === 'paragraph') {
                output += serializeInline(child.content) + '\n';
                // Check what follows this paragraph
                const nextChild = content[i + 1];
                if (nextChild) {
                    const nextIsList = ['bulletList', 'orderedList', 'taskList'].includes(nextChild.type);
                    if (nextIsList) {
                        // Always add blank line before nested list (required by Djot syntax)
                        // This doesn't make it loose since the following content is a list marker
                        output += '\n';
                    } else if (parentIsLoose) {
                        // Add blank line between paragraphs only if parent list is loose
                        output += '\n';
                    }
                }
            } else if (['bulletList', 'orderedList', 'taskList'].includes(child.type)) {
                serializeNode(child, depth + 1);
                // Add blank line after nested list if followed by more content
                if (i < content.length - 1) {
                    output += '\n';
                }
            }
        });
    }

    function serializeInline(content) {
        if (!content) return '';
        let result = '';

        content.forEach(node => {
            if (node.type === 'text') {
                let text = node.text || '';
                const marks = node.marks || [];

                // Check each mark type
                const hasCode = marks.some(m => m.type === 'code');
                const hasBold = marks.some(m => m.type === 'bold');
                const hasItalic = marks.some(m => m.type === 'italic');
                const hasHighlight = marks.some(m => m.type === 'highlight');
                const hasDelete = marks.some(m => m.type === 'djotDelete');
                const hasInsert = marks.some(m => m.type === 'djotInsert');
                const hasSup = marks.some(m => m.type === 'superscript');
                const hasSub = marks.some(m => m.type === 'subscript');
                const link = marks.find(m => m.type === 'link');
                const djotSpan = marks.find(m => m.type === 'djotSpan');
                const abbr = marks.find(m => m.type === 'djotAbbreviation');
                const kbd = marks.find(m => m.type === 'djotKbd');
                const dfn = marks.find(m => m.type === 'djotDefinition');
                const headingRef = marks.find(m => m.type === 'djotHeadingRef');

                // Apply marks from innermost to outermost
                let t = text;
                if (hasCode) t = '`' + t + '`';
                if (hasSub) t = '~' + t + '~';
                if (hasSup) t = '^' + t + '^';
                if (hasInsert) t = '{+' + t + '+}';
                if (hasDelete) t = '{-' + t + '-}';
                if (hasHighlight) t = '{=' + t + '=}';
                if (hasItalic) t = '_' + t + '_';
                if (hasBold) t = '*' + t + '*';
                // Heading references take precedence over regular links
                if (headingRef) {
                    const ref = headingRef.attrs?.headingRef || '';
                    // If display text differs from heading, use [[Heading|display]] syntax
                    if (ref && ref !== text) {
                        t = '[[' + ref + '|' + t + ']]';
                    } else {
                        t = '[[' + (ref || t) + ']]';
                    }
                } else if (link) {
                    t = '[' + t + '](' + link.attrs.href + ')';
                }
                if (djotSpan) t = '[' + t + ']{.' + (djotSpan.attrs?.class || 'class') + '}';
                if (kbd) t = '[' + t + ']{kbd}';
                // Handle dfn and abbr - combine them if both present
                if (dfn && abbr && abbr.attrs?.title) {
                    // Combined: [text]{dfn abbr="..."}
                    const dfnTitle = dfn.attrs?.title || '';
                    const attrs = dfnTitle ? 'dfn="' + dfnTitle + '"' : 'dfn';
                    t = '[' + t + ']{' + attrs + ' abbr="' + abbr.attrs.title + '"}';
                } else if (abbr && abbr.attrs?.title) {
                    t = '[' + t + ']{abbr="' + abbr.attrs.title + '"}';
                } else if (dfn) {
                    const dfnTitle = dfn.attrs?.title || '';
                    t = dfnTitle ? '[' + t + ']{dfn="' + dfnTitle + '"}' : '[' + t + ']{dfn}';
                }

                result += t;
            } else if (node.type === 'hardBreak') {
                result += '\\\n';
            } else if (node.type === 'image') {
                const alt = node.attrs?.alt || '';
                const src = node.attrs?.src || '';
                result += '![' + alt + '](' + src + ')';
            } else if (node.type === 'djotFootnote') {
                const label = node.attrs?.label || 'note';
                result += '[^' + label + ']';
            }
        });

        return result;
    }

    /**
     * Reconstruct code-group Djot from HTML
     * HTML structure: <div class="code-group"><input><label>Tab</label>...<div class="code-group-panel"><pre><code>...</code></pre></div>...
     */
    function serializeCodeGroupFromHtml(html) {
        if (!html) return '';

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const container = doc.querySelector('.code-group');
        if (!container) return html; // Fallback to raw HTML

        let result = '::: code-group\n';

        // Get tab labels
        const labels = container.querySelectorAll('label.code-group-label');
        const panels = container.querySelectorAll('.code-group-panel');

        panels.forEach((panel, i) => {
            const pre = panel.querySelector('pre');
            const code = panel.querySelector('code');
            if (!code) return;

            // Get language from class
            const langMatch = (code.className || '').match(/language-(\w+)/);
            const lang = langMatch ? langMatch[1] : '';

            // Get tab label
            const tabLabel = labels[i] ? labels[i].textContent.trim() : '';

            // Get code content and find safe fence
            const codeContent = (code.textContent || '').trim();
            const fence = findSafeCodeFence(codeContent);

            // Build code fence with safe marker
            result += fence + ' ' + lang;
            if (tabLabel) {
                result += ' [' + tabLabel + ']';
            }
            result += '\n';
            result += codeContent + '\n';
            result += fence + '\n\n';
        });

        // Remove trailing blank line before closing
        result = result.trimEnd() + '\n';
        result += ':::\n';
        return result;
    }

    /**
     * Convert HTML element to Djot markup
     */
    function htmlElementToDjot(element, indent = '') {
        let result = '';

        for (const child of element.childNodes) {
            if (child.nodeType === Node.TEXT_NODE) {
                const text = child.textContent.trim();
                if (text) {
                    result += indent + text + '\n';
                }
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                const tag = child.tagName.toLowerCase();

                if (tag === 'p') {
                    result += indent + (child.textContent || '').trim() + '\n\n';
                } else if (tag === 'h1' || tag === 'h2' || tag === 'h3' || tag === 'h4' || tag === 'h5' || tag === 'h6') {
                    const level = parseInt(tag.charAt(1));
                    result += indent + '#'.repeat(level) + ' ' + (child.textContent || '').trim() + '\n\n';
                } else if (tag === 'ul') {
                    for (const li of child.querySelectorAll(':scope > li')) {
                        result += indent + '- ' + (li.textContent || '').trim() + '\n';
                    }
                    result += '\n';
                } else if (tag === 'ol') {
                    let num = 1;
                    for (const li of child.querySelectorAll(':scope > li')) {
                        result += indent + num + '. ' + (li.textContent || '').trim() + '\n';
                        num++;
                    }
                    result += '\n';
                } else if (tag === 'table') {
                    const rows = child.querySelectorAll('tr');
                    // First pass: collect all cell texts and find max widths
                    const allRowTexts = [];
                    const colWidths = [];
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('th, td');
                        const cellTexts = Array.from(cells).map((c, colIndex) => {
                            let cellContent = '';
                            for (const node of c.childNodes) {
                                if (node.nodeType === Node.TEXT_NODE) {
                                    cellContent += node.textContent;
                                } else if (node.nodeType === Node.ELEMENT_NODE) {
                                    const nodeName = node.tagName.toLowerCase();
                                    if (nodeName === 'code') {
                                        cellContent += '`' + node.textContent + '`';
                                    } else if (nodeName === 'strong' || nodeName === 'b') {
                                        cellContent += '*' + node.textContent + '*';
                                    } else if (nodeName === 'em' || nodeName === 'i') {
                                        cellContent += '_' + node.textContent + '_';
                                    } else {
                                        cellContent += node.textContent;
                                    }
                                }
                            }
                            const text = cellContent.trim();
                            colWidths[colIndex] = Math.max(colWidths[colIndex] || 3, text.length);
                            return text;
                        });
                        allRowTexts.push(cellTexts);
                    });
                    // Second pass: output with correct separator widths
                    allRowTexts.forEach((cellTexts, rowIndex) => {
                        result += indent + '| ' + cellTexts.join(' | ') + ' |\n';
                        if (rowIndex === 0) {
                            const separator = colWidths.map(width => '-'.repeat(width)).join('|');
                            result += indent + '|' + separator + '|\n';
                        }
                    });
                    result += '\n';
                } else if (tag === 'pre') {
                    const code = child.querySelector('code');
                    const langMatch = code ? (code.className || '').match(/language-(\w+)/) : null;
                    const lang = langMatch ? langMatch[1] : '';
                    // Get code content, preserving newlines
                    const codeEl = code || child;
                    // Check for line spans (Torchlight format)
                    const lineSpans = codeEl.querySelectorAll('.line');
                    let codeContent;
                    if (lineSpans.length > 0) {
                        // Extract text from each line span
                        codeContent = Array.from(lineSpans).map(span => span.textContent).join('\n');
                    } else {
                        // Use textContent directly - it preserves newlines
                        codeContent = codeEl.textContent || '';
                    }
                    // Use safe fence that doesn't conflict with content backticks
                    const fence = findSafeCodeFence(codeContent);
                    result += indent + fence + (lang ? ' ' + lang : '') + '\n';
                    result += codeContent;
                    if (!codeContent.endsWith('\n')) result += '\n';
                    result += indent + fence + '\n\n';
                } else if (tag === 'blockquote') {
                    const inner = htmlElementToDjot(child, '');
                    inner.trim().split('\n').forEach(line => {
                        result += indent + '> ' + line + '\n';
                    });
                    result += '\n';
                } else {
                    // Recurse for other elements (div, etc.)
                    result += htmlElementToDjot(child, indent);
                }
            }
        }

        return result;
    }

    /**
     * Reconstruct tabs Djot from HTML
     * HTML structure: <div class="tabs"><input><label>Tab</label>...<div class="tabs-panel">...</div>...
     */
    function serializeTabsFromHtml(html) {
        if (!html) return '';

        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const container = doc.querySelector('.tabs');
        if (!container) return html; // Fallback to raw HTML

        let result = ':::: tabs\n\n';

        // Get tab labels and panels
        const labels = container.querySelectorAll('label.tabs-label');
        const panels = container.querySelectorAll('.tabs-panel');

        panels.forEach((panel, i) => {
            const tabLabel = labels[i] ? labels[i].textContent.trim() : 'Tab ' + (i + 1);

            result += '::: tab\n';
            // Add heading as the tab label (djot-php extracts this)
            result += '### ' + tabLabel + '\n\n';
            // Convert panel content to Djot
            result += htmlElementToDjot(panel);
            result += ':::\n\n';
        });

        result += '::::\n';
        return result;
    }

    serializeNode(doc);
    return output.trim();
}

/**
 * Escape special Djot characters in text
 *
 * @param {string} text - Plain text to escape
 * @returns {string} Escaped text safe for Djot
 */
export function escapeDjot(text) {
    return text
        .replace(/\\/g, '\\\\')
        .replace(/\*/g, '\\*')
        .replace(/_/g, '\\_')
        .replace(/\[/g, '\\[')
        .replace(/\]/g, '\\]')
        .replace(/\{/g, '\\{')
        .replace(/\}/g, '\\}')
        .replace(/\^/g, '\\^')
        .replace(/~/g, '\\~')
        .replace(/`/g, '\\`');
}

/**
 * Find a safe code fence that doesn't conflict with content
 *
 * @param {string} content - The code content to check
 * @param {number} minLength - Minimum fence length (default 3)
 * @returns {string} A backtick fence that's safe to use
 */
function findSafeCodeFence(content, minLength = 3) {
    // Find the longest sequence of backticks in the content
    let maxBackticks = 0;
    const matches = content.match(/`+/g);
    if (matches) {
        for (const match of matches) {
            maxBackticks = Math.max(maxBackticks, match.length);
        }
    }
    // Use a fence that's at least one backtick longer than the longest sequence
    const fenceLength = Math.max(minLength, maxBackticks + 1);
    return '`'.repeat(fenceLength);
}

export default serializeToDjot;
