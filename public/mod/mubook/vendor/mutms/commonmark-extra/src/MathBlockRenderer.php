<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\Util\Xml;

final class MathBlockRenderer implements NodeRendererInterface {
    /**
     * @param FencedCode $node
     * @param ChildNodeRendererInterface $childRenderer
     * @return string
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
        FencedCode::assertInstanceOf($node);

        $infoWords = $node->getInfoWords();
        if ($infoWords === ['math']) {
            $literal = $node->getLiteral();
            $literal = rtrim($literal); // Remove extra newline.
            return '<div class="code-language-math">\(' . Xml::escape($literal) . '\)</div>';
        }

        $renderer = new FencedCodeRenderer();
        return $renderer->render($node, $childRenderer);
    }
}
