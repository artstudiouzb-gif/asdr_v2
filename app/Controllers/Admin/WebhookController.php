<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\UrlGuard;
use App\Core\View;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * Управление исходящими вебхуками (задача 136) — только супер-администратор.
 */
final class WebhookController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        // Фильтр журнала доставок по статусу (группа 2.2): all|failed|pending|sent.
        $status = (string) ($_GET['status'] ?? '');
        $status = in_array($status, ['failed', 'pending', 'sent'], true) ? $status : '';
        View::render('admin/webhooks/index', [
            'items' => Webhook::all(),
            'deliveries' => WebhookDelivery::recent(30, $status !== '' ? $status : null),
            'events' => Webhook::EVENTS,
            'statusFilter' => $status,
        ]);
    }

    public function store(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        [$event, $url, $secret, $active, $error] = $this->collect();
        if ($error !== null) {
            Flash::error($error);
        } else {
            Webhook::create($event, $url, $secret, $active);
            Flash::success('Вебхук добавлен.');
        }
        header('Location: /admin/webhooks');
        exit;
    }

    public function update(array $params): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        [$event, $url, $secret, $active, $error] = $this->collect();
        if ($error !== null) {
            Flash::error($error);
        } else {
            Webhook::update((int) $params['id'], $event, $url, $secret, $active);
            Flash::success('Вебхук обновлён.');
        }
        header('Location: /admin/webhooks');
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        Webhook::delete((int) $params['id']);
        Flash::success('Вебхук удалён.');
        header('Location: /admin/webhooks');
        exit;
    }

    /**
     * @return array{0:string,1:string,2:?string,3:bool,4:?string}
     */
    private function collect(): array
    {
        $event = (string) ($_POST['event_type'] ?? '');
        $url = trim((string) ($_POST['url'] ?? ''));
        $secret = trim((string) ($_POST['secret'] ?? ''));
        $active = !empty($_POST['is_active']);

        if (!in_array($event, Webhook::EVENTS, true)) {
            return ['', '', null, false, 'Неизвестный тип события.'];
        }
        // На сохранении проверяем схему; приватные адреса дополнительно
        // отсекаются на доставке (WebhookDispatcher::deliver).
        if ($url === '' || !UrlGuard::isSafeLink($url) || !preg_match('#^https?://#i', $url)) {
            return ['', '', null, false, 'Укажите корректный http(s)-URL.'];
        }

        return [$event, $url, $secret !== '' ? $secret : null, $active, null];
    }
}
