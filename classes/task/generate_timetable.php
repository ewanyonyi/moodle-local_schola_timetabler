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

namespace local_schola_timetabler\task;

use core\task\scheduled_task;
use local_schola_timetabler\algorithm\solver;
use local_schola_timetabler\licensing\license_manager;

/**
 * Scheduled task runner for timetabling generation.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_timetable extends scheduled_task {
    /**
     * Get task name for administrative display.
     *
     * @return string Human-readable task name.
     */
    public function get_name() {
        return get_string('pluginname', 'local_schola_timetabler');
    }

    /**
     * Execute timetable solver task.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/calendar/lib.php');

        \core_php_time_limit::raise(600);
        raise_memory_limit(MEMORY_EXTRA);

        $tier = license_manager::get_tier();
        mtrace("Executing native Course and Exam Timetabling Engine [Tier: " . strtoupper($tier) . "]...");

        $courses = $DB->get_records_select('course', 'id > 1 AND visible = 1');
        $slots   = $DB->get_records('local_schola_timetabler_slots');
        $rooms   = $DB->get_records('local_schola_timetabler_rooms');

        $solver = new solver($slots, $rooms);
        $solver->load_courses($courses);

        if ($solver->solve_all()) {
            $solution = $solver->get_solution();
            $DB->delete_records('local_schola_timetabler_schedules');

            foreach ($solution['classes'] ?? [] as $courseid => $sched) {
                $DB->insert_record('local_schola_timetabler_schedules', (object)[
                    'schedule_type' => 'class',
                    'courseid'     => $courseid,
                    'roomid'       => $sched['room_id'],
                    'slotid'       => $sched['slot_id'],
                    'teacherid'    => $sched['teacher_id'],
                ]);

                $event = new \stdClass();
                $event->name        = 'Weekly Lecture - Room ' . $rooms[$sched['room_id']]->name;
                $event->courseid    = $courseid;
                $event->eventtype   = 'course';
                $event->timestart   = time();
                $event->timeduration = 5400;
                \calendar_event::create($event);
            }

            mtrace("Schedules successfully generated and synchronized!");
        } else {
            mtrace("Error: Unable to find a conflict-free schedule.");
        }
    }
}
