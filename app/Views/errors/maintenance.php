<?php

use App\Models\Setting;

$siteName = Setting::get('site_name', 'ASDR');
$message = Setting::get('maintenance_message', 'Сайт временно закрыт на техническое обслуживание. Мы скоро вернёмся.');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName, ENT_QUOTES) ?> — техническое обслуживание</title>
<style>
    body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; text-align: center; padding: 90px 20px; color: #1a1a1a; background: #f4f5f7; }
    h1 { font-size: 30px; margin: 0 0 12px; }
    p { color: #666; max-width: 520px; margin: 0 auto; line-height: 1.6; }
    .icon { font-size: 56px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="icon">🛠️</div>
<h1><?= htmlspecialchars($siteName, ENT_QUOTES) ?></h1>
<p><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
</body>
</html>
