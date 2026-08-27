<?php

declare(strict_types=1);

namespace WpDjot\Test\TestCase;

use PHPUnit\Framework\TestCase;
use WpDjot\Plugin;

/**
 * What the plugin registers, and which posts it will rewrite.
 *
 * The excerpt regression lived in registerFilters() rather than in the filter
 * body: the hook was never added when both content options were off, so a
 * block post's archive excerpt fell through to core, which drops a dynamic
 * block and leaves nothing. A test that only called filterExcerpt() would have
 * stayed green through it, which is why these assert the registry.
 */
class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        wp_test_reset_filters();
        $GLOBALS['wp_test_options'] = [];
        $GLOBALS['wp_test_posts'] = [];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function pluginWith(array $options): Plugin
    {
        $GLOBALS['wp_test_options']['wpdjot_settings'] = $options;
        $plugin = new Plugin();
        $plugin->init();

        return $plugin;
    }

    private function registersExcerptFilter(Plugin $plugin): bool
    {
        foreach (wp_test_callbacks('get_the_excerpt') as $callback) {
            if (is_array($callback) && $callback[0] === $plugin && $callback[1] === 'filterExcerpt') {
                return true;
            }
        }

        return false;
    }

    public function testExcerptFilterIsRegisteredWhenContentFiltersAreOn(): void
    {
        $plugin = $this->pluginWith(['enable_posts' => true, 'enable_pages' => false]);

        $this->assertTrue($this->registersExcerptFilter($plugin));
    }

    /**
     * The regression. A block renders through its own callback whatever the
     * content options say, so its excerpt has to be rescued whatever they say
     * too.
     */
    public function testExcerptFilterIsRegisteredWhenContentFiltersAreOff(): void
    {
        $plugin = $this->pluginWith(['enable_posts' => false, 'enable_pages' => false]);

        $this->assertTrue(
            $this->registersExcerptFilter($plugin),
            'a block post gets no excerpt at all when this hook is missing',
        );
    }

    public function testContentFilterStaysGatedOnTheOptions(): void
    {
        $plugin = $this->pluginWith(['enable_posts' => false, 'enable_pages' => false]);

        $registered = false;
        foreach (wp_test_callbacks('the_content') as $callback) {
            if (is_array($callback) && $callback[0] === $plugin && $callback[1] === 'filterContent') {
                $registered = true;
            }
        }

        $this->assertFalse($registered, 'the content filter is what the options are for');
    }
}
