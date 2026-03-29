<?php

declare(strict_types=1);

namespace WpDjot\Converter;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
            'dl' => $this->processDefinitionList($node),
            'dt' => $this->processDefinitionTerm($node),
            'dd' => $this->processDefinitionDescription($node),
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

    /**
     * Process <dl> definition list.
     */
    protected function processDefinitionList(DOMElement $node): string
    {
        $result = '';
        $afterDescription = false;

        foreach ($node->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            $tagName = strtolower($child->tagName);

            if ($tagName === 'dt') {
                // Add blank line before term if we just finished a description
                if ($afterDescription) {
                    $result .= "\n";
                }
                $result .= $this->processDefinitionTerm($child);
                $afterDescription = false;
            } elseif ($tagName === 'dd') {
                $result .= $this->processDefinitionDescription($child);
                $afterDescription = true;
            }
        }

        return $result;
    }

    /**
     * Process <dt> definition term.
     */
    protected function processDefinitionTerm(DOMElement $node): string
    {
        $content = trim($this->processChildren($node));
        if ($content === '') {
            return '';
        }

        return ': ' . $content . "\n";
    }

    /**
     * Process <dd> definition description.
     */
    protected function processDefinitionDescription(DOMElement $node): string
    {
        $result = "\n";

        foreach ($node->childNodes as $child) {
            $content = $this->processNode($child);
            if (trim($content) === '') {
                continue;
            }

            // Indent each line with two spaces
            $lines = explode("\n", trim($content));
            foreach ($lines as $line) {
                if ($line !== '') {
                    $result .= '  ' . $line . "\n";
                }
            }
        }

        return $result;
    }
}
