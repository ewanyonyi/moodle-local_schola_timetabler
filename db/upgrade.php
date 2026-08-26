<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade routines for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade database schema and records.
 *
 * @param int $oldversion The old version number.
 * @return bool True on success.
 */
function xmldb_local_schola_timetabler_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026081300) {
        // Define table local_schola_timetabler_templates to be created.
        $table = new xmldb_table('local_schola_timetabler_templates');

        // Adding fields to table local_schola_timetabler_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('slots_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_schola_timetabler_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_schola_timetabler_templates.
        upgrade_plugin_savepoint(true, 2026081300, 'local', 'academic_timetabler');
    }

    if ($oldversion < 2026081401) {
        $renames = [
            'local_ss_rooms'     => 'local_schola_timetabler_rooms',
            'local_ss_slots'     => 'local_schola_timetabler_slots',
            'local_ss_schedules' => 'local_schola_timetabler_schedules',
            'local_ss_templates' => 'local_schola_timetabler_templates',
        ];

        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            $newtable = new xmldb_table($newname);
            if ($dbman->table_exists($oldtable) && !$dbman->table_exists($newtable)) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        // Create missing tables if they don't exist yet
        $rooms = new xmldb_table('local_schola_timetabler_rooms');
        if (!$dbman->table_exists($rooms)) {
            $rooms->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $rooms->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $rooms->add_field('capacity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $rooms->add_field('is_lab', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $rooms->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($rooms);
        }

        $slots = new xmldb_table('local_schola_timetabler_slots');
        if (!$dbman->table_exists($slots)) {
            $slots->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $slots->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'class');
            $slots->add_field('dayofweek', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
            $slots->add_field('exactdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $slots->add_field('starttime', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
            $slots->add_field('endtime', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
            $slots->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($slots);
        }

        $schedules = new xmldb_table('local_schola_timetabler_schedules');
        if (!$dbman->table_exists($schedules)) {
            $schedules->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $schedules->add_field('schedule_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $schedules->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $schedules->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $schedules->add_field('roomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $schedules->add_field('slotid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $schedules->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $schedules->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($schedules);
        }

        $templates = new xmldb_table('local_schola_timetabler_templates');
        if (!$dbman->table_exists($templates)) {
            $templates->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $templates->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $templates->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $templates->add_field('slots_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $templates->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $templates->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $templates->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($templates);
        }

        upgrade_plugin_savepoint(true, 2026081401, 'local', 'academic_timetabler');
    }

    return true;
}
