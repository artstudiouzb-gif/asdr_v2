<?php
/** @var array $data */
$career = $data['career'] ?? [];
$edu = $data['edu_items'] ?? [];
$extraTitle = trim((string) ($data['extra_title'] ?? ''));
$extraText = trim((string) ($data['extra_text'] ?? ''));
$quote = trim((string) ($data['quote_text'] ?? ''));
?>
<div class="block-bio">
    <div class="bio-main">
        <?php if (!empty($data['bio_title'])): ?><h2 class="bio__title"><?= htmlspecialchars((string) $data['bio_title'], ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if (!empty($data['bio_text'])): ?><div class="bio__text"><?= nl2br(htmlspecialchars((string) $data['bio_text'], ENT_QUOTES)) ?></div><?php endif; ?>
        <?php if (!empty($career)): ?>
            <ol class="bio-career">
                <?php foreach ($career as $row): ?>
                    <li class="bio-career__item">
                        <span class="bio-career__years"><?= htmlspecialchars((string) ($row['years'] ?? ''), ENT_QUOTES) ?></span>
                        <span class="bio-career__text"><?= nl2br(htmlspecialchars((string) ($row['text'] ?? ''), ENT_QUOTES)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
    <div class="bio-side">
        <div class="bio-edu">
            <?php if (!empty($data['edu_title'])): ?><h2 class="bio__title"><?= htmlspecialchars((string) $data['edu_title'], ENT_QUOTES) ?></h2><?php endif; ?>
            <?php if (!empty($edu)): ?>
                <ol class="bio-edu__list">
                    <?php foreach ($edu as $row): ?>
                        <li class="bio-edu__item">
                            <span class="bio-edu__years"><?= htmlspecialchars((string) ($row['years'] ?? ''), ENT_QUOTES) ?></span>
                            <span class="bio-edu__degree"><?= htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES) ?></span>
                            <?php if (!empty($row['org'])): ?><span class="bio-edu__org"><?= htmlspecialchars((string) $row['org'], ENT_QUOTES) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
            <?php if ($extraTitle !== '' || $extraText !== ''): ?>
                <div class="bio-extra">
                    <?php if ($extraTitle !== ''): ?><h3 class="bio-extra__title"><?= htmlspecialchars($extraTitle, ENT_QUOTES) ?></h3><?php endif; ?>
                    <?php if ($extraText !== ''): ?>
                        <ul class="bio-extra__list">
                            <?php foreach (preg_split('/\r\n|\r|\n/', $extraText) ?: [] as $line): ?>
                                <?php if (trim($line) === '') { continue; } ?>
                                <li><?= htmlspecialchars(trim($line), ENT_QUOTES) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($quote !== ''): ?>
            <figure class="bio-quote">
                <span class="bio-quote__mark">“</span>
                <blockquote class="bio-quote__text"><?= htmlspecialchars($quote, ENT_QUOTES) ?></blockquote>
                <?php if (!empty($data['quote_author'])): ?><figcaption class="bio-quote__author">— <?= htmlspecialchars((string) $data['quote_author'], ENT_QUOTES) ?></figcaption><?php endif; ?>
            </figure>
        <?php endif; ?>
    </div>
</div>
