<?php

use App\Core\SocialEmbed;

test("SocialEmbed::transform преобразует ссылки Telegram, Instagram и YouTube в интерактивные блоки", function () {
    // 1. Telegram посты
    $tg = "<p>https://t.me/artstudio_uz/123</p>";
    $outTg = SocialEmbed::transform($tg);
    assert_contains("social-embed--telegram", $outTg);
    assert_contains("data-telegram-post=\"artstudio_uz/123\"", $outTg);

    // 2. Instagram посты и Reels
    $ig = "<p>https://www.instagram.com/p/C_abc123/</p>";
    $outIg = SocialEmbed::transform($ig);
    assert_contains("social-embed--instagram", $outIg);
    assert_contains("Смотреть в Instagram", $outIg);

    // 3. YouTube видео
    $yt = "<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>";
    $outYt = SocialEmbed::transform($yt);
    assert_contains("social-embed--youtube", $outYt);
    assert_contains("youtube-nocookie.com/embed/dQw4w9WgXcQ", $outYt);
});

