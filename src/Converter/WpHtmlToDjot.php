<?php

declare(strict_types=1);

namespace WpDjot\Converter;

use Djot\Converter\HtmlToDjot;
use DOMElement;
use DOMNode;

/**
 * Extended HTML to Djot converter with WP-Djot semantic element support.
 *
 * Converts semantic HTML elements to Djot span syntax:
 * - <kbd>text</kbd> → [text]{kbd}
 * - <abbr title="...">text</abbr> → [text]{abbr="..."}
 * - <dfn>text</dfn> → [text]{dfn}
 * - <dfn title="...">text</dfn> → [text]{dfn="..."}
 */
class WpHtmlToDjot extends HtmlToDjot
{
    protected function processNode(DOMNode $node): string
    {
        if (!($node instanceof DOMElement)) {
            return parent::processNode($node);
        }

        $tagName = strtolower($node->tagName);

        return match ($tagName) {
            'kbd' => $this->processSemanticElement($node, 'kbd'),
            'abbr' => $this->processAbbr($node),
            'dfn' => $this->processDfn($node),
            default => parent::processNode($node),
        };
    }

    /**
     * Process simple semantic elements like <kbd>, <samp>, <var>.
     */
    protected function processSemanticElement(DOMElement $node, string $attr): string
    {
        $content = trim($this->processChildren($node));
        if ($content === '') {
            return '';
        }

        return '[' . $content . ']{' . $attr . '}';
    }

    /**
     * Process <abbr> with title attribute.
     */
    protected function processAbbr(DOMElement $node): string
    {
        $content = trim($this->processChildren($node));
        if ($content === '') {
            return '';
        }

        $title = $node->getAttribute('title');
        if ($title !== '') {
            return '[' . $content . ']{abbr="' . $this->escapeAttrValue($title) . '"}';
        }

        // abbr without title - just return as plain text
        return $content;
    }

    /**
     * Process <dfn> with optional title attribute.
     */
    protected function processDfn(DOMElement $node): string
    {
        $content = trim($this->processChildren($node));
        if ($content === '') {
            return '';
        }

        $title = $node->getAttribute('title');
        if ($title !== '') {
            return '[' . $content . ']{dfn="' . $this->escapeAttrValue($title) . '"}';
        }

        return '[' . $content . ']{dfn}';
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
