<?php

declare(strict_types=1);

namespace WpDjot\Converter;

use Djot\Converter\MarkdownToDjot;

/**
 * Extended Markdown to Djot converter with WP-Djot semantic element support.
 *
 * Converts inline HTML semantic elements to Djot span syntax:
 * - <kbd>text</kbd> → [text]{kbd}
 * - <abbr title="...">text</abbr> → [text]{abbr="..."}
 * - <dfn>text</dfn> → [text]{dfn}
 * - <dfn title="...">text</dfn> → [text]{dfn="..."}
 */
class WpMarkdownToDjot extends MarkdownToDjot
{
    public function convert(string $markdown): string
    {
        $djot = parent::convert($markdown);

        // Convert inline HTML semantic elements to Djot span syntax
        $djot = $this->convertSemanticElements($djot);

        return $djot;
    }

    /**
     * Convert inline HTML semantic elements to Djot span syntax.
     */
    protected function convertSemanticElements(string $djot): string
    {
        // Convert <kbd>text</kbd> → [text]{kbd}
        $djot = preg_replace_callback(
            '/<kbd>([^<]+)<\/kbd>/i',
            fn($m) => '[' . $m[1] . ']{kbd}',
            $djot,
        ) ?? $djot;

        // Convert <abbr title="...">text</abbr> → [text]{abbr="..."}
        $djot = preg_replace_callback(
            '/<abbr\s+title=["\']([^"\']+)["\']\s*>([^<]+)<\/abbr>/i',
            fn($m) => '[' . $m[2] . ']{abbr="' . $this->escapeAttrValue($m[1]) . '"}',
            $djot,
        ) ?? $djot;

        // Convert <abbr>text</abbr> (without title) → just text
        $djot = preg_replace_callback(
            '/<abbr>([^<]+)<\/abbr>/i',
            fn($m) => $m[1],
            $djot,
        ) ?? $djot;

        // Convert <dfn title="...">text</dfn> → [text]{dfn="..."}
        $djot = preg_replace_callback(
            '/<dfn\s+title=["\']([^"\']+)["\']\s*>([^<]+)<\/dfn>/i',
            fn($m) => '[' . $m[2] . ']{dfn="' . $this->escapeAttrValue($m[1]) . '"}',
            $djot,
        ) ?? $djot;

        // Convert <dfn>text</dfn> → [text]{dfn}
        $djot = preg_replace_callback(
            '/<dfn>([^<]+)<\/dfn>/i',
            fn($m) => '[' . $m[1] . ']{dfn}',
            $djot,
        ) ?? $djot;

        return $djot;
    }

    /**
     * Escape attribute value for Djot.
     */
    protected function escapeAttrValue(string $value): string
    {
        // Escape quotes and backslashes
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
