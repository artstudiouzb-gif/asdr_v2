<?php

declare(strict_types=1);

test('Роут /admin/logout обрабатывает как GET так и POST запросы без 404 ошибки', function (): void {
    $routes = (string) file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
    $controller = (string) file_get_contents(
        dirname(__DIR__, 2) . '/app/Controllers/Admin/AuthController.php'
    );

    assert_contains(
        "\$router->get('/admin/logout', [AdminAuthController::class, 'logout']);",
        $routes,
        'GET-маршрут зарегистрирован'
    );
    assert_contains(
        "\$router->post('/admin/logout', [AdminAuthController::class, 'logout']);",
        $routes,
        'POST-маршрут зарегистрирован'
    );
    assert_contains("if (\$_SERVER['REQUEST_METHOD'] === 'POST')", $controller);
    assert_contains('Csrf::verifyRequest();', $controller, 'POST-выход защищён CSRF');
    assert_contains('Auth::logout();', $controller, 'сессия очищается');
    assert_contains("header('Location: /admin/login');", $controller, 'после выхода есть редирект');
});
