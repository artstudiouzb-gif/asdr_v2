<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\News;
use App\Models\NewsTranslation;

test('Новости: публичные списки, рубрики и связанные материалы изолированы по языку', function (): void {
    ensure_test_db();

    $pdo = Database::pdo();
    $suffix = bin2hex(random_bytes(4));
    $ids = [];
    $insert = $pdo->prepare(
        "INSERT INTO news
            (title, slug, badge, excerpt, content, status, published_at, lang, translation_group_id, views, created_at)
         VALUES
            (:title, :slug, :badge, :excerpt, :content, :status, NOW(), :lang, :group_id, :views, NOW())"
    );

    try {
        $insert->execute([
            ':title' => 'RU independent ' . $suffix,
            ':slug' => 'news-lang-ru-' . $suffix,
            ':badge' => 'ISO-RU-' . $suffix,
            ':excerpt' => 'RU excerpt',
            ':content' => '<p>RU content</p>',
            ':status' => 'published',
            ':lang' => 'ru',
            ':group_id' => null,
            ':views' => 999999901,
        ]);
        $ruId = (int) $pdo->lastInsertId();
        $ids[] = $ruId;

        $insert->execute([
            ':title' => 'UZ independent ' . $suffix,
            ':slug' => 'news-lang-uz-' . $suffix,
            ':badge' => 'ISO-UZ-' . $suffix,
            ':excerpt' => 'UZ excerpt',
            ':content' => '<p>UZ content</p>',
            ':status' => 'published',
            ':lang' => 'uz',
            ':group_id' => $ruId,
            ':views' => 999999900,
        ]);
        $uzId = (int) $pdo->lastInsertId();
        $ids[] = $uzId;

        $insert->execute([
            ':title' => 'RU legacy ' . $suffix,
            ':slug' => 'news-lang-legacy-' . $suffix,
            ':badge' => 'LEGACY-RU-' . $suffix,
            ':excerpt' => 'Legacy RU excerpt',
            ':content' => '<p>Legacy RU content</p>',
            ':status' => 'published',
            ':lang' => 'ru',
            ':group_id' => null,
            ':views' => 999999899,
        ]);
        $legacyId = (int) $pdo->lastInsertId();
        $ids[] = $legacyId;
        NewsTranslation::upsert($legacyId, 'uz', [
            'title' => 'UZ legacy ' . $suffix,
            'badge' => 'LEGACY-UZ-' . $suffix,
            'content' => '<p>Legacy UZ content</p>',
        ]);

        $insert->execute([
            ':title' => 'RU shadowed ' . $suffix,
            ':slug' => 'news-lang-shadowed-' . $suffix,
            ':badge' => 'SHADOW-RU-' . $suffix,
            ':excerpt' => 'Shadow RU excerpt',
            ':content' => '<p>Shadow RU content</p>',
            ':status' => 'published',
            ':lang' => 'ru',
            ':group_id' => null,
            ':views' => 999999898,
        ]);
        $shadowRootId = (int) $pdo->lastInsertId();
        $ids[] = $shadowRootId;
        NewsTranslation::upsert($shadowRootId, 'uz', [
            'title' => 'UZ legacy must stay hidden ' . $suffix,
            'badge' => 'SHADOW-UZ-' . $suffix,
            'content' => '<p>Hidden legacy UZ content</p>',
        ]);

        $insert->execute([
            ':title' => 'UZ draft ' . $suffix,
            ':slug' => 'news-lang-draft-' . $suffix,
            ':badge' => 'DRAFT-UZ-' . $suffix,
            ':excerpt' => 'Draft UZ excerpt',
            ':content' => '<p>Draft UZ content</p>',
            ':status' => 'draft',
            ':lang' => 'uz',
            ':group_id' => $shadowRootId,
            ':views' => 999999897,
        ]);
        $draftUzId = (int) $pdo->lastInsertId();
        $ids[] = $draftUzId;

        $ruRows = News::published(10, 0, 'ru', 'ISO-RU-' . $suffix);
        assert_same([$ruId], array_map(static fn (array $row): int => (int) $row['id'], $ruRows));
        assert_same(1, News::publishedCount('ISO-RU-' . $suffix, 'ru'));
        assert_same(0, News::publishedCount('ISO-RU-' . $suffix, 'uz'));

        $uzRows = News::published(10, 0, 'uz', 'ISO-UZ-' . $suffix);
        assert_same([$uzId], array_map(static fn (array $row): int => (int) $row['id'], $uzRows));
        assert_same(1, News::publishedCount('ISO-UZ-' . $suffix, 'uz'));
        assert_same(0, News::publishedCount('ISO-UZ-' . $suffix, 'ru'));

        $legacyRows = News::published(10, 0, 'uz', 'LEGACY-UZ-' . $suffix);
        assert_same(1, count($legacyRows), 'legacy-перевод остаётся доступен до создания независимой записи');
        assert_same($legacyId, (int) $legacyRows[0]['id']);
        assert_same('UZ legacy ' . $suffix, (string) $legacyRows[0]['title']);
        assert_same(0, News::publishedCount('LEGACY-RU-' . $suffix, 'uz'));

        assert_same([], News::published(10, 0, 'uz', 'SHADOW-UZ-' . $suffix), 'legacy скрыт независимым черновиком');
        assert_same(0, News::publishedCount('SHADOW-UZ-' . $suffix, 'uz'));
        assert_same(0, News::publishedCount('DRAFT-UZ-' . $suffix, 'uz'));

        $ruBadges = News::distinctBadges('ru');
        $uzBadges = News::distinctBadges('uz');
        assert_true(in_array('ISO-RU-' . $suffix, $ruBadges, true));
        assert_false(in_array('ISO-UZ-' . $suffix, $ruBadges, true));
        assert_true(in_array('ISO-UZ-' . $suffix, $uzBadges, true));
        assert_true(in_array('LEGACY-UZ-' . $suffix, $uzBadges, true));
        assert_false(in_array('ISO-RU-' . $suffix, $uzBadges, true));
        assert_false(in_array('SHADOW-UZ-' . $suffix, $uzBadges, true));

        $resolvedUz = News::findPublishedBySlug('news-lang-ru-' . $suffix, 'uz');
        assert_true($resolvedUz !== null);
        assert_same($uzId, (int) $resolvedUz['id'], 'чужой slug переводится в запись нужного языка');
        assert_same('news-lang-uz-' . $suffix, (string) $resolvedUz['slug']);

        $shadowed = News::findPublishedBySlug('news-lang-shadowed-' . $suffix, 'uz');
        assert_true($shadowed !== null);
        assert_same($shadowRootId, (int) $shadowed['id']);
        assert_same('RU shadowed ' . $suffix, (string) $shadowed['title'], 'черновик не обходится legacy-переводом');
        assert_false(in_array('uz', News::availableLangs($shadowRootId), true));
        assert_true(in_array('uz', News::availableLangs($ruId), true));

        $relatedUz = News::related($uzId, 12, 'uz');
        $relatedIds = array_map(static fn (array $row): int => (int) $row['id'], $relatedUz);
        assert_true(in_array($legacyId, $relatedIds, true), 'связанные новости используют допустимый legacy-перевод');
        assert_false(in_array($ruId, $relatedIds, true), 'RU-копия текущей новости не дублируется');
        assert_false(in_array($shadowRootId, $relatedIds, true), 'legacy с независимым черновиком не показывается');
        assert_false(in_array($draftUzId, $relatedIds, true), 'черновик не показывается');

        $topRuIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            News::mostViewed(0, 20, 'ru')
        );
        $topUzIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            News::mostViewed(0, 20, 'uz')
        );
        assert_true(in_array($ruId, $topRuIds, true));
        assert_false(in_array($uzId, $topRuIds, true));
        assert_true(in_array($uzId, $topUzIds, true));
        assert_false(in_array($ruId, $topUzIds, true));

        $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/NewsController.php');
        assert_contains('News::distinctBadges($lang)', $controller);
        assert_contains('News::publishedCount($badge !== \'\' ? $badge : null, $lang)', $controller);
    } finally {
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM news WHERE id IN ({$placeholders})")->execute($ids);
        }
    }
});
