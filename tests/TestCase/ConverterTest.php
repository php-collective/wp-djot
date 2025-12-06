<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase;

use Djot\DjotConverter;
use Djot\Event\RenderEvent;
use Djot\Node\Inline\Text;
use PHPUnit\Framework\TestCase;
use WpDjot\Converter;

class ConverterTest extends TestCase
{
    private Converter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new Converter(safeMode: false);
    }

    public function testPreservesInternalNewlines(): void
    {
        $djot = "# Heading\n\nParagraph 1\n\nParagraph 2";

        $html = $this->converter->convert($djot);

        // Should have separate paragraphs
        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Paragraph 1', $html);
        $this->assertStringContainsString('Paragraph 2', $html);
        // Two separate paragraphs, not merged
        $this->assertEquals(2, substr_count($html, '<p>'));
    }

    public function testPreservesCodeBlockNewlines(): void
    {
        $djot = "```\nline1\nline2\nline3\n```";

        $html = $this->converter->convert($djot);

        $this->assertStringContainsString('<code>', $html);
        // Newlines should be preserved inside code block
        $this->assertStringContainsString("line1\nline2\nline3", $html);
    }

    public function testPreservesListNewlines(): void
    {
        $djot = "- Item 1\n- Item 2\n- Item 3";

        $html = $this->converter->convert($djot);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertEquals(3, substr_count($html, '<li>'));
    }

    public function testNormalizesWindowsLineEndings(): void
    {
        $djot = "Line 1\r\n\r\nLine 2";

        $html = $this->converter->convert($djot);

        // Should still create two paragraphs
        $this->assertEquals(2, substr_count($html, '<p>'));
    }

    public function testNormalizesOldMacLineEndings(): void
    {
        $djot = "Line 1\r\rLine 2";

        $html = $this->converter->convert($djot);

        // Should still create two paragraphs
        $this->assertEquals(2, substr_count($html, '<p>'));
    }

    public function testTrimsLeadingTrailingWhitespace(): void
    {
        $djot = "\n\n  # Heading  \n\n";

        $html = $this->converter->convert($djot);

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('Heading', $html);
    }

    public function testBlockquoteNewlines(): void
    {
        $djot = "> Line 1\n> Line 2\n>\n> Line 3";

        $html = $this->converter->convert($djot);

        $this->assertStringContainsString('<blockquote>', $html);
    }

    public function testPreservesMultipleBlankLines(): void
    {
        // In Djot, multiple blank lines between paragraphs should still result in separate paragraphs
        $djot = "Paragraph 1\n\n\n\nParagraph 2";

        $html = $this->converter->convert($djot);

        $this->assertEquals(2, substr_count($html, '<p>'));
    }

    public function testDivBlockNewlines(): void
    {
        $djot = "::: note\nLine 1\n\nLine 2\n:::";

        $html = $this->converter->convert($djot);

        $this->assertStringContainsString('class="note"', $html);
    }

    public function testSafeModePreservesNewlines(): void
    {
        $safeConverter = new Converter(safeMode: true);
        $djot = "# Heading\n\nParagraph 1\n\nParagraph 2";

        $html = $safeConverter->convert($djot);

        $this->assertEquals(2, substr_count($html, '<p>'));
    }

    public function testAbbreviationRendering(): void
    {
        // Test the abbreviation rendering logic directly using DjotConverter
        $djotConverter = new DjotConverter(safeMode: false);

        // Add the abbreviation handler (same logic as Plugin::customizeConverter)
        $djotConverter->getRenderer()->on('render.span', function (RenderEvent $event): void {
            $node = $event->getNode();
            $abbr = $node->getAttribute('abbr');

            if ($abbr === null) {
                return;
            }

            $attrStr = ' title="' . htmlspecialchars((string)$abbr, ENT_QUOTES, 'UTF-8') . '"';

            foreach ($node->getAttributes() as $key => $value) {
                if ($key === 'abbr') {
                    continue;
                }
                $attrStr .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                    . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }

            $children = '';
            foreach ($node->getChildren() as $child) {
                if ($child instanceof Text) {
                    $children .= htmlspecialchars($child->getContent(), ENT_NOQUOTES, 'UTF-8');
                }
            }

            $event->setHtml('<abbr' . $attrStr . '>' . $children . '</abbr>');
            $event->preventDefault();
        });

        $djot = '[CSS]{abbr="Cascading Style Sheets"}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<abbr', $html);
        $this->assertStringContainsString('title="Cascading Style Sheets"', $html);
        $this->assertStringContainsString('>CSS</abbr>', $html);
    }

    public function testAbbreviationWithAdditionalAttributes(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);

        $djotConverter->getRenderer()->on('render.span', function (RenderEvent $event): void {
            $node = $event->getNode();
            $abbr = $node->getAttribute('abbr');

            if ($abbr === null) {
                return;
            }

            $attrStr = ' title="' . htmlspecialchars((string)$abbr, ENT_QUOTES, 'UTF-8') . '"';

            foreach ($node->getAttributes() as $key => $value) {
                if ($key === 'abbr') {
                    continue;
                }
                $attrStr .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                    . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }

            $children = '';
            foreach ($node->getChildren() as $child) {
                if ($child instanceof Text) {
                    $children .= htmlspecialchars($child->getContent(), ENT_NOQUOTES, 'UTF-8');
                }
            }

            $event->setHtml('<abbr' . $attrStr . '>' . $children . '</abbr>');
            $event->preventDefault();
        });

        $djot = '[HTML]{abbr="HyperText Markup Language" .tech-term}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<abbr', $html);
        $this->assertStringContainsString('title="HyperText Markup Language"', $html);
        $this->assertStringContainsString('class="tech-term"', $html);
        $this->assertStringContainsString('>HTML</abbr>', $html);
    }

    public function testSpanWithoutAbbrRemainsSpan(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);

        $djotConverter->getRenderer()->on('render.span', function (RenderEvent $event): void {
            $node = $event->getNode();
            $abbr = $node->getAttribute('abbr');

            if ($abbr === null) {
                return;
            }

            $event->setHtml('<abbr>test</abbr>');
            $event->preventDefault();
        });

        $djot = '[regular text]{.highlight}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('class="highlight"', $html);
        $this->assertStringNotContainsString('<abbr', $html);
    }

    public function testKbdRendering(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);
        $this->addSemanticSpanHandler($djotConverter);

        $djot = '[Ctrl+C]{kbd=""}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<kbd', $html);
        $this->assertStringContainsString('>Ctrl+C</kbd>', $html);
    }

    public function testKbdWithClass(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);
        $this->addSemanticSpanHandler($djotConverter);

        $djot = '[Ctrl+V]{kbd="" .shortcut}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<kbd', $html);
        $this->assertStringContainsString('class="shortcut"', $html);
        $this->assertStringContainsString('>Ctrl+V</kbd>', $html);
    }

    public function testDfnRendering(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);
        $this->addSemanticSpanHandler($djotConverter);

        $djot = '[API]{dfn=""}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<dfn', $html);
        $this->assertStringContainsString('>API</dfn>', $html);
        $this->assertStringNotContainsString('title=', $html);
    }

    public function testDfnWithTitle(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);
        $this->addSemanticSpanHandler($djotConverter);

        $djot = '[API]{dfn="Application Programming Interface"}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<dfn', $html);
        $this->assertStringContainsString('title="Application Programming Interface"', $html);
        $this->assertStringContainsString('>API</dfn>', $html);
    }

    public function testCombinedDfnAndAbbr(): void
    {
        $djotConverter = new DjotConverter(safeMode: false);
        $this->addSemanticSpanHandler($djotConverter);

        $djot = '[HST]{dfn="" abbr="Hubble Space Telescope"}';
        $html = $djotConverter->convert($djot);

        $this->assertStringContainsString('<dfn>', $html);
        $this->assertStringContainsString('<abbr title="Hubble Space Telescope">HST</abbr>', $html);
        $this->assertStringContainsString('</dfn>', $html);
    }

    /**
     * Add the semantic span handler (same logic as Plugin::customizeConverter)
     */
    private function addSemanticSpanHandler(DjotConverter $converter): void
    {
        $converter->getRenderer()->on('render.span', function (RenderEvent $event): void {
            $node = $event->getNode();

            $abbr = $node->getAttribute('abbr');
            $kbd = $node->getAttribute('kbd');
            $dfn = $node->getAttribute('dfn');

            $excludeAttrs = [];

            $children = '';
            foreach ($node->getChildren() as $child) {
                if ($child instanceof Text) {
                    $children .= htmlspecialchars($child->getContent(), ENT_NOQUOTES, 'UTF-8');
                }
            }

            $content = $children;

            if ($abbr !== null) {
                $abbrTitle = ' title="' . htmlspecialchars((string)$abbr, ENT_QUOTES, 'UTF-8') . '"';
                $content = '<abbr' . $abbrTitle . '>' . $children . '</abbr>';
                $excludeAttrs[] = 'abbr';
            } elseif ($kbd !== null) {
                $content = '<kbd>' . $children . '</kbd>';
                $excludeAttrs[] = 'kbd';
            }

            if ($dfn !== null) {
                $dfnAttr = '';
                if ($dfn !== '' && $dfn !== true) {
                    $dfnAttr = ' title="' . htmlspecialchars((string)$dfn, ENT_QUOTES, 'UTF-8') . '"';
                }
                $content = '<dfn' . $dfnAttr . '>' . $content . '</dfn>';
                $excludeAttrs[] = 'dfn';
            }

            if (!$excludeAttrs) {
                return;
            }

            $remainingAttrs = [];
            foreach ($node->getAttributes() as $key => $value) {
                if (in_array($key, $excludeAttrs, true)) {
                    continue;
                }
                $remainingAttrs[$key] = $value;
            }

            if ($remainingAttrs) {
                $attrStr = '';
                foreach ($remainingAttrs as $key => $value) {
                    $attrStr .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
                        . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
                }
                $content = '<span' . $attrStr . '>' . $content . '</span>';
            }

            $event->setHtml($content);
            $event->preventDefault();
        });
    }
}
