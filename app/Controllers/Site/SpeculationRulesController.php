<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\SpeculationRules;

final class SpeculationRulesController
{
    public function show(): void
    {
        // Без этого MIME браузер правила не примет.
        header('Content-Type: application/speculationrules+json');
        header('Cache-Control: public, max-age=86400');
        echo SpeculationRules::json();
    }
}
