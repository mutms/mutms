<?php

namespace MuTMS\CommonMark\Extra;

use League\CommonMark\Node\Inline\AbstractInline;

final class MathInline extends AbstractInline {
    /** @var string string */
    protected $expression;

    public function __construct(string $expression) {
        parent::__construct();
        $this->expression = $expression;
    }

    public function getExpression(): string {
        return $this->expression;
    }
}
