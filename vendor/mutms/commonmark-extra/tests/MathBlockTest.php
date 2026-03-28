<?php

namespace MuTMS\CommonMark\Extra\Tests;

class MathBlockTest extends TestBase {
    public function testConvert(): void {
        $converter = self::createConverter();

        $this->assertSame(
            '<p>block math</p>
<div class="code-language-math">\(1/2=0.5\)</div>
<p>test</p>
',
            (string)$converter->convert('block math 
```math
1/2=0.5
```
test')
        );

        $this->assertSame(
            '<p>block math</p>
<pre><code class="language-php">1/2=0.5
</code></pre>
<p>test</p>
',
            (string)$converter->convert('block math 
```php
1/2=0.5
```
test')
        );
    }
}
