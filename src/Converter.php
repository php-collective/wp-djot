<?php

declare(strict_types=1);

namespace WpDjot;

use Djot\DjotConverter;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Wrapper around the Djot converter with WordPress-specific features.
 */
class Converter
{
    private DjotConverter $converter;

    private DjotConverter $safeConverter;

    private bool $defaultSafeMode;

    public function __construct(bool $safeMode = true)
    {
        $this->defaultSafeMode = $safeMode;
        $this->converter = new DjotConverter(safeMode: false);
        $this->safeConverter = new DjotConverter(safeMode: true);
    }

    /**
     * Convert Djot markup to HTML.
     *
     * @param string $djot The Djot markup to convert.
     * @param bool|null $safeMode Override safe mode setting.
     */
    public function convert(string $djot, ?bool $safeMode = null): string
    {
        $useSafeMode = $safeMode ?? $this->defaultSafeMode;

        $djot = $this->preProcess($djot);

        $converter = $useSafeMode ? $this->safeConverter : $this->converter;
        $html = $converter->convert($djot);

        return $this->postProcess($html, $useSafeMode);
    }

    /**
     * Convert with safe mode enabled (for untrusted content).
     */
    public function convertSafe(string $djot): string
    {
        return $this->convert($djot, true);
    }

    /**
     * Convert without safe mode (for trusted content).
     */
    public function convertUnsafe(string $djot): string
    {
        return $this->convert($djot, false);
    }

    /**
     * Pre-process Djot content before conversion.
     */
    private function preProcess(string $djot): string
    {
        // Trim leading/trailing whitespace
        $djot = trim($djot);

        // Normalize line endings
        $djot = str_replace(["\r\n", "\r"], "\n", $djot);

        // WordPress sometimes adds extra paragraph tags - remove them
        $djot = preg_replace('/<p>\s*<\/p>/', '', $djot) ?? $djot;

        // Decode HTML entities that WordPress may have encoded
        $djot = html_entity_decode($djot, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        /**
         * Filter Djot content before conversion.
         *
         * @param string $djot The Djot markup.
         */
        if (function_exists('apply_filters')) {
            $djot = (string)apply_filters('wp_djot_pre_convert', $djot);
        }

        return $djot;
    }

    /**
     * Post-process HTML after conversion.
     */
    private function postProcess(string $html, bool $useSafeMode = false): string
    {
        // Apply HTML Purifier for extra sanitization in safe mode (if available)
        if ($useSafeMode && $html) {
            $html = $this->purifyHtml($html);
        }

        // Add djot-content wrapper class for styling
        if ($html) {
            $html = '<div class="djot-content">' . $html . '</div>';
        }

        /**
         * Filter HTML after Djot conversion.
         *
         * @param string $html The converted HTML.
         */
        if (function_exists('apply_filters')) {
            $html = (string)apply_filters('wp_djot_post_convert', $html);
        }

        return $html;
    }

    /**
     * Purify HTML using HTMLPurifier if available.
     *
     * Falls back to WordPress wp_kses_post() if HTMLPurifier is not installed.
     */
    private function purifyHtml(string $html): string
    {
        // Use HTMLPurifier if available (composer require ezyang/htmlpurifier)
        if (class_exists(HTMLPurifier::class)) {
            static $purifier = null;
            if ($purifier === null) {
                $config = HTMLPurifier_Config::createDefault();
                $config->set('Cache.DefinitionImpl', null);
                $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title],ul,ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th,td,img[src|alt|title],span[class],div[class],sup,sub,mark,ins,del,hr');
                $purifier = new HTMLPurifier($config);
            }

            return $purifier->purify($html);
        }

        // Fallback to WordPress sanitization
        if (function_exists('wp_kses_post')) {
            return wp_kses_post($html);
        }

        return $html;
    }

    /**
     * Check if a string contains Djot-specific syntax.
     */
    public function containsDjot(string $content): bool
    {
        // Check for Djot-specific patterns
        $patterns = [
            '/\{[a-z]+\}/', // Attributes like {.class}
            '/\^\[/', // Footnotes
            '/\$.*\$/', // Math
            '/:.*:/', // Symbols
            '/\{djot\}/', // Our shortcode
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
