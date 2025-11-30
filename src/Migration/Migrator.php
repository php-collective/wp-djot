<?php

declare(strict_types=1);

namespace WpDjot\Migration;

use Djot\Converter\HtmlToDjot;
use Djot\Converter\MarkdownToDjot;
use WP_Query;

/**
 * Handles migration of WordPress content to Djot format.
 */
class Migrator
{
    private HtmlToDjot $htmlConverter;

    private MarkdownToDjot $markdownConverter;

    private string $backupMetaKey = '_wp_djot_original_content';

    private string $commentBackupMetaKey = '_wp_djot_original_comment';

    public function __construct()
    {
        $this->htmlConverter = new HtmlToDjot();
        $this->markdownConverter = new MarkdownToDjot();
    }

    /**
     * Analyze a post's content to determine migration complexity.
     *
     * @return array{
     *     post_id: int,
     *     has_html: bool,
     *     has_markdown: bool,
     *     has_shortcodes: bool,
     *     has_gutenberg: bool,
     *     shortcodes: array<string>,
     *     complexity: string,
     *     can_auto_migrate: bool
     * }
     */
    public function analyze(int $postId): array
    {
        $post = get_post($postId);
        if (!$post) {
            return [
                'post_id' => $postId,
                'has_html' => false,
                'has_markdown' => false,
                'has_shortcodes' => false,
                'has_gutenberg' => false,
                'shortcodes' => [],
                'complexity' => 'unknown',
                'can_auto_migrate' => false,
            ];
        }

        $content = $post->post_content;

        $hasHtml = $this->detectHtml($content);
        $hasMarkdown = $this->detectMarkdown($content);
        $hasGutenberg = $this->detectGutenberg($content);
        $shortcodes = $this->detectShortcodes($content);
        $hasShortcodes = (bool)$shortcodes;

        // Determine complexity
        $complexityScore = 0;
        if ($hasHtml) {
            $complexityScore += 2;
        }
        if ($hasMarkdown) {
            $complexityScore += 1;
        }
        if ($hasGutenberg) {
            $complexityScore += 3;
        }
        if ($hasShortcodes) {
            $complexityScore += count($shortcodes);
        }

        $complexity = match (true) {
            $complexityScore === 0 => 'none',
            $complexityScore <= 2 => 'low',
            $complexityScore <= 5 => 'medium',
            default => 'high',
        };

        // Can auto-migrate if no Gutenberg and no complex shortcodes
        $canAutoMigrate = !$hasGutenberg && $complexityScore <= 5;

        return [
            'post_id' => $postId,
            'has_html' => $hasHtml,
            'has_markdown' => $hasMarkdown,
            'has_shortcodes' => $hasShortcodes,
            'has_gutenberg' => $hasGutenberg,
            'shortcodes' => $shortcodes,
            'complexity' => $complexity,
            'can_auto_migrate' => $canAutoMigrate,
        ];
    }

    /**
     * Migrate a single post to Djot format.
     *
     * @return array{success: bool, message: string, original?: string, converted?: string}
     */
    public function migrate(int $postId, bool $dryRun = false, bool $force = false): array
    {
        $post = get_post($postId);
        if (!$post) {
            return [
                'success' => false,
                'message' => "Post {$postId} not found",
            ];
        }

        $analysis = $this->analyze($postId);

        if (!$force && !$analysis['can_auto_migrate']) {
            return [
                'success' => false,
                'message' => "Post {$postId} has high complexity ({$analysis['complexity']}). Use --force to migrate anyway.",
            ];
        }

        $original = $post->post_content;
        $converted = $this->convert($original, $analysis);

        if ($dryRun) {
            return [
                'success' => true,
                'message' => "Dry run: Post {$postId} would be converted",
                'original' => $original,
                'converted' => $converted,
            ];
        }

        // Backup original content
        update_post_meta($postId, $this->backupMetaKey, $original);

        // Update post
        $result = wp_update_post([
            'ID' => $postId,
            'post_content' => $converted,
        ], true);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => "Failed to update post {$postId}: " . $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => "Post {$postId} migrated successfully",
            'original' => $original,
            'converted' => $converted,
        ];
    }

    /**
     * Rollback a migrated post to its original content.
     */
    public function rollback(int $postId): array
    {
        $original = get_post_meta($postId, $this->backupMetaKey, true);

        if (!$original) {
            return [
                'success' => false,
                'message' => "No backup found for post {$postId}",
            ];
        }

        $result = wp_update_post([
            'ID' => $postId,
            'post_content' => $original,
        ], true);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => "Failed to rollback post {$postId}: " . $result->get_error_message(),
            ];
        }

        // Remove backup after successful rollback
        delete_post_meta($postId, $this->backupMetaKey);

        return [
            'success' => true,
            'message' => "Post {$postId} rolled back successfully",
        ];
    }

    /**
     * Get posts that can be migrated.
     *
     * @param array{post_type?: (string | array<string>), limit?: int, offset?: int}|array $args
     *
     * @return array<\WP_Post>
     */
    public function getPostsToMigrate(array $args = []): array
    {
        $defaults = [
            'post_type' => ['post', 'page'],
            'limit' => -1,
            'offset' => 0,
        ];

        $args = array_merge($defaults, $args);

        $query = new WP_Query([
            'post_type' => $args['post_type'],
            'post_status' => 'any',
            'posts_per_page' => $args['limit'],
            'offset' => $args['offset'],
            'meta_query' => [
                [
                    'key' => $this->backupMetaKey,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        return $query->posts;
    }

    /**
     * Get posts that have been migrated (have backups).
     *
     * @return array<\WP_Post>
     */
    public function getMigratedPosts(): array
    {
        $query = new WP_Query([
            'post_type' => ['post', 'page'],
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => $this->backupMetaKey,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        return $query->posts;
    }

    /**
     * Convert content to Djot format.
     *
     * @param string $content
     * @param array<string, mixed> $analysis
     */
    private function convert(string $content, array $analysis): string
    {
        // Preserve shortcodes
        $shortcodePlaceholders = [];
        $content = $this->protectShortcodes($content, $shortcodePlaceholders);

        // Preserve Gutenberg blocks (if any)
        $blockPlaceholders = [];
        $content = $this->protectGutenbergBlocks($content, $blockPlaceholders);

        // Convert based on content type (Markdown first, then HTML)
        if ($analysis['has_markdown']) {
            $content = $this->markdownConverter->convert($content);
        }
        if ($analysis['has_html']) {
            $content = $this->htmlConverter->convert($content);
        }

        // Restore Gutenberg blocks
        foreach ($blockPlaceholders as $placeholder => $block) {
            $content = str_replace($placeholder, $block, $content);
        }

        // Restore shortcodes
        foreach ($shortcodePlaceholders as $placeholder => $shortcode) {
            $content = str_replace($placeholder, $shortcode, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     * @param array<string, string> $placeholders
     */
    private function protectShortcodes(string $content, array &$placeholders): string
    {
        // Match WordPress shortcodes
        $pattern = '/\[([a-zA-Z_][a-zA-Z0-9_-]*)(?:\s[^\]]*)?(?:\/\]|\](?:.*?\[\/\1\])?)/s';

        return (string)preg_replace_callback($pattern, function (array $matches) use (&$placeholders): string {
            $placeholder = '{{SHORTCODE_' . count($placeholders) . '}}';
            $placeholders[$placeholder] = $matches[0];

            return $placeholder;
        }, $content);
    }

    /**
     * @param string $content
     * @param array<string, string> $placeholders
     */
    private function protectGutenbergBlocks(string $content, array &$placeholders): string
    {
        // Match Gutenberg block comments
        $pattern = '/<!--\s*wp:[^>]+-->.*?<!--\s*\/wp:[^>]+-->/s';

        return (string)preg_replace_callback($pattern, function (array $matches) use (&$placeholders): string {
            $placeholder = '{{BLOCK_' . count($placeholders) . '}}';
            $placeholders[$placeholder] = $matches[0];

            return $placeholder;
        }, $content);
    }

    private function detectHtml(string $content): bool
    {
        // Check for common HTML tags (not just < which could be comparison)
        return (bool)preg_match('/<(p|div|span|a|img|h[1-6]|ul|ol|li|table|strong|em|br|hr)[^>]*>/i', $content);
    }

    private function detectMarkdown(string $content): bool
    {
        $patterns = [
            '/^#{1,6}\s/m', // Headers
            '/\*\*[^*]+\*\*/', // Bold
            '/\*[^*]+\*/', // Italic
            '/\[.+\]\(.+\)/', // Links
            '/^[-*+]\s/m', // Unordered lists
            '/^\d+\.\s/m', // Ordered lists
            '/^```/m', // Code blocks
            '/^>/m', // Blockquotes
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    private function detectGutenberg(string $content): bool
    {
        return str_contains($content, '<!-- wp:');
    }

    /**
     * @return array<string>
     */
    private function detectShortcodes(string $content): array
    {
        preg_match_all('/\[([a-zA-Z_][a-zA-Z0-9_-]*)/', $content, $matches);

        return array_unique($matches[1]);
    }

    /**
     * Analyze a comment's content to determine migration complexity.
     *
     * @return array{
     *     comment_id: int,
     *     has_html: bool,
     *     has_markdown: bool,
     *     complexity: string,
     *     can_auto_migrate: bool
     * }
     */
    public function analyzeComment(int $commentId): array
    {
        $comment = get_comment($commentId);
        if (!$comment) {
            return [
                'comment_id' => $commentId,
                'has_html' => false,
                'has_markdown' => false,
                'complexity' => 'unknown',
                'can_auto_migrate' => false,
            ];
        }

        $content = $comment->comment_content;

        $hasHtml = $this->detectHtml($content);
        $hasMarkdown = $this->detectMarkdown($content);

        // Comments are simpler - no Gutenberg or shortcodes typically
        $complexityScore = 0;
        if ($hasHtml) {
            $complexityScore += 2;
        }
        if ($hasMarkdown) {
            $complexityScore += 1;
        }

        $complexity = match (true) {
            $complexityScore === 0 => 'none',
            $complexityScore <= 2 => 'low',
            default => 'medium',
        };

        return [
            'comment_id' => $commentId,
            'has_html' => $hasHtml,
            'has_markdown' => $hasMarkdown,
            'complexity' => $complexity,
            'can_auto_migrate' => true, // Comments are always auto-migratable
        ];
    }

    /**
     * Migrate a single comment to Djot format.
     *
     * @return array{success: bool, message: string, original?: string, converted?: string}
     */
    public function migrateComment(int $commentId, bool $dryRun = false): array
    {
        $comment = get_comment($commentId);
        if (!$comment) {
            return [
                'success' => false,
                'message' => "Comment {$commentId} not found",
            ];
        }

        $analysis = $this->analyzeComment($commentId);
        $original = $comment->comment_content;

        // Convert content
        $converted = $original;
        if ($analysis['has_markdown']) {
            $converted = $this->markdownConverter->convert($converted);
        }
        if ($analysis['has_html']) {
            $converted = $this->htmlConverter->convert($converted);
        }

        if ($dryRun) {
            return [
                'success' => true,
                'message' => "Dry run: Comment {$commentId} would be converted",
                'original' => $original,
                'converted' => $converted,
            ];
        }

        // Backup original content
        update_comment_meta($commentId, $this->commentBackupMetaKey, $original);

        // Update comment
        $result = wp_update_comment([
            'comment_ID' => $commentId,
            'comment_content' => $converted,
        ], true);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => "Failed to update comment {$commentId}: " . $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => "Comment {$commentId} migrated successfully",
            'original' => $original,
            'converted' => $converted,
        ];
    }

    /**
     * Rollback a migrated comment to its original content.
     *
     * @return array{success: bool, message: string}
     */
    public function rollbackComment(int $commentId): array
    {
        $original = get_comment_meta($commentId, $this->commentBackupMetaKey, true);

        if (!$original) {
            return [
                'success' => false,
                'message' => "No backup found for comment {$commentId}",
            ];
        }

        $result = wp_update_comment([
            'comment_ID' => $commentId,
            'comment_content' => $original,
        ], true);

        if (is_wp_error($result)) {
            return [
                'success' => false,
                'message' => "Failed to rollback comment {$commentId}: " . $result->get_error_message(),
            ];
        }

        // Remove backup after successful rollback
        delete_comment_meta($commentId, $this->commentBackupMetaKey);

        return [
            'success' => true,
            'message' => "Comment {$commentId} rolled back successfully",
        ];
    }

    /**
     * Get comments that can be migrated.
     *
     * @param array{limit?: int, offset?: int, post_id?: int}|array $args
     *
     * @return array<\WP_Comment>
     */
    public function getCommentsToMigrate(array $args = []): array
    {
        $defaults = [
            'limit' => -1,
            'offset' => 0,
        ];

        $args = array_merge($defaults, $args);

        $queryArgs = [
            'status' => 'approve',
            'number' => $args['limit'] === -1 ? 0 : $args['limit'],
            'offset' => $args['offset'],
            'meta_query' => [
                [
                    'key' => $this->commentBackupMetaKey,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        if (isset($args['post_id'])) {
            $queryArgs['post_id'] = $args['post_id'];
        }

        return get_comments($queryArgs);
    }

    /**
     * Get comments that have been migrated (have backups).
     *
     * @return array<\WP_Comment>
     */
    public function getMigratedComments(): array
    {
        return get_comments([
            'status' => 'approve',
            'number' => 0,
            'meta_query' => [
                [
                    'key' => $this->commentBackupMetaKey,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
    }
}
