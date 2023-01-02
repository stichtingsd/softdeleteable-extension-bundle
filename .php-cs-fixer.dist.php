<?php

declare(strict_types=1);

/*
 * This file is a part of the StichtingSD/SoftDeleteableExtensionBundle
 * (c) StichtingSD <info@stichtingsd.nl> https://stichtingsd.nl
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$finder = PhpCsFixer\Finder::create()->in([
    __DIR__.'/src',
    __DIR__.'/tests',
])->append([__FILE__]);

$header = <<<'HEADER'
    This file is a part of the StichtingSD/SoftDeleteableExtensionBundle
    (c) StichtingSD <info@stichtingsd.nl> https://stichtingsd.nl
    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    HEADER;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP81Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PhpCsFixer:risky' => true,
        '@PhpCsFixer' => true,
        'php_unit_test_class_requires_covers' => false,
        '@Symfony' => true,
    ])
    ->setFinder($finder)->setRiskyAllowed(true)->setUsingCache(true);
