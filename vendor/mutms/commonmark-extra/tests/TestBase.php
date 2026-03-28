<?php

namespace MuTMS\CommonMark\Extra\Tests;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

abstract class TestBase extends \PHPUnit\Framework\TestCase {
    public static function createConverter(array $config = []): MarkdownConverter {
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new \MuTMS\CommonMark\Extra\ExtraExtension());

        return new MarkdownConverter($environment);
    }
}
