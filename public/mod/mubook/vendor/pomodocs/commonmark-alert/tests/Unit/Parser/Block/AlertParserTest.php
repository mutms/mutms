<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Tests\Unit\Parser\Block;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use PomoDocs\CommonMark\Alert\Node\Block\Alert;
use PomoDocs\CommonMark\Alert\Parser\Block\AlertParser;

it('gets the correct type', function () {
    $parser = new AlertParser('warning');

    expect($parser->getBlock())->toBeInstanceOf(Alert::class)
        ->and($parser->getBlock()->getType())->toBe('warning');
});

it('checks if the block contains some other blocks', function () {
    expect((new AlertParser('tip'))->isContainer())->toBeTrue();
});

it('checks if the block can contain some othe blocks', function () {
    $parser = new AlertParser('tip');

    expect($parser->canContain($this->createMock(AbstractBlock::class)))->toBeTrue();
});

it('tries to continue parsing', function () {
    $cursor = new Cursor('> Lorem ipsum');
    $parser = new AlertParser('tip');

    $continue = $parser->tryContinue($cursor, $this->createMock(BlockContinueParserInterface::class));

    expect($continue)->toBeInstanceOf(BlockContinue::class);
});

it('tries to continue parsing a wrong block', function () {
    $cursor = new Cursor(' Lorem ipsum');
    $parser = new AlertParser('tip');

    $continue = $parser->tryContinue($cursor, $this->createMock(BlockContinueParserInterface::class));

    expect($continue)->toBeNull();
});
