<?php

declare(strict_types=1);

namespace WpDjot;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Djot\DjotConverter;
use Djot\Exception\ParseException;
use Djot\Exception\ParseWarning;
use Djot\Extension\AsciiHeadingIdsExtension;
use Djot\Extension\CodeGroupExtension;
use Djot\Extension\FrontmatterExtension;
use Djot\Extension\HeadingLevelShiftExtension;
use Djot\Extension\HeadingPermalinksExtension;
use Djot\Extension\HeadingReferenceExtension;
use Djot\Extension\MermaidExtension;
use Djot\Extension\SemanticSpanExtension;
use Djot\Extension\SmartQuotesExtension;
use Djot\Extension\TableOfContentsExtension;
use Djot\Extension\TabsExtension;
use Djot\Profile;
use Djot\Renderer\SoftBreakMode;
use HTMLPurifier;
use HTMLPurifier_Config;
use WpDjot\Extension\TorchlightExtension;

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

    private int $headingShift;

    private bool $mermaidEnabled;

    private string $torchlightTheme;

    private string $torchlightDarkTheme;

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
        int $headingShift = 0,
        bool $mermaidEnabled = false,
        string $torchlightTheme = 'github-light',
        string $torchlightDarkTheme = '',
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
        $this->headingShift = $headingShift;
        $this->mermaidEnabled = $mermaidEnabled;
        $this->torchlightTheme = $torchlightTheme;
        $this->torchlightDarkTheme = $torchlightDarkTheme;
        $this->converter = new DjotConverter(safeMode: false);
        $this->converter->getHtmlRenderer()->setCodeBlockTabWidth(4);
        $this->safeConverter = new DjotConverter(safeMode: true);
        $this->safeConverter->getHtmlRenderer()->setCodeBlockTabWidth(4);
    }

    /**
     * Create a Converter instance from WordPress settings.
     *
     * This is the preferred way to create a Converter to ensure all settings are applied.
     */
    public static function fromSettings(): self
    {
        $options = get_option('wpdjot_settings', []);

        // Safe mode defaults to ON: a fresh install has no stored option array
        // yet, and the absence of the key must not read as "disabled" (the
        // settings sanitizer always stores an explicit boolean once saved).
        // Site-level hard opt-out: when unfiltered HTML is disabled site-wide,
        // raw-HTML passthrough must never be possible regardless of the plugin
        // setting (mirrors how core strips the unfiltered_html capability).
        $safeMode = (bool)($options['safe_mode'] ?? true)
            || (defined('DISALLOW_UNFILTERED_HTML') && DISALLOW_UNFILTERED_HTML);

        return new self(
            safeMode: $safeMode,
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
            smartQuotesLocale: $options['smart_quotes_locale'] ?? 'auto',
            headingShift: (int)($options['heading_shift'] ?? 0),
            mermaidEnabled: !empty($options['mermaid_enabled']),
            torchlightTheme: (string)($options['torchlight_theme'] ?? 'github-light'),
            torchlightDarkTheme: (string)($options['torchlight_dark_theme'] ?? ''),
        );
    }

    /**
     * Get or create a converter for the specified profile.
     *
     * @param string $profileName
     * @param bool $safeMode
     * @param string $context Context name for filters: 'article', 'comment', or 'excerpt'
     * @param bool $roundTripMode Enable round-trip mode for visual editor (adds data-djot-* attributes)
     */
    private function getProfileConverter(string $profileName, bool $safeMode, string $context = 'article', bool $roundTripMode = false): DjotConverter
    {
        $softBreakSetting = $context === 'comment' ? $this->commentSoftBreak : $this->postSoftBreak;
        $tocKey = ($this->tocEnabled && $context === 'article')
            ? '_toc_' . $this->tocPosition . '_' . $this->tocMinLevel . '_' . $this->tocMaxLevel . '_' . $this->tocListType
            : '';
        $permalinksKey = ($this->permalinksEnabled && $context === 'article') ? '_permalinks' : '';
        // Resolve 'auto' to the active site locale up front so the cache key reflects
        // the locale actually used. Otherwise a switch_to_locale() between conversions
        // in one request would reuse the first locale's cached converter.
        $smartQuotesLocale = $this->smartQuotesLocale === 'auto' ? $this->getWpLocale() : $this->smartQuotesLocale;
        $smartQuotesKey = (!$roundTripMode && $smartQuotesLocale !== 'en') ? '_sq_' . $smartQuotesLocale : '';
        $headingShiftKey = $this->headingShift > 0 ? '_hs' . $this->headingShift : '';
        $mermaidKey = $this->mermaidEnabled ? '_mermaid' : '';
        $roundTripKey = $roundTripMode ? '_rt' : '';
        $key = $profileName . ($safeMode ? '_safe' : '_unsafe') . '_' . $softBreakSetting . ($this->markdownMode ? '_md' : '') . $tocKey . $permalinksKey . $smartQuotesKey . $headingShiftKey . $mermaidKey . $roundTripKey;

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

            // Strip disallowed / unknown nodes instead of converting them to text.
            // Needed for extension-provided node types (e.g. FrontmatterExtension) which
            // aren't in NodeType::allBlockTypes() and would otherwise be turned into a
            // paragraph containing their raw source. Also means a raw HTML block denied
            // by the profile disappears entirely instead of rendering as escaped text.
            $profile?->onDisallowed(Profile::ACTION_STRIP);

            // Determine soft break mode
            $softBreakMode = match ($softBreakSetting) {
                'space' => SoftBreakMode::Space,
                'br' => SoftBreakMode::Break,
                default => SoftBreakMode::Newline,
            };

            // Create converter with appropriate settings
            // blocksInterruptParagraphs + nestedListsWithoutBlankLine = markdown
            //   compatibility; the granular successors to djot's deprecated
            //   significantNewlines (which was the union of these two levers).
            // softBreakMode = how soft breaks render (now controllable separately)
            // warnings = collect non-fatal parse issues (undefined refs, bad attr lists, ...)
            // strict   = throw ParseException on fatal errors; caller (convertArticle) catches
            //            and falls back so a broken post still renders for visitors.
            //            Only enabled for the article path so a single broken comment or
            //            excerpt can't throw a whole page.
            $strict = ($context === 'article');
            $converter = new DjotConverter(
                safeMode: $safeMode,
                profile: $profile,
                blocksInterruptParagraphs: $this->markdownMode,
                nestedListsWithoutBlankLine: $this->markdownMode,
                softBreakMode: $softBreakMode,
                warnings: true,
                strict: $strict,
            );

            // Convert tabs to 4 spaces in code blocks for consistent display
            $converter->getHtmlRenderer()->setCodeBlockTabWidth(4);

            // Fold auto-generated heading ids to ASCII (Über -> Uber, Привет -> Privet).
            // djot 0.1.29 changed the default slugger to preserve non-ASCII characters;
            // this extension restores the ASCII-folded ids used by earlier releases so
            // existing anchor/permalink URLs keep working.
            $converter->addExtension(new AsciiHeadingIdsExtension());

            // Enable round-trip mode only for visual editor (excerpt context)
            // This outputs data-djot-* attributes that preserve source syntax
            if ($roundTripMode) {
                $converter->getHtmlRenderer()->setRoundTripMode(true);
                $converter->getParser()->getInlineParser()->setQuoteCharacters('"', '"', "'", "'");
                $converter->addOutputTransformer(static function (string $html): string {
                    return str_replace(["\u{2018}", "\u{2019}"], ["'", "'"], $html);
                });
            }

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

            // Add smart quotes extension for non-English locales.
            // Round-trip/editor mode must not mutate source punctuation.
            if (!$roundTripMode && $smartQuotesLocale !== 'en') {
                $converter->addExtension(new SmartQuotesExtension(locale: $smartQuotesLocale));
            }

            // Apply heading level shift (h1 → h2, etc.)
            if ($this->headingShift > 0) {
                $converter->addExtension(new HeadingLevelShiftExtension(shift: $this->headingShift));
            }

            // Add Mermaid diagram support
            if ($this->mermaidEnabled) {
                $converter->addExtension(new MermaidExtension());
            }

            // Silently strip YAML/TOML/JSON frontmatter at the top of the document.
            // convertExcerpt() carries it separately for visual-editor round trips.
            $converter->addExtension(new FrontmatterExtension());

            // Add semantic span support (kbd, abbr, dfn attributes)
            $converter->addExtension(new SemanticSpanExtension());

            // Add code group support (tabbed code blocks)
            $converter->addExtension(new CodeGroupExtension());

            // Add tabs support (tabbed content sections)
            $converter->addExtension(new TabsExtension());

            // Add heading reference support ([[Heading Text]] links)
            $converter->addExtension(new HeadingReferenceExtension());

            // Add Torchlight syntax highlighting
            // A configured dark theme switches to dual-theme rendering: one
            // pass emits both palettes and the front end flips them via the
            // --phiki-dark-* CSS variables.
            $torchlightTheme = $this->torchlightDarkTheme !== '' && $this->torchlightDarkTheme !== $this->torchlightTheme
                ? ['light' => $this->torchlightTheme, 'dark' => $this->torchlightDarkTheme]
                : $this->torchlightTheme;
            $converter->addExtension(new TorchlightExtension(
                theme: $torchlightTheme,
            ));

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

        try {
            $html = $converter->convert($djot);
            $warnings = $converter->getWarnings();
        } catch (ParseException $e) {
            // Fatal parse error in strict mode. Fall back to a lenient converter so the
            // page still renders for visitors, then synthesize a warning for the banner.
            $lenient = new DjotConverter(
                safeMode: false,
                profile: $this->profileForFallback(),
                blocksInterruptParagraphs: $this->markdownMode,
                nestedListsWithoutBlankLine: $this->markdownMode,
                warnings: false,
                strict: false,
            );
            $html = $lenient->convert($djot);
            $warnings = [
                new ParseWarning(
                    $e->getMessage(),
                    $e->getSourceLine(),
                    $e->getSourceColumn(),
                    'fatal',
                    null,
                ),
            ];
        }

        $html = $this->prependWarningBanner($html, $warnings);

        return $this->postProcess($html, false);
    }

    /**
     * Lenient profile used when strict parsing throws — mirrors postProfile but
     * without extensions, just to produce renderable HTML fallback.
     */
    private function profileForFallback(): ?Profile
    {
        return match ($this->postProfile) {
            'none' => null,
            'full' => Profile::full(),
            'comment' => Profile::comment(),
            'minimal' => Profile::minimal(),
            default => Profile::article(),
        };
    }

    /**
     * Convert for excerpts using article profile but without TOC or permalinks.
     */
    public function convertExcerpt(string $djot): string
    {
        $djot = $this->preProcess($djot, true);
        $frontmatterSource = $this->extractLeadingFrontmatterSource($djot);
        $codeBlockSources = $this->extractFencedCodeBlockSources($djot);
        $containerSources = $this->extractFencedDivSources($djot, 'code-group');
        $roundTripBlockSources = $this->extractRoundTripBlockSources($djot);
        $djot = $this->protectRoundTripSmartQuoteLiterals($djot);
        // Use round-trip mode for visual editor compatibility
        $converter = $this->getProfileConverter($this->postProfile, false, 'excerpt', roundTripMode: true);
        $html = $converter->convert($djot);
        if ($codeBlockSources !== []) {
            $html = $this->restoreRoundTripCodeSources($html, $codeBlockSources);
        }
        if ($containerSources !== []) {
            $html = $this->restoreRoundTripContainerSources($html, $containerSources, 'code-group');
        }
        if ($roundTripBlockSources !== []) {
            $html = $this->addRoundTripBlockSources($html, $roundTripBlockSources);
        }
        $html = $this->restoreRoundTripSmartQuoteLiterals($html);
        if ($frontmatterSource !== null) {
            $html = $this->prependRoundTripFrontmatter($html, $frontmatterSource);
        }

        return $this->postProcess($html, false);
    }

    /**
     * Extract leading frontmatter source so the visual editor can put it back.
     */
    private function extractLeadingFrontmatterSource(string $djot): ?string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $djot));
        if (!preg_match('/^---\w*\s*$/', $lines[0])) {
            return null;
        }

        $count = count($lines);
        for ($i = 1; $i < $count; $i++) {
            if (preg_match('/^---\s*$/', $lines[$i])) {
                return implode("\n", array_slice($lines, 0, $i + 1));
            }
        }

        return null;
    }

    private function prependRoundTripFrontmatter(string $html, string $frontmatterSource): string
    {
        return '<div data-djot-frontmatter data-djot-src="'
            . str_replace("\n", '&#10;', htmlspecialchars($frontmatterSource, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            . '" hidden></div>' . "\n" . $html;
    }

    private function protectRoundTripSmartQuoteLiterals(string $djot): string
    {
        return str_replace(["\u{2018}", "\u{2019}"], ["\u{E000}", "\u{E001}"], $djot);
    }

    private function restoreRoundTripSmartQuoteLiterals(string $html): string
    {
        return str_replace(["\u{E000}", "\u{E001}"], ["\u{2018}", "\u{2019}"], $html);
    }

    /**
     * @return list<array{type: 'paragraph'|'heading', source: string, plain: string}>
     */
    private function extractRoundTripBlockSources(string $djot): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $djot));
        $blocks = [];
        $paragraph = [];
        $inFence = false;
        $inFrontmatter = false;
        $inDivFence = false;

        foreach ($lines as $index => $line) {
            if ($index === 0 && preg_match('/^---\w*\s*$/', $line)) {
                $this->flushRoundTripParagraph($blocks, $paragraph);
                $inFrontmatter = true;

                continue;
            }
            if ($inFrontmatter) {
                if (preg_match('/^---\s*$/', $line)) {
                    $inFrontmatter = false;
                }

                continue;
            }

            if (preg_match('/^\s*(`{3,}|~{3,})/', $line)) {
                $this->flushRoundTripParagraph($blocks, $paragraph);
                $inFence = !$inFence;

                continue;
            }
            if ($inFence) {
                continue;
            }

            if (preg_match('/^\s*:{3,}/', $line)) {
                $this->flushRoundTripParagraph($blocks, $paragraph);
                $inDivFence = !$inDivFence;

                continue;
            }
            if ($inDivFence) {
                continue;
            }

            if (trim($line) === '') {
                $this->flushRoundTripParagraph($blocks, $paragraph);

                continue;
            }

            if (preg_match('/^(#{1,6}\s+.*)$/', $line, $matches)) {
                $this->flushRoundTripParagraph($blocks, $paragraph);
                $blocks[] = [
                    'type' => 'heading',
                    'source' => $matches[1],
                    'plain' => $this->plainTextFromDjotSource($matches[1], 'heading'),
                ];

                continue;
            }

            if ($this->isRoundTripParagraphLine($line)) {
                $paragraph[] = $line;

                continue;
            }

            $this->flushRoundTripParagraph($blocks, $paragraph);
        }

        $this->flushRoundTripParagraph($blocks, $paragraph);

        return $blocks;
    }

    /**
     * @param list<array{type: 'paragraph'|'heading', source: string, plain: string}> $blocks
     * @param list<string> $paragraph
     */
    private function flushRoundTripParagraph(array &$blocks, array &$paragraph): void
    {
        if ($paragraph === []) {
            return;
        }

        $source = implode("\n", $paragraph);
        $blocks[] = [
            'type' => 'paragraph',
            'source' => $source,
            'plain' => $this->plainTextFromDjotSource($source, 'paragraph'),
        ];
        $paragraph = [];
    }

    private function isRoundTripParagraphLine(string $line): bool
    {
        return !preg_match('/^\s*(?:>|[-+*]\s+|\d+[.)]\s+|\|+|\[[^\]]+\]:|\{[.#%])/', $line);
    }

    private function plainTextFromDjotSource(string $source, string $type): string
    {
        $plain = trim(str_replace("\n", ' ', $source));
        if ($type === 'heading') {
            $plain = (string)preg_replace('/^#{1,6}\s+/', '', $plain);
            $plain = (string)preg_replace('/\s+\{#[^}]+\}\s*$/', '', $plain);
        }
        $plain = (string)preg_replace_callback(
            '/\[([^\]]+)\]\([^)]+\)/',
            fn (array $matches): string => $this->plainTextFromDjotSource($matches[1], 'paragraph'),
            $plain,
        );
        $plain = (string)preg_replace('/<((?:https?|mailto):[^>]+)>/', '$1', $plain);
        $plain = (string)preg_replace('/`([^`]*)`/', '$1', $plain);
        $plain = str_replace(['*', '_'], '', $plain);
        $plain = (string)preg_replace('/\s+/', ' ', $plain);

        return html_entity_decode(trim($plain), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param string $html
     * @param list<array{type: 'paragraph'|'heading', source: string, plain: string}> $blockSources
     */
    private function addRoundTripBlockSources(string $html, array $blockSources): string
    {
        $index = 0;

        return preg_replace_callback(
            '/<(p|h[1-6])\b([^>]*)>(.*?)<\/\1>/s',
            function (array $matches) use ($blockSources, &$index): string {
                $expectedType = str_starts_with($matches[1], 'h') ? 'heading' : 'paragraph';
                $source = $blockSources[$index] ?? null;
                if ($source === null || $source['type'] !== $expectedType) {
                    return $matches[0];
                }

                $plain = $this->plainTextFromHtml($matches[3]);
                if ($plain !== $source['plain']) {
                    return $matches[0];
                }

                $index++;
                if (str_contains($matches[2], 'data-djot-src=')) {
                    return $matches[0];
                }

                return '<' . $matches[1] . $matches[2]
                    . ' data-djot-src="' . $this->encodeRoundTripAttribute($source['source']) . '"'
                    . ' data-djot-plain="' . $this->encodeRoundTripAttribute($source['plain']) . '">'
                    . $matches[3] . '</' . $matches[1] . '>';
            },
            $html,
        ) ?? $html;
    }

    private function plainTextFromHtml(string $html): string
    {
        $plain = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = (string)preg_replace('/\s+/', ' ', $plain);

        return trim($plain);
    }

    private function encodeRoundTripAttribute(string $value): string
    {
        return str_replace("\n", '&#10;', htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * @return list<string>
     */
    private function extractFencedDivSources(string $djot, string $class): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $djot));
        $sources = [];
        $current = [];
        $fenceLength = 0;

        foreach ($lines as $line) {
            if ($current === []) {
                if (preg_match('/^\s*(:{3,})\s+' . preg_quote($class, '/') . '\b.*$/', $line, $matches)) {
                    $fenceLength = strlen($matches[1]);
                    $current = [$line];
                }

                continue;
            }

            $current[] = $line;
            if (preg_match('/^\s*:{' . $fenceLength . ',}\s*$/', $line)) {
                $sources[] = implode("\n", $current);
                $current = [];
                $fenceLength = 0;
            }
        }

        return $sources;
    }

    /**
     * @param string $html
     * @param list<string> $containerSources
     * @param string $class
     */
    private function restoreRoundTripContainerSources(string $html, array $containerSources, string $class): string
    {
        $index = 0;

        return preg_replace_callback(
            '/<div\b(?=[^>]*\bclass="[^"]*\b' . preg_quote($class, '/') . '\b[^"]*")([^>]*)\sdata-djot-src="[^"]*"([^>]*)>/',
            function (array $matches) use ($containerSources, &$index): string {
                if (!isset($containerSources[$index])) {
                    return $matches[0];
                }

                $source = $this->encodeRoundTripAttribute($containerSources[$index]);
                $index++;

                return '<div' . $matches[1] . ' data-djot-src="' . $source . '"' . $matches[2] . '>';
            },
            $html,
        ) ?? $html;
    }

    /**
     * @return list<string>
     */
    private function extractFencedCodeBlockSources(string $djot): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $djot));
        $sources = [];
        $current = [];
        $fenceChar = null;
        $fenceLength = 0;
        $stripBlockquoteMarkers = false;

        foreach ($lines as $line) {
            $sourceLine = $stripBlockquoteMarkers ? $this->stripBlockquoteMarker($line) : $line;
            if ($fenceChar === null) {
                $candidateLine = $this->stripBlockquoteMarker($line);
                if (preg_match('/^\s*(`{3,}|~{3,}).*$/', $candidateLine, $matches)) {
                    $fenceChar = $matches[1][0];
                    $fenceLength = strlen($matches[1]);
                    $stripBlockquoteMarkers = $candidateLine !== $line;
                    $current = [$candidateLine];
                }

                continue;
            }

            $current[] = $sourceLine;
            if (preg_match('/^\s*' . preg_quote(str_repeat($fenceChar, $fenceLength), '/') . $fenceChar . '*\s*$/', $sourceLine)) {
                $sources[] = implode("\n", $current);
                $current = [];
                $fenceChar = null;
                $fenceLength = 0;
                $stripBlockquoteMarkers = false;
            }
        }

        return $sources;
    }

    private function stripBlockquoteMarker(string $line): string
    {
        return (string)preg_replace('/^\s*>\s?/', '', $line);
    }

    /**
     * @param string $html
     * @param list<string> $codeBlockSources
     */
    private function restoreRoundTripCodeSources(string $html, array $codeBlockSources): string
    {
        $index = 0;

        return preg_replace_callback(
            '/<pre\b([^>]*)\sdata-djot-src="[^"]*"([^>]*)>/',
            static function (array $matches) use ($codeBlockSources, &$index): string {
                if (!isset($codeBlockSources[$index])) {
                    return $matches[0];
                }

                $source = str_replace(
                    "\n",
                    '&#10;',
                    htmlspecialchars($codeBlockSources[$index], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );
                $index++;

                return '<pre' . $matches[1] . ' data-djot-src="' . $source . '"' . $matches[2] . '>';
            },
            $html,
        ) ?? $html;
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
     * Prepend a visible warning banner to the rendered HTML if any parse warnings
     * were collected, and the current visitor is a logged-in user who can edit posts.
     *
     * Regular visitors see nothing. Warnings are also written to the PHP error log
     * (debug.log when WP_DEBUG_LOG is on) so they can be grepped server-side.
     *
     * @param string $html
     * @param list<\Djot\Exception\ParseWarning> $warnings
     */
    private function prependWarningBanner(string $html, array $warnings): string
    {
        if ($warnings === []) {
            return $html;
        }

        $postId = function_exists('get_the_ID') ? (int)get_the_ID() : 0;
        foreach ($warnings as $warning) {
            error_log(sprintf('[wpdjot] post=%d %s', $postId, (string)$warning));
        }

        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
            return $html;
        }
        if (!function_exists('current_user_can') || !current_user_can('edit_posts')) {
            return $html;
        }

        $items = '';
        foreach ($warnings as $warning) {
            $location = sprintf('line %d, col %d', $warning->getLine(), $warning->getColumn());
            $suggestion = $warning->getSuggestion();
            $items .= '<li><code>' . esc_html($location) . '</code> — ' . esc_html($warning->getMessage())
                . ($suggestion !== null ? ' <em>(' . esc_html($suggestion) . ')</em>' : '')
                . '</li>';
        }

        $heading = esc_html(sprintf(
            /* translators: %d: number of djot parse warnings */
            _n('Djot parse warning (%d)', 'Djot parse warnings (%d)', count($warnings), 'djot-markup'),
            count($warnings),
        ));
        $hint = esc_html__('Only site editors see this banner.', 'djot-markup');

        $banner = '<div class="wpdjot-warnings" role="alert" style="'
            . 'border:1px solid #d63638;background:#fcf0f1;color:#1d2327;'
            . 'padding:12px 16px;margin:0 0 16px;border-radius:4px;font-size:14px;">'
            . '<strong>' . $heading . '</strong>'
            . '<ul style="margin:6px 0 0;padding-left:20px;">' . $items . '</ul>'
            . '<p style="margin:6px 0 0;font-size:12px;color:#646970;">' . $hint . '</p>'
            . '</div>';

        return $banner . $html;
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

        // Process code block line numbers and highlighting syntax
        // Syntax: ``` lang # {2,4-5} or ``` lang #=9 {2,4-5}
        $djot = $this->preProcessCodeBlocks($djot);

        // Decode HTML entities that WordPress encoded at save time
        // (e.g. &gt; in post_content even though we run before the_content filters).
        // Without this, `$foo->bar` inside inline code renders as `$foo-&amp;gt;bar`.
        $djot = html_entity_decode($djot, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Only clean up WordPress HTML artifacts if content was already processed
        if (!$isRaw) {
            // Remove <br> tags that WordPress wpautop() may have added
            // This is critical for fenced code blocks - <br> before ``` breaks recognition
            $djot = preg_replace('/<br\s*\/?>\n?/i', "\n", $djot) ?? $djot;

            // Remove <p>...</p> wrapper tags that wpautop() adds (preserve content)
            $djot = preg_replace('/<p>(.*?)<\/p>/s', "$1\n\n", $djot) ?? $djot;

            // WordPress sometimes adds empty paragraph tags - remove them
            $djot = preg_replace('/<p>\s*<\/p>/', '', $djot) ?? $djot;

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
     * Pre-process code blocks - currently a no-op as filename handling is done by TorchlightExtension.
     *
     * Note: Line numbers (#, #=N) and highlighting ({lines}) are handled by djot-php.
     * The wp-djot specific [filename] syntax is parsed by TorchlightExtension directly
     * from the language string during rendering.
     *
     * Syntax: ``` php [config.php]
     */
    private function preProcessCodeBlocks(string $djot): string
    {
        // Filename parsing is handled by TorchlightExtension
        return $djot;
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
                $config->set('HTML.Allowed', 'p,br,strong,em,a[href|title|rel],ul[class],ol,li,code,pre[data-filename],blockquote,h1,h2,h3,h4,h5,h6,table,caption,thead,tbody,tr,th,td,img[src|alt|title],span[class],div[class],sup,sub,mark,ins,del,hr,input[type|checked|disabled],figure,figcaption');

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
                'id' => true,
                'name' => true,
                'checked' => true,
                'disabled' => true,
                'class' => true,
            ],
            'label' => [
                'for' => true,
                'class' => true,
            ],
            'ul' => [
                'class' => true,
            ],
            'pre' => [
                'class' => true,
                'data-filename' => true,
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
