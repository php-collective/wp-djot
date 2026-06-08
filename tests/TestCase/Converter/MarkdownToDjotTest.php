<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase\Converter;

use Djot\Converter\MarkdownToDjot;
use PHPUnit\Framework\TestCase;

/**
 * Pins the Markdown to Djot conversion contract that the migrate command and
 * the convert-markdown REST endpoint rely on. The semantic-element conversion
 * (kbd/abbr/dfn) lives in djot-php; this guards wp-djot's features against
 * regressions from library upgrades.
 */
class MarkdownToDjotTest extends TestCase
{
    private MarkdownToDjot $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new MarkdownToDjot();
    }

    public function testKbd(): void
    {
        $md = 'Press <kbd>Ctrl+C</kbd> to copy';
        $djot = $this->converter->convert($md);

        $this->assertSame('Press [Ctrl+C]{kbd} to copy', $djot);
    }

    public function testMultipleKbd(): void
    {
        $md = 'Press <kbd>Ctrl</kbd>+<kbd>C</kbd>';
        $djot = $this->converter->convert($md);

        $this->assertSame('Press [Ctrl]{kbd}+[C]{kbd}', $djot);
    }

    public function testAbbrWithTitle(): void
    {
        $md = 'The <abbr title="HyperText Markup Language">HTML</abbr> standard';
        $djot = $this->converter->convert($md);

        $this->assertSame('The [HTML]{abbr="HyperText Markup Language"} standard', $djot);
    }

    public function testAbbrWithSingleQuotes(): void
    {
        $md = "The <abbr title='Cascading Style Sheets'>CSS</abbr> spec";
        $djot = $this->converter->convert($md);

        $this->assertSame('The [CSS]{abbr="Cascading Style Sheets"} spec', $djot);
    }

    public function testAbbrWithoutTitle(): void
    {
        $md = 'The <abbr>CSS</abbr> spec';
        $djot = $this->converter->convert($md);

        // Title-less abbr is preserved as a flagged span so it can round-trip.
        $this->assertSame('The [CSS]{abbr} spec', $djot);
    }

    public function testDfn(): void
    {
        $md = '<dfn>Term</dfn> is a definition';
        $djot = $this->converter->convert($md);

        $this->assertSame('[Term]{dfn} is a definition', $djot);
    }

    public function testDfnWithTitle(): void
    {
        $md = '<dfn title="Explanation">Term</dfn> means something';
        $djot = $this->converter->convert($md);

        $this->assertSame('[Term]{dfn="Explanation"} means something', $djot);
    }

    public function testMixedSemanticElements(): void
    {
        $md = 'Press <kbd>F1</kbd> to see the <abbr title="Application Programming Interface">API</abbr> docs';
        $djot = $this->converter->convert($md);

        $this->assertSame('Press [F1]{kbd} to see the [API]{abbr="Application Programming Interface"} docs', $djot);
    }

    public function testPreservesMarkdownFormatting(): void
    {
        $md = "# Heading\n\n**Bold** and _italic_";
        $djot = $this->converter->convert($md);

        $this->assertStringContainsString('# Heading', $djot);
        $this->assertStringContainsString('*Bold*', $djot);
        $this->assertStringContainsString('_italic_', $djot);
    }

    public function testNestedList(): void
    {
        $md = "- Item 1\n- Item 2\n  - Nested";
        $djot = $this->converter->convert($md);

        $this->assertStringContainsString('- Item 1', $djot);
        $this->assertStringContainsString('- Item 2', $djot);
        $this->assertStringContainsString('- Nested', $djot);
    }

    public function testCodeBlock(): void
    {
        $md = "```php\ncode\n```";
        $djot = $this->converter->convert($md);

        $this->assertStringContainsString('```php', $djot);
        $this->assertStringContainsString('code', $djot);
    }
}
