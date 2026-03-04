<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;

return RectorConfig::configure()
    ->withBootstrapFiles([
        __DIR__ . '/rector-bootstrap.php',
    ])
    ->withPaths([
        // Only downgrade packages with PHP 8.2 readonly class syntax
        __DIR__ . '/vendor/torchlight/engine',
        __DIR__ . '/vendor/phiki/phiki',
        __DIR__ . '/vendor/nette/schema',
        __DIR__ . '/vendor/nette/utils',
    ])
    ->withSets([
        DowngradeLevelSetList::DOWN_TO_PHP_81,
    ]);
