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

        $language = $block->getLanguage() ?: 'text';
        $code = $block->getContent();
        $attributes = $block->getAttributes();

        // Check for line numbers attribute (from djot {#} syntax or data attribute)
        $showLineNumbers = $this->showLineNumbers;
        $startLine = 1;

        // Parse djot code block options (e.g., "php #" for line numbers, "php #=42" for start at 42)
        if (isset($attributes['lineNumbers'])) {
            $showLineNumbers = true;
            if (is_numeric($attributes['lineNumbers'])) {
                $startLine = (int) $attributes['lineNumbers'];
            }
        }

        try {
            $html = $this->engine->codeToHtml($code, $language, $this->theme);

            // Wrap with appropriate classes
            $classes = ['torchlight'];
            if ($showLineNumbers) {
                $classes[] = 'has-line-numbers';
            }

            // Extract the inner content from Torchlight's output and wrap with our classes
            $event->setHtml($html);
        } catch (\Throwable $e) {
            // Fallback to basic rendering on error
            $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            $langClass = $language ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';
            $event->setHtml('<pre><code' . $langClass . '>' . $escapedCode . '</code></pre>' . "\n");
        }
    }
}
