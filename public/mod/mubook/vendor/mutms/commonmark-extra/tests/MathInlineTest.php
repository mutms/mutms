<?php

namespace MuTMS\CommonMark\Extra\Tests;

class MathInlineTest extends TestBase {
    public function testConvert(): void {
        $converter = self::createConverter();

        $this->assertSame(
            "<p>inline math \(1/2=0.5\) test</p>\n",
            (string)$converter->convert('inline math $`1/2=0.5`$ test')
        );

        $this->assertSame(
            "<p>inline math $1/2=0.5$ test</p>\n",
            (string)$converter->convert('inline math $1/2=0.5$ test')
        );

        $this->assertSame(
            "<p>inline math <code>1/2=0.5</code> test</p>\n",
            (string)$converter->convert('inline math `1/2=0.5` test')
        );
    }
}
