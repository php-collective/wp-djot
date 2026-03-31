<?php

declare(strict_types=1);

namespace WpDjot\Extension;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\DjotConverter;
use Djot\Event\RenderEvent;
use Djot\Extension\ExtensionInterface;
use Djot\Node\Block\CodeBlock;
use Djot\Renderer\HtmlRenderer;
use Djot\Util\StringUtil;
use Torchlight\Engine\Engine;

/**
 * Torchlight syntax highlighting extension for djot-php.
 *
 * Uses Torchlight Engine (built on Phiki) to provide:
 * - Syntax highlighting with TextMate grammars
 * - Line numbers (with custom start)
 * - Line highlighting
 * - Diff annotations (++/--)
 * - Focus mode
 * - And more via [tl! ...] comment annotations
 *
 * @see https://github.com/torchlight-api/engine
 */
class TorchlightExtension implements ExtensionInterface
{
    private Engine $engine;

    private string $theme;

    private bool $showLineNumbers;

    private bool $roundTripMode = false;

    /**
     * @param string $theme Theme name (e.g., 'github-light', 'github-dark', 'synthwave-84')
     * @param bool $showLineNumbers Whether to show line numbers by default
     */
    public function __construct(
        string $theme = 'github-light',
        bool $showLineNumbers = false,
    ) {
        $this->theme = $theme;
        $this->showLineNumbers = $showLineNumbers;
        $this->engine = new Engine();

        // Register djot grammar from djot-grammars package for normal article rendering.
        $grammarPath = dirname(__DIR__, 2) . '/vendor/php-collective/djot-grammars/textmate/djot.tmLanguage.json';
        if (file_exists($grammarPath)) {
            $this->engine->getEnvironment()->getGrammarRepository()->register('djot', $grammarPath);
            $this->engine->getEnvironment()->getGrammarRepository()->alias('dj', 'djot');
        }
    }

    /**
     * Register the extension with the converter.
     */
    public function register(DjotConverter $converter): void
    {
        $renderer = $converter->getRenderer();
        $this->roundTripMode = $renderer instanceof HtmlRenderer && $renderer->isRoundTripMode();

        $converter->on('render.code_block', function (RenderEvent $event): void {
            $this->renderCodeBlock($event);
        });
    }

    /**
     * Render a code block using Torchlight Engine.
     */
    private function renderCodeBlock(RenderEvent $event): void
    {
        $block = $event->getNode();
        if (!$block instanceof CodeBlock) {
            return;
        }

        $rawLanguage = $block->getLanguage() ?: 'text';
        $code = $block->getContent();

        // Parse language string for options (e.g., "php #" or "php #=42" or "php [config.php]")
        $parsed = $this->parseLanguageOptions($rawLanguage);
        $language = $parsed['language'];
        $showLineNumbers = $parsed['lineNumbers'] || $this->showLineNumbers;
        $filename = $parsed['filename'];
        $djotSrc = $this->roundTripMode ? $this->reconstructCodeBlockSource($block, $rawLanguage) : null;

        // The visual editor and wp-admin previews depend on a plain <pre><code>
        // shape. Torchlight/Phiki markup is fine for frontend rendering but is
        // fragile in editor/admin parsing paths.
        if ($this->shouldRenderPlainCodeBlock($language)) {
            $this->renderPlainCodeBlock($event, $code, $language, $rawLanguage, $filename, $djotSrc);

            return;
        }

        // Some TextMate grammars still trip PCRE lookbehind limitations in Phiki.
        // Fall back to plain code rendering for these languages to keep the editor stable.
        if ($this->shouldUsePlainCodeFallback($language)) {
            $this->renderPlainCodeBlock($event, $code, $language, $rawLanguage, $filename, $djotSrc);

            return;
        }

        // Use inline torchlight options for custom start line
        // (API options are reset by Engine internally, but inline comments work)
        if ($parsed['startLine'] !== 1) {
            $options = ['lineNumbersStart' => $parsed['startLine']];
            $code = '// torchlight! ' . json_encode($options) . "\n" . $code;
        }

        try {
            $html = $this->engine->codeToHtml($code, $language, $this->theme, withGutter: $showLineNumbers);

            // Add data-language-raw attribute to preserve full language string for visual editor
            if ($rawLanguage !== $language) {
                $html = $this->addLanguageRawAttribute($html, $rawLanguage);
            }

            // Add data-filename attribute to the pre element if filename is specified
            if ($filename !== null) {
                $html = $this->addFilenameAttribute($html, $filename);
            }

            if ($djotSrc !== null) {
                $html = $this->addDjotSrcAttribute($html, $djotSrc);
            }

            $event->setHtml($html);
        } catch (\Throwable $e) {
            // Fallback to basic rendering on error
            $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            $langClass = $language ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';
            $filenameAttr = $filename !== null ? ' data-filename="' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '"' : '';
            $langRawAttr = $rawLanguage !== $language ? ' data-language-raw="' . htmlspecialchars($rawLanguage, ENT_QUOTES, 'UTF-8') . '"' : '';
            $djotSrcAttr = $djotSrc !== null ? ' data-djot-src="' . htmlspecialchars($djotSrc, ENT_QUOTES, 'UTF-8') . '"' : '';
            $event->setHtml('<pre' . $filenameAttr . $langRawAttr . $djotSrcAttr . '><code' . $langClass . '>' . $escapedCode . '</code></pre>' . "\n");
        }
    }

    /**
     * Add data-filename attribute to the pre element in HTML output.
     */
    private function addFilenameAttribute(string $html, string $filename): string
    {
        $escapedFilename = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');

        // Add data-filename to the opening <pre> tag
        return preg_replace(
            '/^<pre\b/',
            '<pre data-filename="' . $escapedFilename . '"',
            $html,
        ) ?? $html;
    }

    /**
     * Add data-language-raw attribute to preserve full language string for visual editor.
     */
    private function addLanguageRawAttribute(string $html, string $rawLanguage): string
    {
        $escapedLang = htmlspecialchars($rawLanguage, ENT_QUOTES, 'UTF-8');

        // Add data-language-raw to the opening <pre> tag
        return preg_replace(
            '/^<pre\b/',
            '<pre data-language-raw="' . $escapedLang . '"',
            $html,
        ) ?? $html;
    }

    private function shouldUsePlainCodeFallback(string $language): bool
    {
        $language = strtolower($language);

        return in_array($language, ['markdown', 'md', 'djot', 'dj'], true);
    }

    private function shouldRenderPlainCodeBlock(string $language): bool
    {
        if ($this->roundTripMode) {
            return true;
        }

        if (defined('WP_ADMIN') && WP_ADMIN) {
            return true;
        }

        return $this->shouldUsePlainCodeFallback($language);
    }

    private function renderPlainCodeBlock(
        RenderEvent $event,
        string $code,
        string $language,
        string $rawLanguage,
        ?string $filename,
        ?string $djotSrc,
    ): void {
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $langClass = $language !== '' ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';
        $filenameAttr = $filename !== null ? ' data-filename="' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '"' : '';
        $langRawAttr = $rawLanguage !== $language ? ' data-language-raw="' . htmlspecialchars($rawLanguage, ENT_QUOTES, 'UTF-8') . '"' : '';
        $djotSrcAttr = $djotSrc !== null ? ' data-djot-src="' . htmlspecialchars($djotSrc, ENT_QUOTES, 'UTF-8') . '"' : '';

        $event->setHtml('<pre' . $filenameAttr . $langRawAttr . $djotSrcAttr . '><code' . $langClass . '>' . $escapedCode . '</code></pre>' . "\n");
    }

    /**
     * Add data-djot-src attribute to the pre element in HTML output.
     */
    private function addDjotSrcAttribute(string $html, string $djotSrc): string
    {
        $escapedSrc = htmlspecialchars($djotSrc, ENT_QUOTES, 'UTF-8');

        return preg_replace(
            '/^<pre\b/',
            '<pre data-djot-src="' . $escapedSrc . '"',
            $html,
        ) ?? $html;
    }

    /**
     * Reconstruct the original Djot source for round-trip-safe code blocks.
     */
    private function reconstructCodeBlockSource(CodeBlock $block, string $rawLanguage): string
    {
        $content = $block->getContent();
        $fence = StringUtil::findSafeCodeFence($content, 3);
        $djot = $fence;

        if ($rawLanguage !== '') {
            $djot .= ' ' . $rawLanguage;
        }

        $djot .= "\n" . $content;

        if (!str_ends_with($content, "\n")) {
            $djot .= "\n";
        }

        return $djot . $fence . "\n";
    }

    /**
     * Parse language string to extract language and options.
     *
     * Supports:
     * - "php" -> language only
     * - "php #" -> language with line numbers
     * - "php #=5" -> language with line numbers starting at 5
     * - "php [config.php]" -> language with filename
     * - "php # [config.php]" -> language with line numbers and filename
     *
     * Note: Line highlighting uses Torchlight's inline annotations:
     * - // [tl! highlight] - highlight this line
     * - // [tl! focus] - focus this line
     * - // [tl! ++] - diff add
     * - // [tl! --] - diff remove
     *
     * @return array{language: string, lineNumbers: bool, startLine: int, filename: string|null}
     */
    private function parseLanguageOptions(string $raw): array
    {
        $language = trim($raw);
        $lineNumbers = false;
        $startLine = 1;
        $filename = null;

        // Check for filename syntax: [filename] at the end
        if (preg_match('/^(.+?)\s*\[([^\]]+)\]\s*$/', $language, $matches)) {
            $language = trim($matches[1]);
            $filename = $matches[2];
        }

        // Check for line numbers syntax: # or #=N
        if (preg_match('/^(\S+)\s+#(?:=(\d+))?/', $language, $matches)) {
            $language = $matches[1];
            $lineNumbers = true;
            if (isset($matches[2])) {
                $startLine = (int) $matches[2];
            }
        }

        return [
            'language' => $language,
            'lineNumbers' => $lineNumbers,
            'startLine' => $startLine,
            'filename' => $filename,
        ];
    }
}
