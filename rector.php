<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;

return RectorConfig::configure()
    ->withBootstrapFiles([
        __DIR__ . '/rector-bootstrap.php',
    ])
    ->withPaths([
        __DIR__ . '/vendor',
    ])
    ->withSkip([
        // Skip our own code - only downgrade vendor
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        DowngradeLevelSetList::DOWN_TO_PHP_81,
    ]);
