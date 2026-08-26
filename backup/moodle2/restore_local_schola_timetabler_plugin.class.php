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

defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure definition for local_schola_timetabler course plugin data.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_schola_timetabler_plugin extends restore_local_plugin {
    /**
     * Define the course restore structure for local_schola_timetabler.
     *
     * @return array
     */
    protected function define_course_plugin_structure() {
        $paths = [];

        $paths[] = new restore_path_element(
            'local_schola_timetabler_schedule',
            $this->get_pathfor('/schedules/schedule')
        );

        return $paths;
    }

    /**
     * Process schedule element during restore.
     *
     * @param array $data Parsed schedule data.
     * @return void
     */
    public function process_local_schola_timetabler_schedule($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->courseid = $this->get_courseid();
        $data->teacherid = $this->get_mappingid('user', $data->teacherid);

        $newitemid = $DB->insert_record('local_schola_timetabler_schedules', $data);

        $this->set_mapping('local_schola_timetabler_schedule', $oldid, $newitemid);
    }
}
