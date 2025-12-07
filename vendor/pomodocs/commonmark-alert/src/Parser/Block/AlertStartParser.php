<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Parser\Block;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class AlertStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '>') {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();

        $type = $cursor->match('/\[\!NOTE\]|\[\!TIP\]|\[\!IMPORTANT\]|\[\!WARNING\]|\[\!CAUTION\]/');

        if ($type === null) {
            return BlockStart::none();
        }

        $type = \strtolower(\trim($type, '[,!,]'));

        $cursor->advanceToNextNonSpaceOrTab();

        return BlockStart::of(new AlertParser($type))->at($cursor);
    }
}
