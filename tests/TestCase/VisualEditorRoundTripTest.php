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
        $this->assertStringContainsString('>Body</p>', $html);
    }

    public function testConvertExcerptDoesNotApplyLocaleSmartQuotes(): void
    {
        $converter = new Converter(
            safeMode: false,
            smartQuotesLocale: 'de',
        );

        $html = $converter->convertExcerpt('"Hallo", helper\'s text and an intentional ’');

        $this->assertStringContainsString('"Hallo"', $html);
        $this->assertStringContainsString("helper's", $html);
        $this->assertStringContainsString('intentional ’', $html);
        $this->assertStringNotContainsString("\u{201E}", $html);
        $this->assertStringNotContainsString("\u{201C}", $html);
    }

    public function testConvertExcerptPreservesOriginalFenceInfoSpacing(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt("```php\necho 'ok';\n```");

        $this->assertStringContainsString('data-djot-src="```php&#10;echo &#039;ok&#039;;&#10;```"', $html);
        $this->assertStringNotContainsString('data-djot-src="``` php', $html);
    }

    public function testConvertExcerptRestoresNestedFenceSourcesInOrder(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt("> ```php\n> echo 'quoted';\n> ```\n\n```js\nconsole.log('top');\n```");

        $this->assertStringContainsString('data-djot-src="```php&#10;echo &#039;quoted&#039;;&#10;```"', $html);
        $this->assertStringContainsString('data-djot-src="```js&#10;console.log(&#039;top&#039;);&#10;```"', $html);
        $this->assertStringNotContainsString('data-djot-src="``` php', $html);
        $this->assertStringNotContainsString('data-djot-src="``` js', $html);
    }

    public function testConvertExcerptCarriesUnchangedParagraphAndHeadingSource(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt(<<<'DJOT'
[`cakephp-menu`](https://github.com/dereuromark/cakephp-menu)

**<https://sandbox.dereuromark.de/menu-sandbox>**

## A small detour: keeping the tree honest {#tree-integrity}

*Explicit `detach()`.*
DJOT);

        $this->assertStringContainsString('data-djot-src="[`cakephp-menu`](https://github.com/dereuromark/cakephp-menu)"', $html);
        $this->assertStringContainsString('data-djot-plain="cakephp-menu"', $html);
        $this->assertStringContainsString('data-djot-src="**&lt;https://sandbox.dereuromark.de/menu-sandbox&gt;**"', $html);
        $this->assertStringContainsString('data-djot-src="## A small detour: keeping the tree honest {#tree-integrity}"', $html);
        $this->assertStringContainsString('data-djot-src="*Explicit `detach()`.*"', $html);
    }

    public function testConvertExcerptPreservesCodeGroupSource(): void
    {
        $converter = new Converter(safeMode: false);

        $html = $converter->convertExcerpt(<<<'DJOT'
::: code-group

```php [From an array]
echo 1;
```

:::
DJOT);

        $this->assertStringContainsString('data-djot-src="::: code-group&#10;&#10;```php [From an array]&#10;echo 1;&#10;```&#10;&#10;:::"', $html);
        $this->assertStringNotContainsString('data-djot-src="::: code-group&#10;``` php', $html);
    }
}
