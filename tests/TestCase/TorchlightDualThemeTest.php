<?php

declare(strict_types=1);

namespace WpDjot\Test;

use PHPUnit\Framework\TestCase;
use WpDjot\Converter;

class TorchlightDualThemeTest extends TestCase
{
    public function testDualThemeEmitsWellFormedDarkVariables(): void
    {
        $converter = new Converter(torchlightTheme: 'github-light', torchlightDarkTheme: 'github-dark');

        $html = $converter->convertArticle("``` php\n\$x = 1;\n```");

        $this->assertStringContainsString('phiki-themes', $html);
        $this->assertStringContainsString('--phiki-dark-color', $html);
        // Well-formed style attributes: no glued declarations, no doubled
        // semicolons (wp_kses drops mangled attribute runs wholesale).
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}--/', $html);
        $this->assertStringNotContainsString(';;', $html);
    }

    public function testSingleThemeStaysSingle(): void
    {
        $converter = new Converter(torchlightTheme: 'github-light');

        $html = $converter->convertArticle("``` php\n\$x = 1;\n```");

        $this->assertStringNotContainsString('phiki-themes', $html);
        $this->assertStringNotContainsString('--phiki-dark-color', $html);
    }
}
