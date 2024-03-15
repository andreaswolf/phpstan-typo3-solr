<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withRules([
        NoUnusedImportsFixer::class,
        ArraySyntaxFixer::class,
        DeclareStrictTypesFixer::class,
    ])
    ->withPreparedSets(psr12: true)
    ->withPaths([
        __DIR__,
        __DIR__ . '/../src/',
        __DIR__ . '/../tests/',
    ]);
