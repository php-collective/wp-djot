<?php

// No declare(strict_types) here: this file is executed via `wp eval-file`,
// which wraps it in eval() where a strict_types declaration is illegal.

/**
 * Integration checks for WP Djot.
 *
 * Runs inside a real WordPress via `wp eval-file` (see the "WP Integration" CI
 * job), so WordPress core and the activated plugin are fully loaded. Unlike the
 * unit suite — which mocks WP functions — these exercise the actual entry
 * points (shortcode, the_content / comment_text filters, template tag, REST
 * routes) against a live install.
 *
 * Exits non-zero if any check fails so CI marks the job red.
 */

$wpdjot_failures = [];

$wpdjot_check = static function (string $name, bool $passed, string $detail = '') use (&$wpdjot_failures): void {
    if ($passed) {
        fwrite(STDOUT, "PASS  {$name}\n");

        return;
    }

    $wpdjot_failures[] = $name . ($detail !== '' ? " — {$detail}" : '');
    fwrite(STDOUT, "FAIL  {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n");
};

$wpdjot_snippet = static fn (string $html): string => trim(preg_replace('/\s+/', ' ', substr($html, 0, 160)) ?? '');

// Plugin is loaded and active.
$wpdjot_check('plugin constant defined', defined('WPDJOT_VERSION'));
$wpdjot_check('template tag registered', function_exists('wpdjot_to_html'));
$wpdjot_check('shortcode registered', shortcode_exists('djot'));

// Shortcode renders Djot to HTML.
$shortcodeHtml = do_shortcode('[djot]# Hello World[/djot]');
$wpdjot_check(
    'shortcode renders heading',
    str_contains($shortcodeHtml, '<h1') && str_contains($shortcodeHtml, 'Hello World'),
    $wpdjot_snippet($shortcodeHtml),
);

// Template tag renders Djot to HTML.
$tagHtml = wpdjot_to_html("## Sub\n\nSome _text_ and *strong*.");
$wpdjot_check(
    'wpdjot_to_html renders',
    str_contains($tagHtml, '<h2') && str_contains($tagHtml, '<em>'),
    $wpdjot_snippet($tagHtml),
);

// Public REST endpoint (no auth) renders a comment preview.
$previewReq = new WP_REST_Request('POST', '/wpdjot/v1/preview-comment');
$previewReq->set_param('content', 'Inline `code` here.');
$previewRes = rest_do_request($previewReq);
$previewData = $previewRes->get_data();
$wpdjot_check('REST preview-comment returns 200', $previewRes->get_status() === 200, 'status=' . $previewRes->get_status());
$wpdjot_check(
    'REST preview-comment renders html',
    is_array($previewData) && isset($previewData['html']) && str_contains((string)$previewData['html'], '<code>'),
    is_array($previewData) ? $wpdjot_snippet((string)($previewData['html'] ?? '')) : 'no data',
);

// Authenticated REST render endpoint (requires edit_posts).
wp_set_current_user(1);
$renderReq = new WP_REST_Request('POST', '/wpdjot/v1/render');
$renderReq->set_param('content', '# Rendered Heading');
$renderRes = rest_do_request($renderReq);
$renderData = $renderRes->get_data();
$wpdjot_check('REST render returns 200 (authed)', $renderRes->get_status() === 200, 'status=' . $renderRes->get_status());
$wpdjot_check(
    'REST render returns html',
    is_array($renderData) && isset($renderData['html']) && str_contains((string)$renderData['html'], '<h1'),
    is_array($renderData) ? $wpdjot_snippet((string)($renderData['html'] ?? '')) : 'no data',
);

if ($wpdjot_failures !== []) {
    fwrite(STDERR, "\n" . count($wpdjot_failures) . " integration check(s) failed:\n - " . implode("\n - ", $wpdjot_failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "\nAll integration checks passed.\n");
