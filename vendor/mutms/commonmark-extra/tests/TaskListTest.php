<?php

namespace MuTMS\CommonMark\Extra\Tests;

class TaskListTest extends TestBase {
    public function testConvert(): void {
        $converter = self::createConverter();

        $this->assertSame(
            '<p>Some text</p>
<ul>
<li><i class="fa-regular fa-square" title="Task not completed" /> not yet</li>
<li><i class="fa-regular fa-square-check" title="Task completed" /> yes, yes</li>
<li>nothing</li>
</ul>
',
            (string)$converter->convert('Some text

- [ ] not yet
- [x] yes, yes
- nothing
')
        );
    }
}
