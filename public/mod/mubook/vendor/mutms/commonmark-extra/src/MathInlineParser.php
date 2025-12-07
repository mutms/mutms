<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

final class MathInlineParser implements InlineParserInterface {
    public const MATH_REGEX = '\$`([^`]+?)`\$';

    public function getMatchDefinition(): InlineParserMatch {
        return InlineParserMatch::regex(self::MATH_REGEX);
    }

    public function parse(InlineParserContext $inlineContext): bool {
        $fullmatch = $inlineContext->getFullMatch();
        $matches = $inlineContext->getMatches();

        $inlineContext->getContainer()->appendChild(new MathInline($matches[1]));
        $inlineContext->getCursor()->advanceBy(strlen($fullmatch));

        return true;
    }
}
