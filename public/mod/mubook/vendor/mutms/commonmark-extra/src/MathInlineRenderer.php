<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class MathInlineRenderer implements NodeRendererInterface {
    /**
     * @param MathInline $node
     * @param ChildNodeRendererInterface $childRenderer
     * @return string
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
        MathInline::assertInstanceOf($node);
        return '\(' . $node->getExpression() . '\)';
    }
}
