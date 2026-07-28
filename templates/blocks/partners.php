<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
?>
<div class="block-partners">
    <?php if ($title !== ''): ?><h2 class="block-partners__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if (!empty($items)): ?>
        <div class="block-partners__grid">
            <?php foreach ($items as $p): ?>
                <?php
                $logo = trim((string) ($p['logo'] ?? ''));
                $nameRaw = (string) ($p['name'] ?? '');
                $name = htmlspecialchars($nameRaw, ENT_QUOTES);
                $url = (string) ($p['url'] ?? '');
                if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                    $url = '';
                }
                // Без логотипа показываем название текстом: пустой <img src="">
                // — это битая картинка, а имя партнёра было видно только во
                // всплывающей подсказке.
                $img = $logo !== ''
                    ? \App\Core\Media::picture($logo, $nameRaw, null, null, 'block-partners__logo', true, '180px')
                    : '<span class="block-partners__name">' . ($name !== '' ? $name : 'Партнёр') . '</span>';
                ?>
                <?php if ($url !== ''): ?>
                    <a class="block-partners__item" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" rel="noopener" title="<?= $name ?>"><?= $img ?></a>
                <?php else: ?>
                    <span class="block-partners__item" title="<?= $name ?>"><?= $img ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
