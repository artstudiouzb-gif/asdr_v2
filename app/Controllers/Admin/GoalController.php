<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminListQuery;
use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\Goal;

/**
 * Раздел «Цели». Записей сотни, а полей у записи три (имя, включена, снимки),
 * поэтому список сделан постраничным с поиском по имени, а форма — короткой.
 */
final class GoalController
{
    public function index(): void
    {
        Auth::requireLogin();
        $filters = AdminListQuery::normalize($_GET, ['newest'], 'newest');
        $search = (string) $filters['q'];

        View::render('admin/goals/index', [
            'goals' => Goal::page((int) $filters['per_page'], (int) $filters['offset'], $search),
            'total' => Goal::countAll($search),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        View::render('admin/goals/form', ['goal' => null, 'images' => [], 'error' => null]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $name = $this->name();
        if ($name === '') {
            View::render('admin/goals/form', [
                'goal' => null,
                'images' => $this->images(),
                'error' => 'У цели должно быть имя — по нему она ищется в списке.',
            ]);
            return;
        }

        $id = Goal::create($name, !empty($_POST['is_active']));
        Goal::replaceImages($id, $this->images());
        Cache::flush();
        Flash::success('Цель добавлена.');
        header('Location: /admin/goals/' . $id . '/edit');
        exit;
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $goal = Goal::find((int) $id);
        if ($goal === null) {
            http_response_code(404);
            View::render('errors/404', []);
            return;
        }

        View::render('admin/goals/form', [
            'goal' => $goal,
            'images' => Goal::images((int) $id),
            'error' => null,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $goal = Goal::find((int) $id);
        if ($goal === null) {
            http_response_code(404);
            View::render('errors/404', []);
            return;
        }

        $name = $this->name();
        if ($name === '') {
            View::render('admin/goals/form', [
                'goal' => array_merge($goal, ['name' => '']),
                'images' => $this->images(),
                'error' => 'У цели должно быть имя — по нему она ищется в списке.',
            ]);
            return;
        }

        Goal::update((int) $id, $name, !empty($_POST['is_active']));
        Goal::replaceImages((int) $id, $this->images());
        Cache::flush();
        Flash::success('Цель сохранена.');
        header('Location: /admin/goals/' . (int) $id . '/edit');
        exit;
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        Goal::delete((int) $id);
        Cache::flush();
        Flash::success('Цель удалена.');
        header('Location: /admin/goals');
        exit;
    }

    private function name(): string
    {
        return mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 255);
    }

    /**
     * Снимки из репитера формы. Пустая строка адреса — удалённая строка, а не
     * кадр: такие пропускает уже модель, здесь важен только порядок.
     *
     * Поля названы slides[...] так же, как у блока «Слайдер» и у виджета:
     * имя с «image»/«photo» админский тест считает полем картинки без
     * медиапикера, а у соседнего поля подписи пикера и не должно быть.
     *
     * @return array<int, array{image: string, alt: string}>
     */
    private function images(): array
    {
        $images = [];
        foreach ((array) ($_POST['slides'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $images[] = [
                'image' => (string) ($row['image'] ?? ''),
                'alt' => (string) ($row['alt'] ?? ''),
            ];
        }

        return $images;
    }
}
