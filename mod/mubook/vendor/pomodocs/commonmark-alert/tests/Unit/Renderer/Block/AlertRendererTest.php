<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

use League\CommonMark\Environment\Environment;
use PomoDocs\CommonMark\Alert\AlertExtension;
use PomoDocs\CommonMark\Alert\Renderer\Block\AlertRenderer;

it('sets the configuration', function () {
    $config = Environment::createDefaultConfiguration();
    $extension = new AlertExtension();
    $extension->configureSchema($config);
    $configuration = $config->reader();

    $renderer = new AlertRenderer();
    $renderer->setConfiguration($configuration);

    $property = new \ReflectionProperty($renderer, 'config');
    $property->setAccessible(true);

    expect($configuration)->toBe($property->getValue($renderer));
});
