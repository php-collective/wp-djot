<?php

declare(strict_types=1);

namespace WpDjot\CLI;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use WP_CLI;
use WpDjot\Migration\Migrator;

/**
 * Migrate WordPress content to Djot format.
 *
 * ## EXAMPLES
 *
 *     # Analyze all posts
 *     $ wp djot analyze
 *
 *     # Migrate a single post
 *     $ wp djot migrate --post-id=123
 *
 *     # Migrate all posts (dry run)
 *     $ wp djot migrate --dry-run
 *
 *     # Rollback a migrated post
 *     $ wp djot rollback --post-id=123
 */
class MigrateCommand
{
    private Migrator $migrator;

    public function __construct()
    {
        $this->migrator = new Migrator();
    }

    /**
     * Analyze posts for migration complexity.
     *
     * ## OPTIONS
     *
     * [--post-id=<id>]
     * : Analyze a specific post by ID.
     *
     * [--post-type=<type>]
     * : Post type to analyze. Default: post,page
     *
     * [--limit=<number>]
     * : Limit number of posts to analyze. Default: all
     *
     * [--format=<format>]
     * : Output format. Options: table, json, csv. Default: table
     *
     * ## EXAMPLES
     *
     *     wp djot analyze
     *     wp djot analyze --post-id=123
     *     wp djot analyze --post-type=post --limit=10
     *     wp djot analyze --format=json
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function analyze(array $args, array $assocArgs): void
    {
        $postId = $assocArgs['post-id'] ?? null;
        $postType = $assocArgs['post-type'] ?? 'post,page';
        $limit = isset($assocArgs['limit']) ? (int)$assocArgs['limit'] : -1;
        $format = $assocArgs['format'] ?? 'table';

        if ($postId !== null) {
            $analysis = $this->migrator->analyze((int)$postId);
            $results = [$this->formatAnalysis($analysis)];
        } else {
            $postTypes = array_map('trim', explode(',', $postType));
            $posts = $this->migrator->getPostsToMigrate([
                'post_type' => $postTypes,
                'limit' => $limit,
            ]);

            $results = [];
            foreach ($posts as $post) {
                $analysis = $this->migrator->analyze($post->ID);
                $results[] = $this->formatAnalysis($analysis);
            }
        }

        if (!$results) {
            WP_CLI::warning('No posts found to analyze.');

            return;
        }

        // Summary stats
        $total = count($results);
        $canMigrate = count(array_filter($results, fn ($r) => $r['auto_migrate'] === 'yes'));

        WP_CLI::log('');
        WP_CLI::log("Found {$total} posts, {$canMigrate} can be auto-migrated.");
        WP_CLI::log('');

        WP_CLI\Utils\format_items($format, $results, [
            'post_id',
            'complexity',
            'html',
            'markdown',
            'gutenberg',
            'shortcodes',
            'auto_migrate',
        ]);
    }

    /**
     * Migrate posts to Djot format.
     *
     * ## OPTIONS
     *
     * [--post-id=<id>]
     * : Migrate a specific post by ID.
     *
     * [--post-type=<type>]
     * : Post type to migrate. Default: post,page
     *
     * [--limit=<number>]
     * : Limit number of posts to migrate.
     *
     * [--dry-run]
     * : Preview changes without saving.
     *
     * [--force]
     * : Force migration even for high-complexity posts.
     *
     * [--show-diff]
     * : Show content diff (only with --dry-run).
     *
     * ## EXAMPLES
     *
     *     wp djot migrate --post-id=123
     *     wp djot migrate --dry-run
     *     wp djot migrate --post-type=post --limit=10
     *     wp djot migrate --dry-run --show-diff --post-id=123
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function migrate(array $args, array $assocArgs): void
    {
        $postId = $assocArgs['post-id'] ?? null;
        $postType = $assocArgs['post-type'] ?? 'post,page';
        $limit = isset($assocArgs['limit']) ? (int)$assocArgs['limit'] : -1;
        $dryRun = isset($assocArgs['dry-run']);
        $force = isset($assocArgs['force']);
        $showDiff = isset($assocArgs['show-diff']);

        if ($dryRun) {
            WP_CLI::log('Running in dry-run mode. No changes will be saved.');
            WP_CLI::log('');
        }

        if ($postId !== null) {
            $this->migratePost((int)$postId, $dryRun, $force, $showDiff);

            return;
        }

        // Batch migration
        $postTypes = array_map('trim', explode(',', $postType));
        $posts = $this->migrator->getPostsToMigrate([
            'post_type' => $postTypes,
            'limit' => $limit,
        ]);

        if (!$posts) {
            WP_CLI::warning('No posts found to migrate.');

            return;
        }

        // Filter to only auto-migratable posts unless --force is used
        $migratable = [];
        $skippedHighComplexity = 0;
        foreach ($posts as $post) {
            $analysis = $this->migrator->analyze($post->ID);
            if ($force || $analysis['can_auto_migrate']) {
                $migratable[] = $post;
            } else {
                $skippedHighComplexity++;
            }
        }

        $totalFound = count($posts);
        $totalMigratable = count($migratable);

        WP_CLI::log("Found {$totalFound} posts, {$totalMigratable} can be auto-migrated.");
        if ($skippedHighComplexity > 0 && !$force) {
            WP_CLI::log("Skipping {$skippedHighComplexity} high-complexity posts (use --force to include).");
        }

        if (!$migratable) {
            WP_CLI::warning('No posts available for migration.');

            return;
        }

        WP_CLI::log('');

        if (!$dryRun) {
            WP_CLI::confirm("Are you sure you want to migrate {$totalMigratable} posts?");
        }

        $posts = $migratable;

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $progress = WP_CLI\Utils\make_progress_bar('Migrating posts', $totalMigratable);

        foreach ($posts as $post) {
            $result = $this->migrator->migrate($post->ID, $dryRun, $force);

            if ($result['success']) {
                $success++;
            } elseif (str_contains($result['message'], 'complexity')) {
                $skipped++;
            } else {
                $failed++;
                WP_CLI::warning($result['message']);
            }

            $progress->tick();
        }

        $progress->finish();

        WP_CLI::log('');
        WP_CLI::success("Migration complete: {$success} succeeded, {$skipped} skipped, {$failed} failed.");
    }

    /**
     * Rollback migrated posts to original content.
     *
     * ## OPTIONS
     *
     * [--post-id=<id>]
     * : Rollback a specific post by ID.
     *
     * [--all]
     * : Rollback all migrated posts.
     *
     * ## EXAMPLES
     *
     *     wp djot rollback --post-id=123
     *     wp djot rollback --all
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function rollback(array $args, array $assocArgs): void
    {
        $postId = $assocArgs['post-id'] ?? null;
        $all = isset($assocArgs['all']);

        if ($postId === null && !$all) {
            WP_CLI::error('Please specify --post-id=<id> or --all');
        }

        if ($postId !== null) {
            $result = $this->migrator->rollback((int)$postId);
            if ($result['success']) {
                WP_CLI::success($result['message']);
            } else {
                WP_CLI::error($result['message']);
            }

            return;
        }

        // Rollback all
        $posts = $this->migrator->getMigratedPosts();

        if (!$posts) {
            WP_CLI::warning('No migrated posts found to rollback.');

            return;
        }

        $total = count($posts);
        WP_CLI::confirm("Are you sure you want to rollback {$total} posts?");

        $success = 0;
        $failed = 0;

        $progress = WP_CLI\Utils\make_progress_bar('Rolling back posts', $total);

        foreach ($posts as $post) {
            $result = $this->migrator->rollback($post->ID);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
            $progress->tick();
        }

        $progress->finish();

        WP_CLI::success("Rollback complete: {$success} succeeded, {$failed} failed.");
    }

    /**
     * Show migration status and statistics.
     *
     * ## EXAMPLES
     *
     *     wp djot status
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function status(array $args, array $assocArgs): void
    {
        $migrated = $this->migrator->getMigratedPosts();
        $pending = $this->migrator->getPostsToMigrate();

        WP_CLI::log('');
        WP_CLI::log('=== WP Djot Migration Status ===');
        WP_CLI::log('');
        WP_CLI::log('Migrated posts: ' . count($migrated));
        WP_CLI::log('Pending posts:  ' . count($pending));
        WP_CLI::log('');

        if ($pending) {
            // Analyze complexity distribution
            $complexity = ['none' => 0, 'low' => 0, 'medium' => 0, 'high' => 0];
            foreach ($pending as $post) {
                $analysis = $this->migrator->analyze($post->ID);
                $complexity[$analysis['complexity']]++;
            }

            WP_CLI::log('Pending by complexity:');
            WP_CLI::log("  None:   {$complexity['none']}");
            WP_CLI::log("  Low:    {$complexity['low']}");
            WP_CLI::log("  Medium: {$complexity['medium']}");
            WP_CLI::log("  High:   {$complexity['high']}");
        }
    }

    /**
     * @return array<string, string>
     */
    private function formatAnalysis(array $analysis): array
    {
        return [
            'post_id' => (string)$analysis['post_id'],
            'complexity' => $analysis['complexity'],
            'html' => $analysis['has_html'] ? 'yes' : 'no',
            'markdown' => $analysis['has_markdown'] ? 'yes' : 'no',
            'gutenberg' => $analysis['has_gutenberg'] ? 'yes' : 'no',
            'shortcodes' => implode(', ', $analysis['shortcodes']) ?: '-',
            'auto_migrate' => $analysis['can_auto_migrate'] ? 'yes' : 'no',
        ];
    }

    private function migratePost(int $postId, bool $dryRun, bool $force, bool $showDiff): void
    {
        $result = $this->migrator->migrate($postId, $dryRun, $force);

        if (!$result['success']) {
            WP_CLI::error($result['message']);
        }

        WP_CLI::success($result['message']);

        if ($showDiff && isset($result['original'], $result['converted'])) {
            WP_CLI::log('');
            WP_CLI::log('=== Original Content ===');
            WP_CLI::log($result['original']);
            WP_CLI::log('');
            WP_CLI::log('=== Converted Content ===');
            WP_CLI::log($result['converted']);
        }
    }

    /**
     * Analyze comments for migration.
     *
     * ## OPTIONS
     *
     * [--comment-id=<id>]
     * : Analyze a specific comment by ID.
     *
     * [--post-id=<id>]
     * : Analyze comments for a specific post.
     *
     * [--limit=<number>]
     * : Limit number of comments to analyze. Default: all
     *
     * [--format=<format>]
     * : Output format. Options: table, json, csv. Default: table
     *
     * ## EXAMPLES
     *
     *     wp djot analyze-comments
     *     wp djot analyze-comments --post-id=123
     *
     * @subcommand analyze-comments
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function analyzeComments(array $args, array $assocArgs): void
    {
        $commentId = $assocArgs['comment-id'] ?? null;
        $postId = $assocArgs['post-id'] ?? null;
        $limit = isset($assocArgs['limit']) ? (int)$assocArgs['limit'] : -1;
        $format = $assocArgs['format'] ?? 'table';

        if ($commentId !== null) {
            $analysis = $this->migrator->analyzeComment((int)$commentId);
            $results = [$this->formatCommentAnalysis($analysis)];
        } else {
            $queryArgs = ['limit' => $limit];
            if ($postId !== null) {
                $queryArgs['post_id'] = (int)$postId;
            }

            $comments = $this->migrator->getCommentsToMigrate($queryArgs);

            $results = [];
            foreach ($comments as $comment) {
                $analysis = $this->migrator->analyzeComment($comment->comment_ID);
                $results[] = $this->formatCommentAnalysis($analysis);
            }
        }

        if (!$results) {
            WP_CLI::warning('No comments found to analyze.');

            return;
        }

        $total = count($results);
        WP_CLI::log('');
        WP_CLI::log("Found {$total} comments to analyze.");
        WP_CLI::log('');

        WP_CLI\Utils\format_items($format, $results, [
            'comment_id',
            'complexity',
            'html',
            'markdown',
        ]);
    }

    /**
     * Migrate comments to Djot format.
     *
     * ## OPTIONS
     *
     * [--comment-id=<id>]
     * : Migrate a specific comment by ID.
     *
     * [--post-id=<id>]
     * : Migrate comments for a specific post.
     *
     * [--limit=<number>]
     * : Limit number of comments to migrate.
     *
     * [--dry-run]
     * : Preview changes without saving.
     *
     * ## EXAMPLES
     *
     *     wp djot migrate-comments --dry-run
     *     wp djot migrate-comments --post-id=123
     *
     * @subcommand migrate-comments
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function migrateComments(array $args, array $assocArgs): void
    {
        $commentId = $assocArgs['comment-id'] ?? null;
        $postId = $assocArgs['post-id'] ?? null;
        $limit = isset($assocArgs['limit']) ? (int)$assocArgs['limit'] : -1;
        $dryRun = isset($assocArgs['dry-run']);

        if ($dryRun) {
            WP_CLI::log('Running in dry-run mode. No changes will be saved.');
            WP_CLI::log('');
        }

        if ($commentId !== null) {
            $result = $this->migrator->migrateComment((int)$commentId, $dryRun);
            if ($result['success']) {
                WP_CLI::success($result['message']);
            } else {
                WP_CLI::error($result['message']);
            }

            return;
        }

        $queryArgs = ['limit' => $limit];
        if ($postId !== null) {
            $queryArgs['post_id'] = (int)$postId;
        }

        $comments = $this->migrator->getCommentsToMigrate($queryArgs);

        if (!$comments) {
            WP_CLI::warning('No comments found to migrate.');

            return;
        }

        $total = count($comments);
        WP_CLI::log("Found {$total} comments to migrate.");

        if (!$dryRun) {
            WP_CLI::confirm("Are you sure you want to migrate {$total} comments?");
        }

        $success = 0;
        $failed = 0;

        $progress = WP_CLI\Utils\make_progress_bar('Migrating comments', $total);

        foreach ($comments as $comment) {
            $result = $this->migrator->migrateComment($comment->comment_ID, $dryRun);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
                WP_CLI::warning($result['message']);
            }
            $progress->tick();
        }

        $progress->finish();

        WP_CLI::log('');
        WP_CLI::success("Migration complete: {$success} succeeded, {$failed} failed.");
    }

    /**
     * Rollback migrated comments.
     *
     * ## OPTIONS
     *
     * [--comment-id=<id>]
     * : Rollback a specific comment by ID.
     *
     * [--all]
     * : Rollback all migrated comments.
     *
     * ## EXAMPLES
     *
     *     wp djot rollback-comments --comment-id=123
     *     wp djot rollback-comments --all
     *
     * @subcommand rollback-comments
     *
     * @param array<string> $args
     * @param array<string, string> $assocArgs
     */
    public function rollbackComments(array $args, array $assocArgs): void
    {
        $commentId = $assocArgs['comment-id'] ?? null;
        $all = isset($assocArgs['all']);

        if ($commentId === null && !$all) {
            WP_CLI::error('Please specify --comment-id=<id> or --all');
        }

        if ($commentId !== null) {
            $result = $this->migrator->rollbackComment((int)$commentId);
            if ($result['success']) {
                WP_CLI::success($result['message']);
            } else {
                WP_CLI::error($result['message']);
            }

            return;
        }

        $comments = $this->migrator->getMigratedComments();

        if (!$comments) {
            WP_CLI::warning('No migrated comments found to rollback.');

            return;
        }

        $total = count($comments);
        WP_CLI::confirm("Are you sure you want to rollback {$total} comments?");

        $success = 0;
        $failed = 0;

        $progress = WP_CLI\Utils\make_progress_bar('Rolling back comments', $total);

        foreach ($comments as $comment) {
            $result = $this->migrator->rollbackComment($comment->comment_ID);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
            $progress->tick();
        }

        $progress->finish();

        WP_CLI::success("Rollback complete: {$success} succeeded, {$failed} failed.");
    }

    /**
     * @return array<string, string>
     */
    private function formatCommentAnalysis(array $analysis): array
    {
        return [
            'comment_id' => (string)$analysis['comment_id'],
            'complexity' => $analysis['complexity'],
            'html' => $analysis['has_html'] ? 'yes' : 'no',
            'markdown' => $analysis['has_markdown'] ? 'yes' : 'no',
        ];
    }
}
