<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\Config\ConfigurationBuilderInterface;
use Nette\Schema\Expect;

final class ExtraExtension implements ConfigurableExtensionInterface {
    public function register(EnvironmentBuilderInterface $environment): void {
        // Inline Mathjax rendering using $`1+2`$ GFM syntax.
        $environment
            ->addInlineParser(new MathInlineParser(), 1010)
            ->addRenderer(MathInline::class, new MathInlineRenderer(), 1020);

        // Add Math fenced block renderer.
        $environment
            ->addRenderer(FencedCode::class, new MathBlockRenderer(), 1030);

        // Task list item rendering.
        $environment
            ->addInlineParser(new TaskListItemParser(), 35)
            ->addRenderer(TaskIconInline::class, new TaskIconRenderer());
    }

    public function configureSchema(ConfigurationBuilderInterface $builder): void {
        $builder->addSchema('task', Expect::structure([
            'labels' => Expect::structure([
                'completed' => Expect::string('Task completed'),
                'notcompleted' => Expect::string('Task not completed'),
            ])
        ]));
    }
}
