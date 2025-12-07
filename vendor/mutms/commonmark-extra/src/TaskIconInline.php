<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Node\Inline\AbstractInline;

final class TaskIconInline extends AbstractInline {
    /** @var bool */
    public $isCompleted;

    public function __construct(bool $isCompleted) {
        parent::__construct();
        $this->isCompleted = $isCompleted;
    }
}
