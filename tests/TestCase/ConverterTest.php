<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase;

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
}
