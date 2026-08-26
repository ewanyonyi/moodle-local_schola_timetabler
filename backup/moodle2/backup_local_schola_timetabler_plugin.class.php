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
 * Backup structure definition for local_schola_timetabler course plugin data.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_schola_timetabler_plugin extends backup_local_plugin {
    /**
     * Define the course backup structure for local_schola_timetabler.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        $schedules = new backup_nested_element('schedules');

        $schedule = new backup_nested_element('schedule', ['id'], [
            'schedule_type', 'quizid', 'roomid', 'slotid', 'teacherid',
        ]);

        $plugin->add_child($pluginwrapper);
        $pluginwrapper->add_child($schedules);
        $schedules->add_child($schedule);

        $schedule->set_source_table('local_schola_timetabler_schedules', ['courseid' => backup::VAR_COURSEID]);

        $schedule->annotate_ids('user', 'teacherid');

        return $plugin;
    }
}
