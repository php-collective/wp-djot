/**
 * Visual Editor Round-Trip Tests
 *
 * Tests that content survives: Djot → HTML → TipTap → Djot
 *
 * Each test case has:
 * - djot: The original Djot markup
 * - html: What djot-php produces (fixture)
 * - expected: What the serializer should produce (may differ slightly from original)
 */

import { describe, it, expect } from 'vitest';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import Highlight from '@tiptap/extension-highlight';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import CodeBlock from '@tiptap/extension-code-block';
import BulletList from '@tiptap/extension-bullet-list';
import OrderedList from '@tiptap/extension-ordered-list';
import ListItem from '@tiptap/extension-list-item';

import { serializeToDjot } from '../../assets/js/tiptap/serializer.js';

// Import custom extensions
import { DjotInsert } from '../../assets/js/tiptap/extensions/djot-insert.js';
import { DjotDelete } from '../../assets/js/tiptap/extensions/djot-delete.js';
import { DjotDiv } from '../../assets/js/tiptap/extensions/djot-div.js';
import { DjotSpan } from '../../assets/js/tiptap/extensions/djot-span.js';
import { DjotKbd } from '../../assets/js/tiptap/extensions/djot-kbd.js';
import { DjotAbbreviation } from '../../assets/js/tiptap/extensions/djot-abbr.js';
import { DjotDefinition } from '../../assets/js/tiptap/extensions/djot-dfn.js';
import {
  DefinitionList,
  DefinitionTerm,
  DefinitionDescription,
} from '../../assets/js/tiptap/extensions/djot-definition-list.js';
import { DjotHeadingRef } from '../../assets/js/tiptap/extensions/djot-heading-ref.js';
import { DjotMermaid } from '../../assets/js/tiptap/extensions/djot-mermaid.js';
import { DjotCodeGroup } from '../../assets/js/tiptap/extensions/djot-code-group.js';
import { DjotTabs } from '../../assets/js/tiptap/extensions/djot-tabs.js';

/**
 * Create a TipTap editor with all extensions for testing
 */
function createEditor(htmlContent) {
  const CustomCodeBlock = CodeBlock.extend({
    addAttributes() {
      return {
        ...this.parent?.(),
        languageRaw: {
          default: null,
          parseHTML: element => {
            const pre = element.closest('pre');
            return pre?.getAttribute('data-language-raw') || null;
          },
          renderHTML: attributes => {
            if (!attributes.languageRaw) return {};
            return { 'data-language-raw': attributes.languageRaw };
          },
        },
        djotSrc: {
          default: null,
          parseHTML: element => {
            const pre = element.closest('pre');
            return pre?.getAttribute('data-djot-src') || null;
          },
        },
      };
    },
  });

  const editor = new Editor({
    extensions: [
      StarterKit.configure({
        codeBlock: false,
        bulletList: false,
        orderedList: false,
        listItem: false,
      }),
      CustomCodeBlock,
      BulletList,
      OrderedList,
      ListItem,
      Link,
      Image,
      Table,
      TableRow,
      TableCell,
      TableHeader,
      TaskList,
      TaskItem.configure({ nested: true }),
      Highlight,
      Subscript,
      Superscript,
      DjotInsert,
      DjotDelete,
      DjotDiv,
      DjotSpan,
      DjotKbd,
      DjotAbbreviation,
      DjotDefinition,
      DefinitionList,
      DefinitionTerm,
      DefinitionDescription,
      DjotHeadingRef,
      DjotMermaid,
      DjotCodeGroup,
      DjotTabs,
    ],
    content: htmlContent,
  });
  return editor;
}

/**
 * Normalize Djot for comparison (ignore whitespace differences)
 */
function normalize(djot) {
  return djot
    .trim()
    .replace(/\r\n/g, '\n')
    .replace(/[ \t]+$/gm, '')  // trailing whitespace
    .replace(/\n{3,}/g, '\n\n'); // multiple blank lines
}

/**
 * Test round-trip for a given case
 */
function testRoundTrip(name, html, expectedDjot) {
  it(name, () => {
    const editor = createEditor(html);
    const result = serializeToDjot(editor.getJSON());
    editor.destroy();

    expect(normalize(result)).toBe(normalize(expectedDjot));
  });
}

describe('Visual Editor Round-Trip', () => {
  describe('Basic formatting', () => {
    testRoundTrip(
      'bold text',
      '<p><strong>bold</strong></p>',
      '*bold*'
    );

    testRoundTrip(
      'italic text',
      '<p><em>italic</em></p>',
      '_italic_'
    );

    testRoundTrip(
      'inline code',
      '<p><code>code</code></p>',
      '`code`'
    );

    testRoundTrip(
      'combined bold and italic',
      '<p><strong><em>bold italic</em></strong></p>',
      '*_bold italic_*'
    );
  });

  describe('Headings', () => {
    testRoundTrip(
      'h1 heading',
      '<h1>Heading 1</h1>',
      '# Heading 1'
    );

    testRoundTrip(
      'h2 heading',
      '<h2>Heading 2</h2>',
      '## Heading 2'
    );

    testRoundTrip(
      'h3 heading',
      '<h3>Heading 3</h3>',
      '### Heading 3'
    );
  });

  describe('Links', () => {
    testRoundTrip(
      'simple link',
      '<p><a href="https://example.com">link text</a></p>',
      '[link text](https://example.com)'
    );
  });

  describe('Heading references', () => {
    testRoundTrip(
      'heading reference same text',
      '<p><a href="#Code-Groups" class="heading-ref" data-heading-ref="Code Groups">Code Groups</a></p>',
      '[[Code Groups]]'
    );

    testRoundTrip(
      'heading reference with display text',
      '<p><a href="#Code-Groups" class="heading-ref" data-heading-ref="Code Groups">see code groups</a></p>',
      '[[Code Groups|see code groups]]'
    );
  });

  describe('Semantic spans', () => {
    testRoundTrip(
      'kbd element',
      '<p><kbd>Ctrl+C</kbd></p>',
      '[Ctrl+C]{kbd}'
    );

    testRoundTrip(
      'abbr element',
      '<p><abbr title="HyperText Markup Language">HTML</abbr></p>',
      '[HTML]{abbr="HyperText Markup Language"}'
    );

    testRoundTrip(
      'dfn element',
      '<p><dfn>API</dfn></p>',
      '[API]{dfn}'
    );

    testRoundTrip(
      'combined dfn and abbr',
      '<p><dfn><abbr title="Cascading Style Sheets">CSS</abbr></dfn></p>',
      '[CSS]{dfn abbr="Cascading Style Sheets"}'
    );
  });

  describe('Definition lists', () => {
    testRoundTrip(
      'definition list',
      '<dl><dt>Term</dt><dd><p>Definition with <em>em</em></p></dd></dl>',
      ': Term\n\n  Definition with _em_'
    );
  });

  describe('Code blocks', () => {
    testRoundTrip(
      'plain code block',
      '<pre><code>const x = 1;</code></pre>',
      '```\nconst x = 1;\n```'
    );

    testRoundTrip(
      'code block with language',
      '<pre><code class="language-javascript">const x = 1;</code></pre>',
      '``` javascript\nconst x = 1;\n```'
    );

    testRoundTrip(
      'safe fenced markdown code block with preserved djot source',
      '<pre data-djot-src="```` markdown&#10;Here is how to write a code block in Markdown:&#10;&#10;```javascript&#10;console.log(&quot;Hello&quot;);&#10;```&#10;&#10;The triple backticks create a fenced code block.&#10;````&#10;"><code class="language-markdown">Here is how to write a code block in Markdown:\n\n```javascript\nconsole.log(&quot;Hello&quot;);\n```\n\nThe triple backticks create a fenced code block.</code></pre>',
      '```` markdown\nHere is how to write a code block in Markdown:\n\n```javascript\nconsole.log("Hello");\n```\n\nThe triple backticks create a fenced code block.\n````'
    );
  });

  describe('Mermaid diagrams', () => {
    testRoundTrip(
      'mermaid block',
      '<pre class="mermaid">graph TD;\n    A-->B;</pre>',
      '``` mermaid\ngraph TD;\n    A-->B;\n```'
    );
  });

  describe('Lists', () => {
    testRoundTrip(
      'bullet list',
      '<ul><li><p>Item 1</p></li><li><p>Item 2</p></li></ul>',
      '- Item 1\n- Item 2'
    );

    testRoundTrip(
      'ordered list',
      '<ol><li><p>First</p></li><li><p>Second</p></li></ol>',
      '1. First\n2. Second'
    );

    testRoundTrip(
      'task list',
      '<ul data-type="taskList"><li data-type="taskItem" data-checked="false"><p>Todo</p></li><li data-type="taskItem" data-checked="true"><p>Done</p></li></ul>',
      '- [ ] Todo\n- [x] Done'
    );
  });

  describe('Tables', () => {
    testRoundTrip(
      'simple table',
      '<table><tr><th><p>Header</p></th></tr><tr><td><p>Cell</p></td></tr></table>',
      '| Header |\n| --- |\n| Cell |'
    );
  });

  describe('Divs/Containers', () => {
    testRoundTrip(
      'note container',
      '<div class="note"><p>Note content</p></div>',
      '::: note\nNote content\n:::'
    );

    testRoundTrip(
      'warning container',
      '<div class="warning"><p>Warning content</p></div>',
      '::: warning\nWarning content\n:::'
    );
  });

  describe('Code groups', () => {
    testRoundTrip(
      'code group with tabs',
      `<div class="code-group">
        <input type="radio" name="codegroup-1" id="codegroup-1-tab-1" class="code-group-radio" checked>
        <label for="codegroup-1-tab-1" class="code-group-label">PHP</label>
        <input type="radio" name="codegroup-1" id="codegroup-1-tab-2" class="code-group-radio">
        <label for="codegroup-1-tab-2" class="code-group-label">JS</label>
        <div class="code-group-panel"><pre><code class="language-php">echo 1;</code></pre></div>
        <div class="code-group-panel"><pre><code class="language-js">console.log(1);</code></pre></div>
      </div>`,
      `::: code-group
\`\`\` php [PHP]
echo 1;
\`\`\`

\`\`\` js [JS]
console.log(1);
\`\`\`
:::`
    );
  });

  describe('Tabs', () => {
    testRoundTrip(
      'tabs container',
      `<div class="tabs">
        <input type="radio" name="tabset-1" id="tabset-1-tab-1" class="tabs-radio" checked>
        <label for="tabset-1-tab-1" class="tabs-label">Tab 1</label>
        <input type="radio" name="tabset-1" id="tabset-1-tab-2" class="tabs-radio">
        <label for="tabset-1-tab-2" class="tabs-label">Tab 2</label>
        <div class="tabs-panel"><p>Content 1</p></div>
        <div class="tabs-panel"><p>Content 2</p></div>
      </div>`,
      `:::: tabs

::: tab
### Tab 1

Content 1

:::

::: tab
### Tab 2

Content 2

:::

::::`
    );

    testRoundTrip(
      'tabs with nested content',
      `<div class="tabs">
        <input type="radio" name="tabset-1" id="tabset-1-tab-1" class="tabs-radio" checked>
        <label for="tabset-1-tab-1" class="tabs-label">Getting Started</label>
        <input type="radio" name="tabset-1" id="tabset-1-tab-2" class="tabs-radio">
        <label for="tabset-1-tab-2" class="tabs-label">Configuration</label>
        <div class="tabs-panel">
          <p>Welcome to the documentation!</p>
          <ul><li>Step 1</li><li>Step 2</li></ul>
        </div>
        <div class="tabs-panel">
          <p>Configuration options:</p>
          <table><tr><th>Option</th><th>Default</th></tr><tr><td>enabled</td><td>true</td></tr></table>
        </div>
      </div>`,
      `:::: tabs

::: tab
### Getting Started

Welcome to the documentation!

- Step 1
- Step 2

:::

::: tab
### Configuration

Configuration options:

| Option | Default |
| --- | --- |
| enabled | true |

:::

::::`
    );
  });
});
