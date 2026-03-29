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
                // Use languageRaw (with Torchlight options) if available, otherwise language
                const lang = node.attrs?.languageRaw || node.attrs?.language || '';
                // Djot uses space between ``` and language
                output += '```' + (lang ? ' ' + lang : '') + '\n';
                output += (node.content || []).map(c => c.text || '').join('') + '\n';
                output += '```\n';
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

        rows.forEach((row, rowIndex) => {
            const cells = row.content || [];
            const cellTexts = cells.map(cell => {
                const content = (cell.content || [])
                    .map(p => serializeInline(p.content))
                    .join(' ');
                return content;
            });
            output += '| ' + cellTexts.join(' | ') + ' |\n';

            // Add separator after header row
            if (rowIndex === 0) {
                const separator = cells.map(() => '---').join(' | ');
                output += '| ' + separator + ' |\n';
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
                if (link) t = '[' + t + '](' + link.attrs.href + ')';
                if (djotSpan) t = '[' + t + ']{.' + (djotSpan.attrs?.class || 'class') + '}';
                if (abbr && abbr.attrs?.title) t = '[' + t + ']{abbr="' + abbr.attrs.title + '"}';
                if (kbd) t = '[' + t + ']{kbd}';
                if (dfn) {
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

export default serializeToDjot;
