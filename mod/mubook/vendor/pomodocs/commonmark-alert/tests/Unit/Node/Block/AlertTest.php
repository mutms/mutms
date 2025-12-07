<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

use PomoDocs\CommonMark\Alert\Node\Block\Alert;

it('tests constructor and getter', function () {
    expect((new Alert('warning'))->getType())->toBe('warning');
});

it('tests constructor with default values', function () {
    expect((new Alert())->getType())->toBe('note');
});
