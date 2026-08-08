<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];
?>
<div class="block-persons">
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-persons__empty"><?= htmlspecialchars(t('Карточки ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="persons-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $photo = trim((string) ($item['photo'] ?? ''));
                $name = trim((string) ($item['name'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));
                $vacant = $photo === '' && $name === '';
                ?>
                <div class="person-card<?= $vacant ? ' person-card--vacant' : '' ?>">
                    <?php if ($photo !== ''): ?>
                        <?= \App\Core\Media::picture($photo, $name !== '' ? $name : t('Фото'), null, null, 'person-card__img', true, '(max-width: 700px) 100vw, 25vw', false, 'person-card__photo') ?>
                    <?php else: ?>
                        <span class="person-card__placeholder">
                            <?= \App\Core\Icon::render('user', 34, 'person-card__placeholder-icon', 1.5) ?>
                            <?php if ($vacant): ?>
                                <?php // Подпись только у по-настоящему пустой карточки: раньше её ?>
                                <?php // получал и руководитель, у которого просто нет фотографии. ?>
                                <span class="person-card__vacant"><?= htmlspecialchars(t('Вакантно'), ENT_QUOTES) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="person-card__body">
                        <?php if ($name !== ''): ?><span class="person-card__name"><?= htmlspecialchars($name, ENT_QUOTES) ?></span><?php endif; ?>
                        <?php if (!empty($item['role'])): ?><span class="person-card__role"><?= htmlspecialchars((string) $item['role'], ENT_QUOTES) ?></span><?php endif; ?>
                        <?php if ($url !== ''): ?><a class="person-card__more" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"><?= htmlspecialchars(t('Подробнее'), ENT_QUOTES) ?> →</a><?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
