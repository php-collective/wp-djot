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
                    if (i < (node.content || []).length - 1) {
                        const curr = child.type;
                        const next = node.content[i + 1]?.type;
                        // Don't add extra blank line between list items
                        if (!['bulletList', 'orderedList', 'taskList'].includes(curr) ||
                            !['bulletList', 'orderedList', 'taskList'].includes(next)) {
                            output += '\n';
                        }
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
                (node.content || []).forEach(item => {
                    output += '  '.repeat(depth) + '- ';
                    serializeListItem(item, depth);
                });
                break;

            case 'orderedList':
                let num = node.attrs?.start || 1;
                (node.content || []).forEach(item => {
                    output += '  '.repeat(depth) + num + '. ';
                    serializeListItem(item, depth);
                    num++;
                });
                break;

            case 'taskList':
                (node.content || []).forEach(item => {
                    const checked = item.attrs?.checked ? 'x' : ' ';
                    output += '  '.repeat(depth) + '- [' + checked + '] ';
                    serializeListItem(item, depth);
                });
                break;

            case 'blockquote':
                const bqLines = [];
                (node.content || []).forEach(child => {
                    const childText = serializeNodeToString(child);
                    childText.split('\n').filter(l => l).forEach(line => {
                        bqLines.push('> ' + line);
                    });
                });
                output += bqLines.join('\n') + '\n';
                break;

            case 'codeBlock':
                const lang = node.attrs?.language || '';
                // Djot uses space between ``` and language
                output += '```' + (lang ? ' ' + lang : '') + '\n';
                output += (node.content || []).map(c => c.text || '').join('') + '\n';
                output += '```\n';
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

            case 'djotDiv':
                const divClass = node.attrs?.class || '';
                output += ':::' + (divClass ? ' ' + divClass : '') + '\n';
                (node.content || []).forEach(child => serializeNode(child, depth));
                output += ':::\n';
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

    function serializeNodeToString(node) {
        const oldOutput = output;
        output = '';
        serializeNode(node);
        const result = output;
        output = oldOutput;
        return result.trim();
    }

    function serializeListItem(item, depth) {
        const content = item.content || [];
        content.forEach((child) => {
            if (child.type === 'paragraph') {
                output += serializeInline(child.content) + '\n';
            } else if (['bulletList', 'orderedList', 'taskList'].includes(child.type)) {
                serializeNode(child, depth + 1);
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
                const hasStrike = marks.some(m => m.type === 'strike');
                const link = marks.find(m => m.type === 'link');
                const djotSpan = marks.find(m => m.type === 'djotSpan');

                // Apply marks from innermost to outermost
                let t = text;
                if (hasCode) t = '`' + t + '`';
                if (hasSub) t = '~' + t + '~';
                if (hasSup) t = '^' + t + '^';
                if (hasInsert) t = '{+' + t + '+}';
                if (hasDelete) t = '{-' + t + '-}';
                if (hasStrike) t = '{~' + t + '~}';
                if (hasHighlight) t = '{=' + t + '=}';
                if (hasItalic) t = '_' + t + '_';
                if (hasBold) t = '*' + t + '*';
                if (link) t = '[' + t + '](' + link.attrs.href + ')';
                if (djotSpan) t = '[' + t + ']{.' + (djotSpan.attrs?.class || 'class') + '}';

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
