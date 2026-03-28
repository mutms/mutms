<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Tests\Functional;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PomoDocs\CommonMark\Alert\AlertExtension;

it('renders the markdown string With default configuration', function (string $md, string $expected) {
    $environment = new Environment();
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownConverter($environment);

    expect((string) $converter->convert($md))->toBe($expected);
})->with('markdown');

it('renders the markdown string with configuration', function (string $md, string $expected) {
    $config = [
        'alert' => [
            'colors' => [
                'note' => 'primary',
                'tip' => 'info',
                'important' => 'success',
                'warning' => 'warning',
                'caution' => 'danger',
            ],
            'icons' => [
                'active' => true,
                'use_svg' => true,
            ],
        ],
    ];

    $environment = new Environment($config);
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownConverter($environment);

    expect((string) $converter->convert($md))->toBe($expected);
})->with('markdownConfiguration');

it('renders the markdown string with defined style sheet', function (string $md, string $expected) {
    $config = [
        'alert' => [
            'class_name' => 'admonition',
        ],
    ];

    $environment = new Environment($config);
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownConverter($environment);

    expect((string) $converter->convert($md))->toBe($expected);
})->with('markdownClass');

it('renders the markdown string with icons by name', function (string $md, string $expected) {
    $config = [
        'alert' => [
            'colors' => [
                'note' => 'primary',
                'tip' => 'info',
                'important' => 'success',
                'warning' => 'warning',
                'caution' => 'danger',
            ],
            'icons' => [
                'active' => true,
                'use_svg' => false,
            ],
        ],
    ];

    $environment = new Environment($config);
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownConverter($environment);

    $this->assertEquals($expected, (string) $converter->convert($md));
})->with('markdownIcons');

it('renders the markdown string with localized labels', function (string $md, string $expected) {
    $config = [
        'alert' => [
            'labels' => [
                'note' => 'Nota',
                'tip' => 'Consilium',
                'important' => 'Maximus',
                'warning' => 'Admonitus',
                'caution' => 'Cautela',
            ],
        ],
    ];

    $environment = new Environment($config);
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownConverter($environment);

    $this->assertEquals($expected, (string) $converter->convert($md));
})->with('markdownLocalized');
