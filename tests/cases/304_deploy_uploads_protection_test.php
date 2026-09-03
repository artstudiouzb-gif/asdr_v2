<?php

declare(strict_types=1);

use App\Core\Media;

test('Правила .gitignore и deploy.yml полностью защищают все файлы и подкаталоги загрузок', function () {
    $gitignore = (string) file_get_contents(APP_ROOT . '/.gitignore');
    $deployYml = (string) file_get_contents(APP_ROOT . '/.github/workflows/deploy.yml');

    foreach ([$gitignore, $deployYml] as $content) {
        assert_contains('/public/uploads/*', $content);
        assert_contains('/public/uploads/**/*', $content);
        assert_contains('/public/uploads/public/*', $content);
        assert_contains('/public/uploads/public/**/*', $content);
        assert_contains('!/public/uploads/public/.gitkeep', $content);
        assert_contains('!/public/uploads/public/index.html', $content);
    }
});

test('Корневой .htaccess содержит fallback на WebP для LiteSpeed и Hostinger', function () {
    $rootHtaccess = (string) file_get_contents(APP_ROOT . '/.htaccess');
    assert_contains('RewriteCond %{DOCUMENT_ROOT}/public/uploads/public/$1.webp -f', $rootHtaccess);
    assert_contains('RewriteRule ^(?:public/)?uploads/public/(.+)\.(jpe?g|png)$ public/uploads/public/$1.webp [T=image/webp,L]', $rootHtaccess);

    $publicHtaccess = (string) file_get_contents(APP_ROOT . '/public/.htaccess');
    assert_contains('RewriteCond %{REQUEST_URI} ^(/public)?/uploads/public/ [NC]', $publicHtaccess);
});

test('Media::servable открывает права закрытого родительского каталога (0755) и файла (0644)', function () {
    $dir = sys_get_temp_dir() . '/servable-test-' . bin2hex(random_bytes(5));
    mkdir($dir . '/sub', 0700, true);
    $file = $dir . '/sub/test.jpg';
    file_put_contents($file, 'fake image data');
    chmod($file, 0600);

    // Вызываем скрытый метод servable через Reflection
    $ref = new ReflectionClass(Media::class);
    $servable = $ref->getMethod('servable');

    $res = $servable->invoke(null, $file);
    assert_true($res, 'Файл стал servable');

    if (PHP_OS_FAMILY !== 'Windows') {
        assert_same(0755, fileperms($dir . '/sub') & 0777, 'Каталог открыт на 0755');
        assert_same(0644, fileperms($file) & 0777, 'Файл открыт на 0644');
    }

    unlink($file);
    rmdir($dir . '/sub');
    rmdir($dir);
});

test('Media::resolveExistingMediaUrl подменяет отсутствующий jpg на существующий webp', function () {
    $root = sys_get_temp_dir() . '/mediaurl-test-' . bin2hex(random_bytes(5));
    mkdir($root . '/sub', 0755, true);
    $webpPath = $root . '/sub/photo.webp';
    file_put_contents($webpPath, 'webp payload');

    $origUploads = \App\Core\Config::get('paths.public_uploads');
    $origUrl = \App\Core\Config::get('paths.public_uploads_url');

    try {
        \App\Core\Config::set('paths.public_uploads', $root);
        \App\Core\Config::set('paths.public_uploads_url', '/uploads/public');

        $resolved = Media::resolveExistingMediaUrl('/uploads/public/sub/photo.jpg');
        assert_same('/uploads/public/sub/photo.webp', $resolved, 'JPG успешно заменён на WebP');
    } finally {
        \App\Core\Config::set('paths.public_uploads', $origUploads);
        \App\Core\Config::set('paths.public_uploads_url', $origUrl);
        @unlink($webpPath);
        @rmdir($root . '/sub');
        @rmdir($root);
    }
});
