<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase\Converter;

use PHPUnit\Framework\TestCase;
use WpDjot\Converter\WpHtmlToDjot;

class WpHtmlToDjotTest extends TestCase
{
    private WpHtmlToDjot $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new WpHtmlToDjot();
    }

    public function testKbd(): void
    {
        $html = '<kbd>Ctrl+C</kbd>';
        $djot = $this->converter->convert($html);

        $this->assertSame("[Ctrl+C]{kbd}\n", $djot);
    }

    public function testKbdInParagraph(): void
    {
        $html = '<p>Press <kbd>Ctrl</kbd>+<kbd>C</kbd> to copy</p>';
        $djot = $this->converter->convert($html);

        $this->assertSame("Press [Ctrl]{kbd}+[C]{kbd} to copy\n", $djot);
    }

    public function testAbbrWithTitle(): void
    {
        $html = '<abbr title="HyperText Markup Language">HTML</abbr>';
        $djot = $this->converter->convert($html);

        $this->assertSame("[HTML]{abbr=\"HyperText Markup Language\"}\n", $djot);
    }

    public function testAbbrWithoutTitle(): void
    {
        $html = '<abbr>CSS</abbr>';
        $djot = $this->converter->convert($html);

        // Without title, abbr is rendered as plain text
        $this->assertSame("CSS\n", $djot);
    }

    public function testDfn(): void
    {
        $html = '<dfn>term</dfn>';
        $djot = $this->converter->convert($html);

        $this->assertSame("[term]{dfn}\n", $djot);
    }

    public function testDfnWithTitle(): void
    {
        $html = '<dfn title="A definition">term</dfn>';
        $djot = $this->converter->convert($html);

        $this->assertSame("[term]{dfn=\"A definition\"}\n", $djot);
    }

    public function testMixedSemanticElements(): void
    {
        $html = '<p>The <abbr title="World Wide Web">WWW</abbr> uses <kbd>HTTP</kbd> protocol</p>';
        $djot = $this->converter->convert($html);

        $this->assertSame("The [WWW]{abbr=\"World Wide Web\"} uses [HTTP]{kbd} protocol\n", $djot);
    }

    public function testEscapesQuotesInAttrValue(): void
    {
        $html = '<abbr title="Quote &quot;test&quot; here">TEXT</abbr>';
        $djot = $this->converter->convert($html);

        $this->assertStringContainsString('abbr="Quote \\"test\\" here"', $djot);
    }

    public function testPreservesRegularElements(): void
    {
        $html = '<p>This is <strong>bold</strong> and <em>italic</em></p>';
        $djot = $this->converter->convert($html);

        $this->assertStringContainsString('*bold*', $djot);
        $this->assertStringContainsString('_italic_', $djot);
    }

    public function testNestedList(): void
    {
        $html = '<ul><li>Item 1</li><li>Item 2<ul><li>Nested</li></ul></li></ul>';
        $djot = $this->converter->convert($html);

        $this->assertStringContainsString('- Item 1', $djot);
        $this->assertStringContainsString('- Item 2', $djot);
        $this->assertStringContainsString('- Nested', $djot);
    }
}
