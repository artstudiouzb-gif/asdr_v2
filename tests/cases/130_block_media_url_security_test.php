<?php

declare(strict_types=1);

use App\Controllers\Admin\BlockController;
use App\Core\BlockRenderer;
use App\Core\Media;

/**
 * @param array<string,mixed> $post
 * @return array<string,mixed>
 */
function collect_gallery_data(array $post): array
{
    $previousPost = $_POST;
    try {
        $_POST = $post;
        $method = new ReflectionMethod(BlockController::class, 'collectData');

        /** @var array<string,mixed> $data */
        $data = $method->invoke(new BlockController(), 'gallery', 'ru');

        return $data;
    } finally {
        $_POST = $previousPost;
    }
}

test('Gallery: при сохранении отбрасывает опасные схемы медиа-URL', function () {
    $data = collect_gallery_data([
        'title_field' => 'Галерея',
        'images' => [
            ['url' => 'javascript:alert(1)', 'caption' => 'JS'],
            ['url' => 'data:image/svg+xml,<svg></svg>', 'caption' => 'Data'],
            ['url' => 'mailto:editor@example.com', 'caption' => 'Mail'],
            ['url' => '/uploads/public/local.jpg', 'caption' => 'Local'],
            ['url' => 'https://cdn.example.com/remote.jpg', 'caption' => 'Remote'],
        ],
    ]);

    assert_same(2, count($data['images']));
    assert_same('/uploads/public/local.jpg', $data['images'][0]['url']);
    assert_same('https://cdn.example.com/remote.jpg', $data['images'][1]['url']);
});

test('Gallery: старые опасные JSON-данные не попадают в публичную разметку', function () {
    $rendered = BlockRenderer::render([
        'id' => 1300,
        'type' => 'gallery',
        'custom_css' => '',
        'data' => json_encode([
            'title' => 'Галерея',
            'images' => [
                ['url' => 'javascript:alert(1)', 'caption' => 'JS'],
                ['url' => '/uploads/public/safe.jpg', 'caption' => 'Safe'],
            ],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    assert_not_contains('javascript:', $rendered['html']);
    assert_contains('href="/uploads/public/safe.jpg"', $rendered['html']);
    assert_contains('src="/uploads/public/safe.jpg"', $rendered['html']);
});

test('Media: центральный рендерер не выводит URL опасных схем', function () {
    assert_same('', Media::picture('javascript:alert(1)', 'Bad'));
    assert_same('', Media::picture('data:image/svg+xml,<svg></svg>', 'Bad'));
    assert_same('', Media::preloadLink('mailto:editor@example.com'));
});
