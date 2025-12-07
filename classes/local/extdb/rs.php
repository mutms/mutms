<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod

namespace tool_mulib\local\extdb;

use PDOStatement;

/**
 * PDO query result set.
 *
 * @package    tool_mulib
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class rs implements \Iterator {
    /** @var PDOStatement|null */
    private $statement;
    /** @var \Iterator|null */
    private $iterator;

    /**
     * Constructor.
     *
     * @param PDOStatement $statement
     */
    public function __construct(PDOStatement $statement) {
        $this->statement = $statement;
        $this->iterator = $statement->getIterator();
    }

    /**
     * Dispose result set.
     *
     * @return void
     */
    public function close(): void {
        if (!isset($this->statement)) {
            return;
        }
        $this->statement->closeCursor();
        $this->statement = null;
        $this->iterator = null;
    }

    /**
     * Result set iteration support.
     *
     * @return \Iterator
     */
    public function getIterator(): \Iterator {
        return $this->statement->getIterator();
    }

    /**
     * Returns current row.
     * @return mixed
     */
    public function current(): mixed {
        return $this->iterator->current();
    }

    /**
     * Moves to next row.
     */
    public function next(): void {
        $this->iterator->next();
    }

    /**
     * Returns row number.
     * @return mixed
     */
    public function key(): mixed {
        return $this->iterator->key();
    }

    /**
     * Is cursor valid?
     * @return bool
     */
    public function valid(): bool {
        return $this->iterator->valid();
    }

    /**
     * Rewind to the beginning.
     */
    public function rewind(): void {
        $this->iterator->rewind();
    }
}
