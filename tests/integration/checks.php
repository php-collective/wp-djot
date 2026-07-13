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

// The public preview-comment endpoint is gated on Djot comment rendering
// (added this release); enable it for these checks. Captured once here and
// restored at the end of the file.
$wpdjot_prev_options = get_option('wpdjot_settings', []);
update_option('wpdjot_settings', array_merge(is_array($wpdjot_prev_options) ? $wpdjot_prev_options : [], [
    'enable_comments' => true,
]));

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

// Gate: with Djot comments explicitly disabled, the endpoint refuses.
update_option('wpdjot_settings', array_merge(is_array($wpdjot_prev_options) ? $wpdjot_prev_options : [], [
    'enable_comments' => false,
]));
$disabledReq = new WP_REST_Request('POST', '/wpdjot/v1/preview-comment');
$disabledReq->set_param('content', 'x');
$wpdjot_check('REST preview-comment refuses when comments disabled', rest_do_request($disabledReq)->get_status() === 403);
update_option('wpdjot_settings', array_merge(is_array($wpdjot_prev_options) ? $wpdjot_prev_options : [], [
    'enable_comments' => true,
]));

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

// Mermaid conditional enqueue: the library loads on a singular post whose
// source uses mermaid, and stays out everywhere else. ($wpdjot_prev_options was
// captured above, before the preview checks toggled enable_comments.)
update_option('wpdjot_settings', array_merge(is_array($wpdjot_prev_options) ? $wpdjot_prev_options : [], [
    'mermaid_enabled' => true,
    'enable_posts' => true,
    'enable_comments' => true,
]));
$wpdjot_plugin = new \WpDjot\Plugin();
$wpdjot_plugin->init();

$wpdjot_mermaid_post = wp_insert_post([
    'post_title' => 'Mermaid integration check',
    'post_status' => 'publish',
    'post_content' => "``` mermaid\nflowchart LR\n    A --> B\n```",
]);
$wpdjot_plain_post = wp_insert_post([
    'post_title' => 'Plain integration check',
    'post_status' => 'publish',
    'post_content' => 'No diagrams here.',
]);

$wpdjot_mermaid_enqueued = static function (array $query) use ($wpdjot_plugin): bool {
    global $wp_query, $wp_the_query, $wp_scripts;
    $wp_scripts = null;
    $wp_query = new WP_Query($query);
    $wp_the_query = $wp_query;
    $wpdjot_plugin->enqueueAssets();

    return wp_script_is('mermaid', 'enqueued');
};

$wpdjot_check('mermaid enqueued on singular post using it', $wpdjot_mermaid_enqueued(['p' => $wpdjot_mermaid_post]));
$wpdjot_check('mermaid skipped on singular post without it', !$wpdjot_mermaid_enqueued(['p' => $wpdjot_plain_post]));
$wpdjot_check('mermaid skipped on archive views', !$wpdjot_mermaid_enqueued([]));

// Diagrams do not render in comments (the comment profile gates the
// extension), so comment content deliberately does not factor into the
// sniff.
$wpdjot_check(
    'comment profile does not render mermaid',
    !str_contains(\WpDjot\Converter::fromSettings()->convertComment("``` mermaid\nflowchart LR\n    A --> B\n```"), 'class="mermaid"'),
);

update_option('wpdjot_settings', $wpdjot_prev_options);
wp_delete_post($wpdjot_mermaid_post, true);
wp_delete_post($wpdjot_plain_post, true);

if ($wpdjot_failures !== []) {
    fwrite(STDERR, "\n" . count($wpdjot_failures) . " integration check(s) failed:\n - " . implode("\n - ", $wpdjot_failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "\nAll integration checks passed.\n");
