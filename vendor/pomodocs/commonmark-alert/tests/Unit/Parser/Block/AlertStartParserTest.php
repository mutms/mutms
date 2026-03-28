<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use PomoDocs\CommonMark\Alert\Node\Block\Alert;
use PomoDocs\CommonMark\Alert\Parser\Block\AlertStartParser;

it('recognizes a NOTE block', function () {
    $cursor = new Cursor('> [!NOTE]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->not->toBeNull();

    $parsers = $start->getBlockParsers();
    $this->assertCount(1, $parsers);

    $block = null;
    foreach ($parsers as $parser) {
        $block = $parser->getBlock();
    }

    expect($block)->toBeInstanceOf(Alert::class)
        ->and($block->getType())->toBe('note');
});

it('recognizes a TIP block', function () {
    $cursor = new Cursor('> [!TIP]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->not->toBeNull();

    $parsers = $start->getBlockParsers();
    $this->assertCount(1, $parsers);

    $block = null;
    foreach ($parsers as $parser) {
        $block = $parser->getBlock();
    }

    expect($block)->toBeInstanceOf(Alert::class)
        ->and($block->getType())->toBe('tip');
});

it('recognizes a IMPORTANT block', function () {
    $cursor = new Cursor('> [!IMPORTANT]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->not->toBeNull();

    $parsers = $start->getBlockParsers();
    $this->assertCount(1, $parsers);

    $block = null;
    foreach ($parsers as $parser) {
        $block = $parser->getBlock();
    }

    expect($block)->toBeInstanceOf(Alert::class)
        ->and($block->getType())->toBe('important');
});

it('recognizes a WARNING block', function () {
    $cursor = new Cursor('> [!WARNING]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->not->toBeNull();

    $parsers = $start->getBlockParsers();
    $this->assertCount(1, $parsers);

    $block = null;
    foreach ($parsers as $parser) {
        $block = $parser->getBlock();
    }

    expect($block)->toBeInstanceOf(Alert::class)
        ->and($block->getType())->toBe('warning');
});

it('recognizes a CAUTION block', function () {
    $cursor = new Cursor('> [!CAUTION]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->not->toBeNull();

    $parsers = $start->getBlockParsers();
    $this->assertCount(1, $parsers);

    $block = null;
    foreach ($parsers as $parser) {
        $block = $parser->getBlock();
    }

    expect($block)->toBeInstanceOf(Alert::class)
        ->and($block->getType())->toBe('caution');
});

it('recognizes a wrong indentation', function () {
    $cursor = new Cursor('  [!NOTE]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->toBeNull();
});

it('recognizes a wrong start block', function () {
    $cursor = new Cursor('! [!NOTE]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->toBeNull();
});

it('recognizes a wrong title', function () {
    $cursor = new Cursor('> [!WRONG]');

    $parser = new AlertStartParser();
    $start = $parser->tryStart($cursor, $this->createMock(MarkdownParserStateInterface::class));

    expect($start)->toBeNull();
});
