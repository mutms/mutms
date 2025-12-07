<?php

declare(strict_types=1);

/*
 * This file is part of the pomodocs/commonmark-alert package.
 * MIT License. For the full copyright and license information,
 * please view the LICENSE file that was distributed
 * with this source code.
 */

namespace PomoDocs\CommonMark\Alert\Node\Block;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * Node class to model an alert block.
 */
final class Alert extends AbstractBlock
{
    /**
     * @param string $type The alert type. Possible values are: note, tip, important, warning, caution.
     */
    public function __construct(private string $type = 'note')
    {
        parent::__construct();
    }

    /**
     * Return the type of the alert.
     */
    public function getType(): string
    {
        return $this->type;
    }
}
