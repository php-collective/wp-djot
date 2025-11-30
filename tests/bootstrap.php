<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Mock WordPress functions for testing
if (!function_exists('get_post')) {
    function get_post(int $postId): ?object
    {
        global $wp_test_posts;

        return $wp_test_posts[$postId] ?? null;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key, bool $single = false): mixed
    {
        global $wp_test_meta;

        $value = $wp_test_meta[$postId][$key] ?? null;

        return $single ? $value : [$value];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): bool
    {
        global $wp_test_meta;
        $wp_test_meta[$postId][$key] = $value;

        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta(int $postId, string $key): bool
    {
        global $wp_test_meta;
        unset($wp_test_meta[$postId][$key]);

        return true;
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post(array $postarr, bool $wpError = false): int
    {
        global $wp_test_posts;
        $id = $postarr['ID'];
        if (isset($wp_test_posts[$id])) {
            $wp_test_posts[$id]->post_content = $postarr['post_content'];
        }

        return $id;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $message;

        public function __construct(string $code = '', string $message = '')
        {
            $this->message = $message;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

if (!class_exists('WP_Query')) {
    class WP_Query
    {
        public array $posts = [];

        public function __construct(array $args = [])
        {
            global $wp_test_posts;
            $this->posts = array_values($wp_test_posts ?? []);
        }
    }
}

// Comment-related mock functions
if (!function_exists('get_comment')) {
    function get_comment(int $commentId): ?object
    {
        global $wp_test_comments;

        return $wp_test_comments[$commentId] ?? null;
    }
}

if (!function_exists('get_comment_meta')) {
    function get_comment_meta(int $commentId, string $key, bool $single = false): mixed
    {
        global $wp_test_comment_meta;

        $value = $wp_test_comment_meta[$commentId][$key] ?? null;

        return $single ? $value : [$value];
    }
}

if (!function_exists('update_comment_meta')) {
    function update_comment_meta(int $commentId, string $key, mixed $value): bool
    {
        global $wp_test_comment_meta;
        $wp_test_comment_meta[$commentId][$key] = $value;

        return true;
    }
}

if (!function_exists('delete_comment_meta')) {
    function delete_comment_meta(int $commentId, string $key): bool
    {
        global $wp_test_comment_meta;
        unset($wp_test_comment_meta[$commentId][$key]);

        return true;
    }
}

if (!function_exists('wp_update_comment')) {
    function wp_update_comment(array $commentarr, bool $wpError = false): int
    {
        global $wp_test_comments;
        $id = $commentarr['comment_ID'];
        if (isset($wp_test_comments[$id])) {
            $wp_test_comments[$id]->comment_content = $commentarr['comment_content'];
        }

        return $id;
    }
}

if (!function_exists('get_comments')) {
    function get_comments(array $args = []): array
    {
        global $wp_test_comments;

        return array_values($wp_test_comments ?? []);
    }
}
