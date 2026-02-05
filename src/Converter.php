<?php

declare(strict_types=1);

namespace WpDjot;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\DjotConverter;
use Djot\Extension\HeadingPermalinksExtension;
use Djot\Extension\SmartQuotesExtension;
use Djot\Extension\TableOfContentsExtension;
use Djot\Profile;
use Djot\Renderer\SoftBreakMode;
use HTMLPurifier;
use HTMLPurifier_Config;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wpdjot_ is our plugin prefix

/**
 * Wrapper around the Djot converter with WordPress-specific features.
 */
class Converter
{
    private DjotConverter $converter;

    private DjotConverter $safeConverter;

    private bool $defaultSafeMode;

    private string $postProfile;

    private string $commentProfile;

    private string $postSoftBreak;

    private string $commentSoftBreak;

    private bool $markdownMode;

    private bool $tocEnabled;

    private string $tocPosition;

    private int $tocMinLevel;

    private int $tocMaxLevel;

    private string $tocListType;

    private bool $permalinksEnabled;

    private string $smartQuotesLocale;

    /**
     * @var array<string, \Djot\DjotConverter>
     */
    private array $profileConverters = [];

    public function __construct(
        bool $safeMode = true,
        string $postProfile = 'article',
        string $commentProfile = 'comment',
        string $postSoftBreak = 'newline',
        string $commentSoftBreak = 'newline',
        bool $markdownMode = false,
        bool $tocEnabled = false,
        string $tocPosition = 'top',
        int $tocMinLevel = 2,
        int $tocMaxLevel = 4,
        string $tocListType = 'ul',
        bool $permalinksEnabled = false,
        string $smartQuotesLocale = 'en',
    ) {
        $this->defaultSafeMode = $safeMode;
        $this->postProfile = $postProfile;
        $this->commentProfile = $commentProfile;
        $this->postSoftBreak = $postSoftBreak;
        $this->commentSoftBreak = $commentSoftBreak;
        $this->markdownMode = $markdownMode;
        $this->tocEnabled = $tocEnabled;
        $this->tocPosition = $tocPosition;
        $this->tocMinLevel = $tocMinLevel;
        $this->tocMaxLevel = $tocMaxLevel;
        $this->tocListType = $tocListType;
        $this->permalinksEnabled = $permalinksEnabled;
        $this->smartQuotesLocale = $smartQuotesLocale;
        $this->converter = new DjotConverter(safeMode: false);
        $this->converter->getRenderer()->setCodeBlockTabWidth(4);
        $this->safeConverter = new DjotConverter(safeMode: true);
        $this->safeConverter->getRenderer()->setCodeBlockTabWidth(4);
    }

    /**
     * Create a Converter instance from WordPress settings.
     *
     * This is the preferred way to create a Converter to ensure all settings are applied.
     */
    public static function fromSettings(): self
    {
        $options = get_option('wpdjot_settings', []);

        return new self(
            safeMode: !empty($options['safe_mode']),
            postProfile: $options['post_profile'] ?? 'article',
            commentProfile: $options['comment_profile'] ?? 'comment',
            postSoftBreak: $options['post_soft_break'] ?? 'newline',
            commentSoftBreak: $options['comment_soft_break'] ?? 'newline',
            markdownMode: !empty($options['markdown_mode']),
            tocEnabled: !empty($options['toc_enabled']),
            tocPosition: $options['toc_position'] ?? 'top',
            tocMinLevel: (int)($options['toc_min_level'] ?? 2),
            tocMaxLevel: (int)($options['toc_max_level'] ?? 4),
            tocListType: $options['toc_list_type'] ?? 'ul',
            permalinksEnabled: !empty($options['permalinks_enabled']),
            smartQuotesLocale: $options['smart_quotes_locale'] ?? 'en',
        );
    }

    /**
     * Get or create a converter for the specified profile.
     *
     * @param string $profileName
     * @param bool $safeMode
     * @param string $context Context name for filters: 'article' or 'comment'
     */
    private function getProfileConverter(string $profileName, bool $safeMode, string $context = 'article'): DjotConverter
    {
        $softBreakSetting = $context === 'comment' ? $this->commentSoftBreak : $this->postSoftBreak;
        $tocKey = ($this->tocEnabled && $context === 'article')
            ? '_toc_' . $this->tocPosition . '_' . $this->tocMinLevel . '_' . $this->tocMaxLevel . '_' . $this->tocListType
            : '';
        $permalinksKey = ($this->permalinksEnabled && $context === 'article') ? '_permalinks' : '';
        $smartQuotesKey = $this->smartQuotesLocale !== 'en' ? '_sq_' . $this->smartQuotesLocale : '';
        $key = $profileName . ($safeMode ? '_safe' : '_unsafe') . '_' . $softBreakSetting . ($this->markdownMode ? '_md' : '') . $tocKey . $permalinksKey . $smartQuotesKey;

        if (!isset($this->profileConverters[$key])) {
            // 'none' means no profile restrictions at all
            $profile = match ($profileName) {
                'none' => null,
                'full' => Profile::full(),
                'article' => Profile::article(),
                'comment' => Profile::comment(),
                'minimal' => Profile::minimal(),
                default => Profile::article(),
            };

            // Use significantNewlines mode for markdown compatibility
            if ($this->markdownMode) {
                $converter = DjotConverter::withSignificantNewlines(safeMode: $safeMode, profile: $profile);
            } else {
                $converter = new DjotConverter(safeMode: $safeMode, profile: $profile);

                // Apply soft break mode (only when not in markdown mode, which handles it automatically)
                $softBreakMode = match ($softBreakSetting) {
                    'space' => SoftBreakMode::Space,
                    'br' => SoftBreakMode::Break,
                    default => SoftBreakMode::Newline,
                };
                $converter->getRenderer()->setSoftBreakMode($softBreakMode);
            }

            // Convert tabs to 4 spaces in code blocks for consistent display
            $converter->getRenderer()->setCodeBlockTabWidth(4);

            // Add Table of Contents extension for articles when enabled
            if ($this->tocEnabled && $context === 'article') {
                $tocExtension = new TableOfContentsExtension(
                    minLevel: $this->tocMinLevel,
                    maxLevel: $this->tocMaxLevel,
                    listType: $this->tocListType,
                    cssClass: 'wpdjot-toc',
                    position: $this->tocPosition,
                );
                $converter->addExtension($tocExtension);

                // Wrap TOC in collapsible <details>/<summary>
                $converter->addOutputTransformer(function (string $html): string {
                    $label = __('Table of Contents', 'djot-markup');

                    return (string)preg_replace(
                        '#<nav class="wpdjot-toc">\n(.*?)</nav>\n#s',
                        '<details class="wpdjot-toc">' . "\n"
                            . '<summary>' . esc_html($label) . '</summary>' . "\n"
                            . '$1</details>' . "\n",
                        $html,
                    );
                });
            }

            // Add heading permalinks for articles when enabled
            if ($this->permalinksEnabled && $context === 'article') {
                $converter->addExtension(new HeadingPermalinksExtension(
                    symbol: '#',
                    cssClass: 'wpdjot-permalink',
                ));
            }

            // Add smart quotes extension for non-English locales
            if ($this->smartQuotesLocale !== 'en') {
                $locale = $this->smartQuotesLocale === 'auto' ? $this->getWpLocale() : $this->smartQuotesLocale;
                $converter->addExtension(new SmartQuotesExtension(locale: $locale));
            }

            // Allow customization via WordPress filters
            if (function_exists('apply_filters')) {
                /** @var \Djot\DjotConverter $converter */
                $converter = apply_filters('wpdjot_converter', $converter, $context);

                // Post-type specific filter
                $postType = function_exists('get_post_type') ? get_post_type() : null;
                if ($postType) {
                    /** @var \Djot\DjotConverter $converter */
                    $converter = apply_filters("wpdjot_converter_{$postType}", $converter, $context);
                }
            }

            $this->profileConverters[$key] = $converter;
        }

        return $this->profileConverters[$key];
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
     * Convert for articles/blog posts using configured profile.
     *
     * Posts are processed before WordPress filters (wptexturize, wpautop)
     * so we receive raw content without HTML artifacts.
     */
    public function convertArticle(string $djot): string
    {
        $djot = $this->preProcess($djot, true);
        $converter = $this->getProfileConverter($this->postProfile, false, 'article');
        $html = $converter->convert($djot);

        return $this->postProcess($html, false);
    }

    /**
     * Convert for comments using configured profile (always with safe mode).
     *
     * Comments are processed before WordPress filters (wptexturize, wpautop)
     * so we receive raw content without HTML artifacts.
     */
    public function convertComment(string $djot): string
    {
        $djot = $this->preProcess($djot, true);
        $converter = $this->getProfileConverter($this->commentProfile, true, 'comment');
        $html = $converter->convert($djot);

        return $this->postProcess($html, true);
    }

    /**
     * Pre-process Djot content before conversion.
     *
     * @param string $djot
     * @param bool $isRaw True if content is raw (before WordPress filters), false if already processed by wpautop/wptexturize
     */
    private function preProcess(string $djot, bool $isRaw = false): string
    {
        // Trim leading/trailing whitespace
        $djot = trim($djot);

        // Normalize line endings
        $djot = str_replace(["\r\n", "\r"], "\n", $djot);

        // Only clean up WordPress HTML artifacts if content was already processed
        if (!$isRaw) {
            // Remove <br> tags that WordPress wpautop() may have added
            // This is critical for fenced code blocks - <br> before ``` breaks recognition
            $djot = preg_replace('/<br\s*\/?>\n?/i', "\n", $djot) ?? $djot;

            // Remove <p>...</p> wrapper tags that wpautop() adds (preserve content)
            $djot = preg_replace('/<p>(.*?)<\/p>/s', "$1\n\n", $djot) ?? $djot;

            // WordPress sometimes adds empty paragraph tags - remove them
            $djot = preg_replace('/<p>\s*<\/p>/', '', $djot) ?? $djot;

            // Decode HTML entities that WordPress may have encoded
            $djot = html_entity_decode($djot, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Ensure blank line before code fences (required by Djot for block recognition)
            // Without a blank line, ``` is treated as inline code, not a code block
            $djot = preg_replace('/([^\n])\n(```)/m', "$1\n\n$2", $djot) ?? $djot;
        }

        /**
         * Filter Djot content before conversion.
         *
         * @param string $djot The Djot markup.
         */
        if (function_exists('apply_filters')) {
            $djot = (string)apply_filters('wpdjot_pre_convert', $djot);
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
            $html = (string)apply_filters('wpdjot_post_convert', $html);
        }

        return $html;
    }

    /**
     * Purify HTML using HTMLPurifier if available.
     *
     * Falls back to WordPress wp_kses_post() if HTMLPurifier is not installed.
     *
     * Customization via filter:
     * add_filter('wpdjot_htmlpurifier_config', function($config) {
     *     $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title|rel],...');
     *     return $config;
     * });
     */
    private function purifyHtml(string $html): string
    {
        // Use HTMLPurifier if available (composer require ezyang/htmlpurifier)
        if (class_exists(HTMLPurifier::class)) {
            static $purifier = null;
            if ($purifier === null) {
                $config = HTMLPurifier_Config::createDefault();
                $config->set('Cache.DefinitionImpl', null);
                $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title|rel],ul[class],ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,table,caption,thead,tbody,tr,th,td,img[src|alt|title],span[class],div[class],sup,sub,mark,ins,del,hr,input[type|checked|disabled],figure,figcaption');

                /**
                 * Filter HTMLPurifier configuration.
                 *
                 * @param \HTMLPurifier_Config $config The HTMLPurifier configuration object.
                 */
                if (function_exists('apply_filters')) {
                    $config = apply_filters('wpdjot_htmlpurifier_config', $config);
                }

                $purifier = new HTMLPurifier($config);
            }

            return $purifier->purify($html);
        }

        // Fallback to WordPress sanitization
        if (function_exists('wp_kses')) {
            return wp_kses($html, self::getAllowedHtml());
        }

        return $html;
    }

    /**
     * Get the allowed HTML tags and attributes for wp_kses sanitization.
     *
     * This is used by both the Converter and block rendering to ensure consistency.
     *
     * @return array<string, array<string, bool>>
     */
    public static function getAllowedHtml(): array
    {
        $allowedHtml = function_exists('wp_kses_allowed_html')
            ? wp_kses_allowed_html('post')
            : [];

        // Djot-specific elements not in WordPress default allowlist
        $allowedHtml = array_merge($allowedHtml, [
            'input' => [
                'type' => true,
                'checked' => true,
                'disabled' => true,
                'class' => true,
            ],
            'ul' => [
                'class' => true,
            ],
            'figure' => [
                'class' => true,
            ],
            'figcaption' => [
                'class' => true,
            ],
        ]);

        /**
         * Filter allowed HTML tags for wp_kses sanitization.
         *
         * @param array<string, array<string, bool>> $allowedHtml Allowed HTML tags and attributes.
         */
        if (function_exists('apply_filters')) {
            $allowedHtml = apply_filters('wpdjot_allowed_html', $allowedHtml);
        }

        return $allowedHtml;
    }

    /**
     * Get the WordPress locale, falling back to 'en'.
     */
    private function getWpLocale(): string
    {
        if (function_exists('get_locale')) {
            return get_locale() ?: 'en';
        }

        return 'en';
    }
}
