<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\RateLimiter;
use App\Models\Subscriber;

/** Публичная подписка на email-дайджест новостей и отписка по токену. */
final class SubscribeController
{
    public function subscribe(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            Flash::error('Сессия устарела, обновите страницу и попробуйте снова.');
            $this->back();
        }

        // Анти-флуд: не более 5 подписок с одного IP за 10 минут.
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!RateLimiter::throttle('subscribe', $ip, 5, 10)) {
            Flash::error('Слишком много попыток. Пожалуйста, попробуйте позже.');
            $this->back();
        }

        // Honeypot: боту тихо показываем «успех».
        if (Csrf::isSpam()) {
            Flash::success('Вы подписаны на дайджест новостей.');
            $this->back();
        }

        $result = Subscriber::subscribe((string) ($_POST['email'] ?? ''));
        match ($result) {
            'ok' => Flash::success('Вы подписаны на дайджест новостей.'),
            'exists' => Flash::success('Этот адрес уже подписан на дайджест.'),
            default => Flash::error('Укажите корректный email.'),
        };
        $this->back();
    }

    public function unsubscribe(): void
    {
        if (Subscriber::unsubscribeByToken((string) ($_GET['token'] ?? ''))) {
            Flash::success('Вы отписаны от дайджеста. Спасибо, что были с нами!');
        } else {
            Flash::error('Ссылка отписки недействительна или уже использована.');
        }
        header('Location: /');
        exit;
    }

    private function back(): never
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $parts = parse_url($referer);
        $path = is_array($parts) ? (string) ($parts['path'] ?? '/') : '/';
        $refererHost = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $requestHost = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');
        if (!str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || ($refererHost !== '' && ($requestHost === '' || $refererHost !== $requestHost))) {
            $path = '/';
        } elseif (is_array($parts) && isset($parts['query'])) {
            $path .= '?' . $parts['query'];
        }
        header('Location: ' . $path);
        exit;
    }
}
