<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase;

use PHPUnit\Framework\TestCase;
use WpDjot\Migration\Migrator;

class MigratorTest extends TestCase
{
    private Migrator $migrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrator = new Migrator();

        // Reset global test data
        global $wp_test_posts, $wp_test_meta, $wp_test_comments, $wp_test_comment_meta;
        $wp_test_posts = [];
        $wp_test_meta = [];
        $wp_test_comments = [];
        $wp_test_comment_meta = [];
    }

    public function testAnalyzeDetectsHtml(): void
    {
        $this->createPost(1, '<p>This is <strong>HTML</strong> content</p>');

        $result = $this->migrator->analyze(1);

        $this->assertTrue($result['has_html']);
        $this->assertFalse($result['has_gutenberg']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testAnalyzeDetectsMarkdown(): void
    {
        $this->createPost(2, "# Heading\n\nThis is **bold** and *italic* text.\n\n- Item 1\n- Item 2");

        $result = $this->migrator->analyze(2);

        $this->assertTrue($result['has_markdown']);
        $this->assertFalse($result['has_gutenberg']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testAnalyzeDetectsGutenberg(): void
    {
        $content = '<!-- wp:paragraph --><p>Block content</p><!-- /wp:paragraph -->';
        $this->createPost(3, $content);

        $result = $this->migrator->analyze(3);

        $this->assertTrue($result['has_gutenberg']);
        $this->assertFalse($result['can_auto_migrate']);
        // Gutenberg(3) + HTML(2) = 5 → medium
        $this->assertEquals('medium', $result['complexity']);
    }

    public function testAnalyzeDetectsShortcodes(): void
    {
        $this->createPost(4, 'Some text [gallery ids="1,2,3"] more text [video src="test.mp4"]');

        $result = $this->migrator->analyze(4);

        $this->assertTrue($result['has_shortcodes']);
        $this->assertContains('gallery', $result['shortcodes']);
        $this->assertContains('video', $result['shortcodes']);
    }

    public function testAnalyzePlainText(): void
    {
        $this->createPost(5, 'Just plain text without any markup.');

        $result = $this->migrator->analyze(5);

        $this->assertFalse($result['has_html']);
        $this->assertFalse($result['has_markdown']);
        $this->assertFalse($result['has_gutenberg']);
        $this->assertFalse($result['has_shortcodes']);
        $this->assertEquals('none', $result['complexity']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testAnalyzeNonExistentPost(): void
    {
        $result = $this->migrator->analyze(999);

        $this->assertFalse($result['can_auto_migrate']);
        $this->assertEquals('unknown', $result['complexity']);
    }

    public function testAnalyzeComplexityLevels(): void
    {
        // None - plain text (0)
        $this->createPost(10, 'Plain text');
        $this->assertEquals('none', $this->migrator->analyze(10)['complexity']);

        // Low - just HTML (2)
        $this->createPost(11, '<p>Simple HTML</p>');
        $this->assertEquals('low', $this->migrator->analyze(11)['complexity']);

        // Medium - HTML(2) + shortcode(1) = 3
        $this->createPost(12, '<p>HTML</p> [gallery]');
        $this->assertEquals('medium', $this->migrator->analyze(12)['complexity']);

        // Medium - Gutenberg(3) + HTML(2) = 5 (still medium, <=5)
        $this->createPost(13, '<!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->');
        $this->assertEquals('medium', $this->migrator->analyze(13)['complexity']);

        // High - Gutenberg(3) + HTML(2) + 2 shortcodes(2) = 7
        $this->createPost(14, '<!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph --> [gallery] [video]');
        $this->assertEquals('high', $this->migrator->analyze(14)['complexity']);
    }

    public function testMigrateDryRun(): void
    {
        $original = '<p>This is <strong>HTML</strong> content</p>';
        $this->createPost(20, $original);

        $result = $this->migrator->migrate(20, dryRun: true);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Dry run', $result['message']);
        $this->assertEquals($original, $result['original']);
        $this->assertNotEmpty($result['converted']);

        // Verify content was NOT changed
        global $wp_test_posts;
        $this->assertEquals($original, $wp_test_posts[20]->post_content);
    }

    public function testMigrateActual(): void
    {
        $original = '<p>This is <strong>HTML</strong> content</p>';
        $this->createPost(21, $original);

        $result = $this->migrator->migrate(21, dryRun: false);

        $this->assertTrue($result['success']);

        // Verify backup was created
        global $wp_test_meta;
        $this->assertEquals($original, $wp_test_meta[21]['_wp_djot_original_content']);

        // Verify content was changed
        global $wp_test_posts;
        $this->assertNotEquals($original, $wp_test_posts[21]->post_content);
    }

    public function testMigrateRejectsHighComplexity(): void
    {
        $this->createPost(22, '<!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->');

        $result = $this->migrator->migrate(22, dryRun: false, force: false);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('complexity', $result['message']);
    }

    public function testMigrateForceHighComplexity(): void
    {
        $this->createPost(23, '<!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->');

        $result = $this->migrator->migrate(23, dryRun: false, force: true);

        $this->assertTrue($result['success']);
    }

    public function testMigrateNonExistentPost(): void
    {
        $result = $this->migrator->migrate(999);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testRollback(): void
    {
        $original = '<p>Original content</p>';
        $this->createPost(30, $original);

        // Migrate first
        $this->migrator->migrate(30, dryRun: false);

        // Then rollback
        $result = $this->migrator->rollback(30);

        $this->assertTrue($result['success']);

        // Verify content was restored
        global $wp_test_posts;
        $this->assertEquals($original, $wp_test_posts[30]->post_content);

        // Verify backup was removed
        global $wp_test_meta;
        $this->assertArrayNotHasKey('_wp_djot_original_content', $wp_test_meta[30] ?? []);
    }

    public function testRollbackNoBackup(): void
    {
        $this->createPost(31, 'Some content');

        $result = $this->migrator->rollback(31);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No backup', $result['message']);
    }

    public function testDetectsVariousHtmlTags(): void
    {
        $htmlCases = [
            '<div>content</div>',
            '<span class="test">text</span>',
            '<a href="url">link</a>',
            '<img src="image.jpg">',
            '<h1>Heading</h1>',
            '<ul><li>item</li></ul>',
            '<table><tr><td>cell</td></tr></table>',
            '<strong>bold</strong>',
            '<em>italic</em>',
            '<br>',
            '<hr>',
        ];

        foreach ($htmlCases as $i => $html) {
            $this->createPost(100 + $i, $html);
            $result = $this->migrator->analyze(100 + $i);
            $this->assertTrue($result['has_html'], "Failed to detect HTML in: {$html}");
        }
    }

    public function testDetectsVariousMarkdownPatterns(): void
    {
        $markdownCases = [
            '# Heading',
            '## Subheading',
            '**bold text**',
            '*italic text*',
            '[link text](http://example.com)',
            '- list item',
            '* list item',
            '1. numbered item',
            '```code block```',
            '> blockquote',
        ];

        foreach ($markdownCases as $i => $md) {
            $this->createPost(200 + $i, $md);
            $result = $this->migrator->analyze(200 + $i);
            $this->assertTrue($result['has_markdown'], "Failed to detect Markdown in: {$md}");
        }
    }

    public function testShortcodeProtection(): void
    {
        $content = '<p>Text before</p> [gallery ids="1,2,3"] <p>Text after</p>';
        $this->createPost(40, $content);

        $result = $this->migrator->migrate(40, dryRun: true);

        // Shortcode should be preserved in output
        $this->assertStringContainsString('[gallery ids="1,2,3"]', $result['converted']);
    }

    public function testSelfClosingShortcode(): void
    {
        $this->createPost(41, 'Text [br/] more text');

        $result = $this->migrator->analyze(41);

        $this->assertTrue($result['has_shortcodes']);
        $this->assertContains('br', $result['shortcodes']);
    }

    public function testEnclosingShortcode(): void
    {
        $this->createPost(42, '[caption]Image caption here[/caption]');

        $result = $this->migrator->analyze(42);

        $this->assertTrue($result['has_shortcodes']);
        $this->assertContains('caption', $result['shortcodes']);
    }

    public function testMixedHtmlAndMarkdown(): void
    {
        // Content with both HTML and Markdown
        $content = "<div class=\"wrapper\">\n\n# Heading\n\nThis is **bold** and *italic*.\n\n</div>";
        $this->createPost(50, $content);

        $result = $this->migrator->analyze(50);

        $this->assertTrue($result['has_html']);
        $this->assertTrue($result['has_markdown']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testMixedContentMigration(): void
    {
        // Content with both HTML and Markdown
        $content = "<p>HTML paragraph with **markdown bold**</p>";
        $this->createPost(51, $content);

        $result = $this->migrator->migrate(51, dryRun: true);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['converted']);
    }

    public function testPreservesNewlinesInHtmlMigration(): void
    {
        $content = "<p>Paragraph 1</p>\n\n<p>Paragraph 2</p>\n\n<p>Paragraph 3</p>";
        $this->createPost(60, $content);

        $result = $this->migrator->migrate(60, dryRun: true);

        $this->assertTrue($result['success']);
        // Should have newlines between converted paragraphs
        $this->assertStringContainsString("\n", $result['converted']);
    }

    public function testPreservesNewlinesInMarkdownMigration(): void
    {
        $content = "# Heading\n\nParagraph 1\n\nParagraph 2";
        $this->createPost(61, $content);

        $result = $this->migrator->migrate(61, dryRun: true);

        $this->assertTrue($result['success']);
        // Converted content should preserve structure
        $this->assertStringContainsString("\n", $result['converted']);
    }

    public function testPreservesCodeBlockNewlinesInMigration(): void
    {
        $content = "```php\nline1\nline2\nline3\n```";
        $this->createPost(62, $content);

        $result = $this->migrator->migrate(62, dryRun: true);

        $this->assertTrue($result['success']);
        // Code block content should preserve internal newlines
        $converted = $result['converted'];
        $this->assertStringContainsString('line1', $converted);
        $this->assertStringContainsString('line2', $converted);
        $this->assertStringContainsString('line3', $converted);
    }

    public function testPreservesListNewlinesInMigration(): void
    {
        $content = "<ul>\n<li>Item 1</li>\n<li>Item 2</li>\n<li>Item 3</li>\n</ul>";
        $this->createPost(63, $content);

        $result = $this->migrator->migrate(63, dryRun: true);

        $this->assertTrue($result['success']);
        // Should convert to Djot list format
        $converted = $result['converted'];
        $this->assertStringContainsString('Item 1', $converted);
        $this->assertStringContainsString('Item 2', $converted);
        $this->assertStringContainsString('Item 3', $converted);
    }

    private function createPost(int $id, string $content): void
    {
        global $wp_test_posts;
        $wp_test_posts[$id] = (object)[
            'ID' => $id,
            'post_content' => $content,
            'post_type' => 'post',
            'post_status' => 'publish',
        ];
    }

    private function createComment(int $id, string $content): void
    {
        global $wp_test_comments;
        $wp_test_comments[$id] = (object)[
            'comment_ID' => $id,
            'comment_content' => $content,
            'comment_approved' => '1',
        ];
    }

    // Comment migration tests

    public function testAnalyzeCommentDetectsHtml(): void
    {
        $this->createComment(1, '<p>This is <strong>HTML</strong> comment</p>');

        $result = $this->migrator->analyzeComment(1);

        $this->assertTrue($result['has_html']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testAnalyzeCommentDetectsMarkdown(): void
    {
        $this->createComment(2, "**bold** and *italic*");

        $result = $this->migrator->analyzeComment(2);

        $this->assertTrue($result['has_markdown']);
        $this->assertTrue($result['can_auto_migrate']);
    }

    public function testAnalyzeCommentNonExistent(): void
    {
        $result = $this->migrator->analyzeComment(999);

        $this->assertFalse($result['can_auto_migrate']);
        $this->assertEquals('unknown', $result['complexity']);
    }

    public function testMigrateCommentDryRun(): void
    {
        $original = '<p>Comment with <strong>HTML</strong></p>';
        $this->createComment(10, $original);

        $result = $this->migrator->migrateComment(10, dryRun: true);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Dry run', $result['message']);

        // Verify content was NOT changed
        global $wp_test_comments;
        $this->assertEquals($original, $wp_test_comments[10]->comment_content);
    }

    public function testMigrateCommentActual(): void
    {
        $original = '<p>Comment with <strong>HTML</strong></p>';
        $this->createComment(11, $original);

        $result = $this->migrator->migrateComment(11, dryRun: false);

        $this->assertTrue($result['success']);

        // Verify backup was created
        global $wp_test_comment_meta;
        $this->assertEquals($original, $wp_test_comment_meta[11]['_wp_djot_original_comment']);

        // Verify content was changed
        global $wp_test_comments;
        $this->assertNotEquals($original, $wp_test_comments[11]->comment_content);
    }

    public function testRollbackComment(): void
    {
        $original = '<p>Original comment</p>';
        $this->createComment(20, $original);

        // Migrate first
        $this->migrator->migrateComment(20, dryRun: false);

        // Then rollback
        $result = $this->migrator->rollbackComment(20);

        $this->assertTrue($result['success']);

        // Verify content was restored
        global $wp_test_comments;
        $this->assertEquals($original, $wp_test_comments[20]->comment_content);

        // Verify backup was removed
        global $wp_test_comment_meta;
        $this->assertArrayNotHasKey('_wp_djot_original_comment', $wp_test_comment_meta[20] ?? []);
    }

    public function testRollbackCommentNoBackup(): void
    {
        $this->createComment(21, 'Some comment');

        $result = $this->migrator->rollbackComment(21);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No backup', $result['message']);
    }
}
