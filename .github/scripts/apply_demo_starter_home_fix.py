from pathlib import Path

seeder = Path('app/Core/DemoSeeder.php')
source = seeder.read_text(encoding='utf-8')

marker = '    private static function isUntouchedStarterHome(PDO $pdo, int $pageId): bool\n'
if marker not in source:
    raise SystemExit('isUntouchedStarterHome marker not found')

helper = '''    /**
     * Canonical form for decoded JSON: object key order is irrelevant, while
     * list order and scalar types remain strict. MySQL JSON normalizes object
     * keys, so direct PHP array identity can reject untouched starter data.
     */
    private static function canonicalJsonValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(static fn (mixed $item): mixed => self::canonicalJsonValue($item), $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalJsonValue($item);
        }

        return $value;
    }

'''
source = source.replace(marker, helper + marker, 1)

old = '                || $data !== $expected[$i][2]) {'
new = '                || self::canonicalJsonValue($data) !== self::canonicalJsonValue($expected[$i][2])) {'
if source.count(old) != 1:
    raise SystemExit(f'unexpected strict comparison occurrences: {source.count(old)}')
source = source.replace(old, new, 1)
seeder.write_text(source, encoding='utf-8')

test_file = Path('tests/cases/69_demo_home_fixture_test.php')
tests = test_file.read_text(encoding='utf-8')
test_marker = "test('Демо: повторный запуск не удаляет страницы и не дополняет настроенное меню', function () {\n"
if test_marker not in tests:
    raise SystemExit('demo test marker not found')

regression = r'''test('Демо: сравнение стартовой главной не зависит от порядка JSON-ключей', function () {
    $method = new ReflectionMethod(\App\Core\DemoSeeder::class, 'canonicalJsonValue');

    $fromMysql = [
        '_spacing' => 'max',
        'button_text' => 'Последние новости',
        'button_url' => '/news',
        'text' => 'Актуальная информация, документы, новости и услуги в одном месте.',
        'title' => 'Официальный сайт организации',
    ];
    $fixtureOrder = [
        'title' => 'Официальный сайт организации',
        'text' => 'Актуальная информация, документы, новости и услуги в одном месте.',
        'button_text' => 'Последние новости',
        'button_url' => '/news',
        '_spacing' => 'max',
    ];

    assert_same(
        $method->invoke(null, $fixtureOrder),
        $method->invoke(null, $fromMysql),
        'порядок ключей JSON-объекта не влияет на определение нетронутой главной'
    );
    assert_true(
        $method->invoke(null, ['limit' => 3]) !== $method->invoke(null, ['limit' => '3']),
        'типы JSON-значений сравниваются строго'
    );

    $seeder = (string) file_get_contents(APP_ROOT . '/app/Core/DemoSeeder.php');
    assert_contains('self::canonicalJsonValue($data)', $seeder);
    assert_not_contains('|| $data !== $expected[$i][2])', $seeder);
});

'''
tests = tests.replace(test_marker, regression + test_marker, 1)
test_file.write_text(tests, encoding='utf-8')

Path('.github/workflows/apply-demo-starter-home-fix.yml').unlink()
Path('.github/scripts/apply_demo_starter_home_fix.py').unlink()
