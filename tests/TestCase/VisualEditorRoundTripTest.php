<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase;

use PHPUnit\Framework\TestCase;
use WpDjot\Converter;

class VisualEditorRoundTripTest extends TestCase
{
    public function testConvertExcerptCarriesFrontmatterForVisualEditor(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt("---\ntitle: Test\n---\n\nBody");

        $this->assertStringContainsString('data-djot-frontmatter', $html);
        $this->assertStringContainsString('data-djot-src="---&#10;title: Test&#10;---"', $html);
        $this->assertStringContainsString('<p>Body</p>', $html);
    }

    public function testConvertExcerptDoesNotApplyLocaleSmartQuotes(): void
    {
        $converter = new Converter(
            safeMode: false,
            smartQuotesLocale: 'de',
        );

        $html = $converter->convertExcerpt('"Hallo", helper\'s text');

        $this->assertStringContainsString('"Hallo"', $html);
        $this->assertStringContainsString("helper's", $html);
        $this->assertStringNotContainsString("\u{201E}", $html);
        $this->assertStringNotContainsString("\u{201C}", $html);
        $this->assertStringNotContainsString("\u{2019}", $html);
    }

    public function testConvertExcerptPreservesOriginalFenceInfoSpacing(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt("```php\necho 'ok';\n```");

        $this->assertStringContainsString('data-djot-src="```php&#10;echo &#039;ok&#039;;&#10;```"', $html);
        $this->assertStringNotContainsString('data-djot-src="``` php', $html);
    }
}
