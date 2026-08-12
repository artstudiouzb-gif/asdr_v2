<?php
/** @var array $data */
/** @var int $blockId */
$projects = is_array($data['projects'] ?? null) ? $data['projects'] : [];
$columns = (int) ($data['columns'] ?? 3);
if ($columns < 2 || $columns > 4) {
    $columns = 3;
}
$head = \App\Core\SectionHead::render([
    'title' => (string) ($data['title'] ?? ''),
    'all_text' => (string) ($data['all_text'] ?? ''),
    'all_url' => (string) ($data['all_url'] ?? ''),
    // Легаси-класс сохраняем: на нём висят правила статичной темы.
    'title_class' => 'block-projects__title',
]);
// Два проекта в трёх колонках оставляли треть ряда пустой.
$templateCss = \App\Core\GridBalance::css($blockId, '.block-projects__grid', '.project-card', count($projects), $columns);
?>
<div class="block-projects">
    <?= $head ?>
    <?php if (empty($projects)): ?>
        <p class="block-projects__empty"><?= htmlspecialchars(t('Проекты пока не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="block-projects__grid">
            <?php foreach ($projects as $p): ?>
                <?php
                $projectUrl = !empty($p['slug'])
                    ? \App\Core\Locale::url('projects/' . (string) $p['slug'])
                    : '';
                $projectTag = $projectUrl !== '' ? 'a' : 'div';
                ?>
                <<?= $projectTag ?> class="project-card"<?= $projectUrl !== '' ? ' href="' . htmlspecialchars($projectUrl, ENT_QUOTES) . '"' : '' ?>>
                    <?php if (!empty($p['cover_image'])): ?>
                        <?= \App\Core\Media::picture((string) $p['cover_image'], (string) ($p['title'] ?? ''), null, null, 'project-card__cover', true, '(max-width: 720px) 100vw, ' . (int) round(100 / $columns) . 'vw') ?>
                    <?php endif; ?>
                    <div class="project-card__title"><?= htmlspecialchars($p['title'] ?? '', ENT_QUOTES) ?></div>
                    <?php if (!empty($p['description'])): ?>
                        <p class="project-card__desc"><?= htmlspecialchars(excerpt((string) $p['description'], 160), ENT_QUOTES) ?></p>
                    <?php endif; ?>
                </<?= $projectTag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
