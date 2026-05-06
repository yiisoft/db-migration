<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withSkip([
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
        RemoveExtraParametersRector::class => [__DIR__ . '/src/Service/Generate/PhpRenderer.php'],
        StringClassNameToClassConstantRector::class => [__DIR__ . '/tests/Common/Service/AbstractMigrationServiceTest.php'],
    ])
    ->withoutParallel();
