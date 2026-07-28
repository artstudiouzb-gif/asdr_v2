<?php
/** @var array $data */
$title = $data['title'] ?? '';
$members = $data['members'] ?? [];
?>
<div class="block-team">
    <?php if ($title !== ''): ?><h2 class="block-team__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if (empty($members)): ?>
        <p class="block-team__empty">Раздел команды пока пуст.</p>
    <?php else: ?>
        <div class="block-team__grid">
            <?php foreach ($members as $m): ?>
                <div class="team-card">
                    <?php if (!empty($m['photo'])): ?>
                        <?= \App\Core\Media::picture((string) $m['photo'], (string) ($m['name'] ?? ''), null, null, 'team-card__photo', true, '(max-width: 700px) 100vw, 25vw') ?>
                    <?php endif; ?>
                    <div class="team-card__name"><?= htmlspecialchars($m['name'] ?? '', ENT_QUOTES) ?></div>
                    <?php if (!empty($m['position'])): ?>
                        <div class="team-card__position"><?= htmlspecialchars($m['position'], ENT_QUOTES) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
