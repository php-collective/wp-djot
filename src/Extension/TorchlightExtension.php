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
use Torchlight\Engine\Engine;
use Torchlight\Engine\Options;

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
    }

    /**
     * Register the extension with the converter.
     */
    public function register(DjotConverter $converter): void
    {
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

        // Parse language string for options (e.g., "php #" or "php #=42 {1,3-5}")
        $parsed = $this->parseLanguageOptions($rawLanguage);
        $language = $parsed['language'];
        $showLineNumbers = $parsed['lineNumbers'] || $this->showLineNumbers;

        // Configure Torchlight options for line numbers
        if ($showLineNumbers || $parsed['startLine'] !== 1) {
            $options = new Options(
                lineNumbersEnabled: $showLineNumbers,
                lineNumbersStart: $parsed['startLine'],
            );
            $this->engine->setTorchlightOptions($options);
        }

        try {
            $html = $this->engine->codeToHtml($code, $language, $this->theme, withGutter: $showLineNumbers);
            $event->setHtml($html);
        } catch (\Throwable $e) {
            // Fallback to basic rendering on error
            $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            $langClass = $language ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';
            $event->setHtml('<pre><code' . $langClass . '>' . $escapedCode . '</code></pre>' . "\n");
        }
    }

    /**
     * Parse language string to extract language and options.
     *
     * Supports:
     * - "php" -> language only
     * - "php #" -> language with line numbers
     * - "php #=5" -> language with line numbers starting at 5
     *
     * Note: Line highlighting uses Torchlight's inline annotations:
     * - // [tl! highlight] - highlight this line
     * - // [tl! focus] - focus this line
     * - // [tl! ++] - diff add
     * - // [tl! --] - diff remove
     *
     * @return array{language: string, lineNumbers: bool, startLine: int}
     */
    private function parseLanguageOptions(string $raw): array
    {
        $language = trim($raw);
        $lineNumbers = false;
        $startLine = 1;

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
        ];
    }
}
