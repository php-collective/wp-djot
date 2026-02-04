<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase\Converter;

use PHPUnit\Framework\TestCase;
use WpDjot\Converter\WpMarkdownToDjot;

class WpMarkdownToDjotTest extends TestCase
{
    private WpMarkdownToDjot $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new WpMarkdownToDjot();
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

        // Without title, abbr is stripped to plain text
        $this->assertSame('The CSS spec', $djot);
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
