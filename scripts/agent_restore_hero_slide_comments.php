<?php

declare(strict_types=1);

function once(string $path, string $old, string $new): void
{
    $source = (string) file_get_contents($path);
    if (substr_count($source, $old) !== 1) {
        fwrite(STDERR, "Patch target not unique in {$path}: " . substr($old, 0, 80) . "\n");
        exit(2);
    }
    file_put_contents($path, str_replace($old, $new, $source));
}

$path = dirname(__DIR__) . '/app/Core/Hero/HeroSlideData.php';

// Keep main's documentation and touch only the data contract required by this feature.
once($path,
    "    public const CTA_STYLES = ['primary', 'secondary', 'ghost', 'link'];\n",
    "    public const CTA_STYLES = ['primary', 'secondary', 'ghost', 'link'];\n\n    /** Как отображать свою SVG/PNG-картинку внутри CTA. */\n    public const CTA_IMAGE_MODES = ['icon', 'fill', 'custom'];\n"
);

once($path,
    "            'cta_image' => '',\n            'cta_new_tab' => false,",
    "            'cta_image' => '',\n            'cta_image_mode' => 'icon',\n            'cta_image_width' => 80,\n            'cta_new_tab' => false,"
);
once($path,
    "            'cta2_image' => '',\n            'cta2_new_tab' => false,",
    "            'cta2_image' => '',\n            'cta2_image_mode' => 'icon',\n            'cta2_image_width' => 80,\n            'cta2_new_tab' => false,"
);
once($path,
    "            'art_size' => 'medium',\n\n            // --- Показ ---",
    "            'art_size' => 'medium',\n            'art_width' => 320,\n\n            // --- Показ ---"
);

once($path,
    "            'cta_image' => BlockDataInput::safeMedia(\$input['cta_image'] ?? ''),\n            'cta_new_tab' => !empty(\$input['cta_new_tab']),",
    "            'cta_image' => BlockDataInput::safeMedia(\$input['cta_image'] ?? ''),\n            'cta_image_mode' => BlockDataInput::enum(\$input, 'cta_image_mode', self::CTA_IMAGE_MODES, 'icon'),\n            'cta_image_width' => self::ranged(\$input['cta_image_width'] ?? null, 20, 400, 80),\n            'cta_new_tab' => !empty(\$input['cta_new_tab']),"
);
once($path,
    "            'cta2_image' => BlockDataInput::safeMedia(\$input['cta2_image'] ?? ''),\n            'cta2_new_tab' => !empty(\$input['cta2_new_tab']),",
    "            'cta2_image' => BlockDataInput::safeMedia(\$input['cta2_image'] ?? ''),\n            'cta2_image_mode' => BlockDataInput::enum(\$input, 'cta2_image_mode', self::CTA_IMAGE_MODES, 'icon'),\n            'cta2_image_width' => self::ranged(\$input['cta2_image_width'] ?? null, 20, 400, 80),\n            'cta2_new_tab' => !empty(\$input['cta2_new_tab']),"
);
once($path,
    "            'art_size' => BlockDataInput::enum(\$input, 'art_size', ['small', 'medium', 'large'], 'medium'),\n",
    "            'art_size' => BlockDataInput::enum(\$input, 'art_size', ['small', 'medium', 'large', 'custom'], 'medium'),\n            'art_width' => self::ranged(\$input['art_width'] ?? null, 40, 1200, 320),\n"
);

once($path,
    "            \$d[\$cta . '_image'] = BlockDataInput::safeMedia(\$d[\$cta . '_image'] ?? '');\n            \$d[\$cta . '_new_tab'] = !empty(\$d[\$cta . '_new_tab']);",
    "            \$d[\$cta . '_image'] = BlockDataInput::safeMedia(\$d[\$cta . '_image'] ?? '');\n            \$d[\$cta . '_image_mode'] = \$enum(\$d[\$cta . '_image_mode'] ?? '', self::CTA_IMAGE_MODES, 'icon');\n            \$d[\$cta . '_image_width'] = self::ranged(\$d[\$cta . '_image_width'] ?? null, 20, 400, 80);\n            \$d[\$cta . '_new_tab'] = !empty(\$d[\$cta . '_new_tab']);"
);
once($path,
    "        \$d['art_size'] = \$enum(\$d['art_size'] ?? '', ['small', 'medium', 'large'], 'medium');\n        \$d['duration'] = self::duration(\$d['duration'] ?? null);",
    "        \$d['art_size'] = \$enum(\$d['art_size'] ?? '', ['small', 'medium', 'large', 'custom'], 'medium');\n        \$d['art_width'] = self::ranged(\$d['art_width'] ?? null, 40, 1200, 320);\n        \$d['duration'] = self::duration(\$d['duration'] ?? null);"
);

@unlink(__FILE__);
@unlink(dirname(__DIR__) . '/.github/workflows/agent-restore-hero-slide.yml');
