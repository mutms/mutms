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

namespace tool_mulib\phpunit\local;

use tool_mulib\local\mudb;
use xmldb_table;

/**
 * MuTMS database helper tests.
 *
 * @group       MuTMS
 * @package     tool_mulib
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mulib\local\nudb
 */
final class mudb_test extends \database_driver_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->tdb->get_manager(); // Loads DDL libs.
    }

    /**
     * Get a xmldb_table object for testing, deleting any existing table
     * of the same name, for example if one was left over from a previous test
     * run that crashed.
     *
     * @param string $suffix table name suffix, use if you need more test tables
     * @return xmldb_table the table object.
     */
    private function get_test_table($suffix = '') {
        $tablename = "test_table";
        if ($suffix !== '') {
            $tablename .= $suffix;
        }

        $table = new xmldb_table($tablename);
        $table->setComment("This is a test'n drop table. You can drop it safely");
        return $table;
    }

    /**
     * Test upserts.
     */
    public function test_upsert_record(): void {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = $this->get_test_table();
        $tablename = $table->getName();

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '10', null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('course', XMLDB_INDEX_UNIQUE, ['course']);
        $dbman->create_table($table);

        $record1 = (object)[
            'course' => '1',
            'value' => '10',
        ];
        $record1->id = $DB->insert_record($tablename, $record1);
        $record2 = (object)[
            'course' => '2',
            'value' => '20',
        ];
        $record2->id = $DB->insert_record($tablename, $record2);

        $record3 = (object)[
            'course' => '3',
            'value' => '30',
        ];
        mudb::upsert_record($tablename, $record3, ['course']);
        $record3 = $DB->get_record($tablename, ['course' => '3']);
        $this->assertSame('30', $record3->value);

        $record4 = [
            'value' => '40',
            'course' => '3',
        ];
        mudb::upsert_record($tablename, $record4, ['course']);
        $record4 = $DB->get_record($tablename, ['course' => '3']);
        $this->assertSame('40', $record4->value);
        $this->assertSame($record3->id, $record4->id);

        $record4 = [
            'value' => '40',
            'course' => '3',
        ];
        mudb::upsert_record($tablename, $record4, ['course']);
        $record4 = $DB->get_record($tablename, ['course' => '3']);
        $this->assertSame('40', $record4->value);
        $this->assertSame($record3->id, $record4->id);

        unset($tablename);
        $table2 = $this->get_test_table('_2');
        $tablename2 = $table2->getName();

        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table2->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table2->add_field('value', XMLDB_TYPE_INTEGER, '10', null, null);
        $table2->add_field('value2', XMLDB_TYPE_INTEGER, '10', null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_index('course-name', XMLDB_INDEX_UNIQUE, ['course', 'name']);
        $dbman->create_table($table2);

        $record1 = (object)[
            'course' => '1',
            'name' => 'abc',
            'value' => '10',
            'value2' => null,
        ];
        $record1->id = $DB->insert_record($tablename2, $record1);
        $record2 = (object)[
            'course' => '2',
            'name' => 'abc',
            'value' => '20',
            'value2' => '200',
        ];
        $record2->id = $DB->insert_record($tablename2, $record2);

        $record3 = (object)[
            'course' => '3',
            'name' => 'abc',
            'value' => '30',
            'value2' => '300',
        ];
        mudb::upsert_record($tablename2, $record3, ['course', 'name']);
        $record3 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'abc']);
        $this->assertSame('30', $record3->value);
        $this->assertSame('300', $record3->value2);

        $record4 = (object)[
            'course' => '3',
            'name' => 'def',
            'value' => '40',
            'value2' => '400',
        ];
        mudb::upsert_record($tablename2, $record4, ['name', 'course']);
        $record4 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('40', $record4->value);
        $this->assertSame('400', $record4->value2);
        $record3 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'abc']);
        $this->assertSame('30', $record3->value);
        $this->assertSame('300', $record3->value2);

        $record5 = [
            'course' => '3',
            'name' => 'def',
            'value' => '50',
        ];
        mudb::upsert_record($tablename2, $record5, ['name', 'course']);
        $record5 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('50', $record5->value);
        $this->assertSame('400', $record5->value2);
        $this->assertSame($record4->id, $record5->id);

        $record6 = [
            'course' => '3',
            'name' => 'def',
            'value' => '60',
            'value2' => '600',
        ];
        mudb::upsert_record($tablename2, $record6, ['name', 'course']);
        $record6 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('60', $record6->value);
        $this->assertSame('600', $record6->value2);
        $this->assertSame($record4->id, $record6->id);

        try {
            mudb::upsert_record($tablename2, $record5, []);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer:'
                . ' moodle_database::upsert_record() requires list of unique constraint columns', $ex->getMessage());
        }

        $record = [
            'course' => '3',
            'name' => null,
            'value' => '50',
        ];
        try {
            mudb::upsert_record($tablename2, $record, ['course', 'name']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer:'
                . ' moodle_database::upsert_record() dataobject must have all unique columns set', $ex->getMessage());
        }

        $record = [
            'id' => '5',
            'course' => '3',
            'name' => 'abc',
            'value' => '50',
        ];
        try {
            mudb::upsert_record($tablename2, $record, ['course', 'name']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer:'
                . ' moodle_database::upsert_record() dataobject must not have id property', $ex->getMessage());
        }

        $record = [
            'xyz' => '5',
            'course' => '3',
            'name' => 'abc',
            'value' => '50',
        ];
        try {
            mudb::upsert_record($tablename2, $record, ['course', 'name']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer:'
                . ' moodle_database::upsert_record() dataobject contains unknown column', $ex->getMessage());
        }

        $record = [
            'course' => '3',
            'name' => 'abc',
        ];
        try {
            mudb::upsert_record($tablename2, $record, ['course', 'name']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer:'
                . ' moodle_database::upsert_record() dataobject must contain at least one non-unique column', $ex->getMessage());
        }

        // Test compatibility with transaction commit.

        $trans = $DB->start_delegated_transaction();

        $record7 = [
            'course' => '3',
            'name' => 'def',
            'value' => '70',
            'value2' => '700',
        ];
        mudb::upsert_record($tablename2, $record7, ['name', 'course']);
        $record7 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('70', $record7->value);
        $this->assertSame('700', $record7->value2);
        $this->assertSame($record4->id, $record7->id);

        $record8 = [
            'course' => '11',
            'name' => 'def',
            'value' => '80',
            'value2' => '800',
        ];
        mudb::upsert_record($tablename2, $record8, ['name', 'course']);
        $record8 = $DB->get_record($tablename2, ['course' => '11', 'name' => 'def']);
        $this->assertSame('80', $record8->value);
        $this->assertSame('800', $record8->value2);

        $trans->allow_commit();

        $record7 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('70', $record7->value);
        $this->assertSame('700', $record7->value2);
        $this->assertSame($record4->id, $record7->id);
        $record8 = $DB->get_record($tablename2, ['course' => '11', 'name' => 'def']);
        $this->assertSame('80', $record8->value);
        $this->assertSame('800', $record8->value2);

        // Test compatibility with transaction rollback.

        $trans = $DB->start_delegated_transaction();

        $record7 = [
            'course' => '3',
            'name' => 'def',
            'value' => '90',
            'value2' => '900',
        ];
        mudb::upsert_record($tablename2, $record7, ['name', 'course']);
        $record7 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
        $this->assertSame('90', $record7->value);
        $this->assertSame('900', $record7->value2);
        $this->assertSame($record4->id, $record7->id);

        $record9 = [
            'course' => '12',
            'name' => 'def',
            'value' => '100',
            'value2' => '1000',
        ];
        mudb::upsert_record($tablename2, $record9, ['name', 'course']);
        $record9 = $DB->get_record($tablename2, ['course' => '12', 'name' => 'def']);
        $this->assertSame('100', $record9->value);
        $this->assertSame('1000', $record9->value2);

        try {
            $trans->rollback(new \Exception());
            $this->fail('Exception expected');
        } catch (\Throwable $ex) {
            $record7 = $DB->get_record($tablename2, ['course' => '3', 'name' => 'def']);
            $this->assertSame('70', $record7->value);
            $this->assertSame('700', $record7->value2);
            $this->assertSame($record4->id, $record7->id);
            $this->assertFalse($DB->record_exists($tablename2, ['course' => '12', 'name' => 'def']));
        }

        // Test insert-only-fields.

        $DB->delete_records($tablename2, []);

        $record1 = [
            'course' => '3',
            'name' => 'abc',
            'value' => '1',
        ];
        mudb::upsert_record($tablename2, $record1, ['name', 'course'], ['value2' => '111']);
        $record = $DB->get_record($tablename2, ['name' => 'abc', 'course' => 3], '*', MUST_EXIST);
        $this->assertSame('3', $record->course);
        $this->assertSame('abc', $record->name);
        $this->assertSame('1', $record->value);
        $this->assertSame('111', $record->value2);

        mudb::upsert_record($tablename2, $record1, ['name', 'course'], ['value2' => '222']);
        $record = $DB->get_record($tablename2, ['name' => 'abc', 'course' => 3], '*', MUST_EXIST);
        $this->assertSame('3', $record->course);
        $this->assertSame('abc', $record->name);
        $this->assertSame('1', $record->value);
        $this->assertSame('111', $record->value2);

        $record2 = [
            'course' => '3',
            'name' => 'abc',
            'value' => '2',
        ];
        mudb::upsert_record($tablename2, $record2, ['name', 'course'], ['value2' => '333']);
        $record = $DB->get_record($tablename2, ['name' => 'abc', 'course' => 3], '*', MUST_EXIST);
        $this->assertSame('3', $record->course);
        $this->assertSame('abc', $record->name);
        $this->assertSame('2', $record->value);
        $this->assertSame('111', $record->value2);

        try {
            mudb::upsert_record($tablename2, $record1, ['name', 'course'], ['xvalue2' => '222']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: moodle_database::upsert_record() '
                . 'insertonlyfields contains unknown column',
                $ex->getMessage()
            );
        }

        try {
            mudb::upsert_record($tablename2, $record1, ['name', 'course'], ['value' => '222']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: moodle_database::upsert_record() '
                . 'insertonlyfields must not share columns with dataobject',
                $ex->getMessage()
            );
        }

        try {
            mudb::upsert_record($tablename2, $record1, ['name', 'course'], ['name' => '222']);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: moodle_database::upsert_record() '
                . 'insertonlyfields cannot contain unique columns',
                $ex->getMessage()
            );
        }

        $dbman->drop_table($table);
        $dbman->drop_table($table2);
    }
}
