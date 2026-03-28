<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Renderer\Block;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;
use League\Config\ConfigurationAwareInterface;
use League\Config\ConfigurationInterface;
use PomoDocs\CommonMark\Alert\Node\Block\Alert;

/**
 * Renderer for Alert block.
 */
final class AlertRenderer implements NodeRendererInterface, XmlNodeRendererInterface, ConfigurationAwareInterface
{
    private ConfigurationInterface $config;

    public function setConfiguration(ConfigurationInterface $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * Render the alert block.
     *
     * @param Node $node The Alert node
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        Alert::assertInstanceOf($node);

        /** @var Alert $node */
        $type = $node->getType();

        /** @var string $class */
        $class = $this->config->get('alert/class_name');

        $icon = $this->getIconString($type);

        /** @var string $label */
        $label = $this->config->get("alert/labels/$type");

        $title = "<p class=\"$class-title\">{$icon}{$label}</p>";

        /** @var array<string, array<array-key, string>> $attributes */
        $attributes = $node->data->get('attributes');

        /** @var string $color */
        $color = $this->config->get("alert/colors/$type");
        $attributes['class'] = "$class $class-$color";

        $filling = $childRenderer->renderNodes($node->children());
        $innerSeparator = $childRenderer->getInnerSeparator();

        $content = $innerSeparator
            . $title
            . $innerSeparator
            . ($filling !== '' ? $filling . $innerSeparator : '');

        return new HtmlElement('div', $attributes, $content);
    }

    public function getXmlTagName(Node $node): string
    {
        return 'alert';
    }

    /**
     * @return array<string, scalar>
     */
    public function getXmlAttributes(Node $node): array
    {
        /** @var Alert $node */
        return [
            'type' => $node->getType(),
        ];
    }

    /**
     * Return an html string representing the icon for the alert title.
     *
     * @param string $type The type of alert.
     * @return string
     */
    private function getIconString(string $type): string
    {
        return $this->config->get('alert/icons/active') ? (
            $this->config->get('alert/icons/use_svg')
                ? $this->config->get("alert/icons/svg/$type")
                : "<i class=\"{$this->config->get("alert/icons/names/$type")}\"></i>"
        )
        : "";
    }
}
