<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;

final class TaskIconRenderer implements NodeRendererInterface, ConfigurationAwareInterface {
    /** @var ConfigurationInterface */
    protected $configuration;

    /**
     * @param TaskIconInline $node
     * @param ChildNodeRendererInterface $childRenderer
     * @return string
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
        TaskIconInline::assertInstanceOf($node);

        if ($node->isCompleted) {
            $label = $this->configuration->get('task/labels/completed');
            $label = htmlspecialchars($label);
            return '<i class="fa-regular fa-square-check" title="' . $label . '" />';
        } else {
            $label = $this->configuration->get('task/labels/notcompleted');
            $label = htmlspecialchars($label);
            return '<i class="fa-regular fa-square" title="' . $label . '" />';
        }
    }

    public function setConfiguration(ConfigurationInterface $configuration): void {
        $this->configuration = $configuration;
    }
}
