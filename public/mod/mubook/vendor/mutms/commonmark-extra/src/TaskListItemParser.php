<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;

final class TaskListItemParser implements InlineParserInterface {
    public function getMatchDefinition(): InlineParserMatch {
        return InlineParserMatch::oneOf('[ ]', '[x]');
    }

    public function parse(InlineParserContext $inlineContext): bool {
        $listItemText = $inlineContext->getContainer();

        if ($listItemText->hasChildren() || !$listItemText instanceof Paragraph) {
            return false;
        }

        $listItem = $listItemText->parent();
        if (!$listItem || !$listItem instanceof ListItem) {
            return false;
        }

        $cursor = $inlineContext->getCursor();
        $checkpoint = $cursor->saveState();
        $cursor->advanceBy(3);

        if (null === $cursor->getNextNonSpaceCharacter()) {
            $cursor->restoreState($checkpoint);
            return false;
        }

        $isCompleted = ('[x]' === $inlineContext->getFullMatch());

        $listItemText->appendChild(new TaskIconInline($isCompleted));

        return true;
    }
}
