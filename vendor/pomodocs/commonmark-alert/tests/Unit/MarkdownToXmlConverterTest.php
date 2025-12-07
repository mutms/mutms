<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Tests\Unit;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Output\RenderedContentInterface;
use League\CommonMark\Xml\MarkdownToXmlConverter;
use PomoDocs\CommonMark\Alert\AlertExtension;

it("renders the markdown string into xml", function (string $md, string $xml) {
    $environment = new Environment();
    $environment->addExtension(new AlertExtension());
    $environment->addExtension(new CommonMarkCoreExtension());
    $converter = new MarkdownToXmlConverter($environment);

    $actual = $converter->convert($md);

    expect($actual)->toBeInstanceOf(RenderedContentInterface::class)
        ->and($actual->getContent())->toBe($xml);
})->with('markdownXml');
