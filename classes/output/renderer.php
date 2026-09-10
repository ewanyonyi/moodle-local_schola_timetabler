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

namespace local_schola_timetabler\output;

use plugin_renderer_base;

/**
 * Output renderer for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render unified executive navigation header across plugin pages.
     *
     * @param string $activepage Active page key ('index', 'rooms', 'slots', 'schedules').
     * @param bool $showclearall Whether to display the Clear All action button.
     * @param string $scheduletype Schedule type key ('all', 'class', 'exam').
     * @return string Rendered HTML header bar.
     */
    public static function render_nav_header(string $activepage = 'index', bool $showclearall = false, string $scheduletype = 'all'): string {
        $indexurl = new \moodle_url('/local/schola_timetabler/index.php');
        $roomsurl = new \moodle_url('/local/schola_timetabler/rooms.php');
        $profilesurl = new \moodle_url('/local/schola_timetabler/profiles.php');
        $slotsurl = new \moodle_url('/local/schola_timetabler/slots.php');
        $breaksurl = new \moodle_url('/local/schola_timetabler/breaks.php');
        $schedulesurl = new \moodle_url('/local/schola_timetabler/schedules.php');
        $helpurl = new \moodle_url('/local/schola_timetabler/help.php');
        $generateurl = new \moodle_url('/local/schola_timetabler/schedules.php', ['open_modal' => 1]);

        $helplabel = get_string_manager()->string_exists('nav_help', 'local_schola_timetabler')
            ? get_string('nav_help', 'local_schola_timetabler')
            : 'Help & Guide';
        if (str_starts_with($helplabel, '[[') && str_ends_with($helplabel, ']]')) {
            $helplabel = 'Help & Guide';
        }

        $profileslabel = get_string_manager()->string_exists('nav_profiles', 'local_schola_timetabler')
            ? get_string('nav_profiles', 'local_schola_timetabler')
            : 'Profiles';
        if (str_starts_with($profileslabel, '[[') && str_ends_with($profileslabel, ']]')) {
            $profileslabel = 'Profiles';
        }

        $slotslabel = 'Slots';

        $navitems = [
            'index'     => ['label' => get_string('nav_overview', 'local_schola_timetabler'), 'url' => $indexurl],
            'rooms'     => ['label' => get_string('nav_rooms', 'local_schola_timetabler'), 'url' => $roomsurl],
            'profiles'  => ['label' => $profileslabel, 'url' => $profilesurl],
            'slots'     => ['label' => $slotslabel, 'url' => $slotsurl],
            'breaks'    => ['label' => get_string('nav_breaks', 'local_schola_timetabler'), 'url' => $breaksurl],
            'schedules' => ['label' => get_string('nav_schedules', 'local_schola_timetabler'), 'url' => $schedulesurl],
            'help'      => ['label' => $helplabel, 'url' => $helpurl],
        ];

        $html = \html_writer::start_div('bg-white border rounded shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3');

        // Navigation Tabs (Pills)
        $html .= \html_writer::start_div('nav nav-pills gap-2');
        foreach ($navitems as $key => $item) {
            $isactive = ($key === $activepage);
            $cls = $isactive
                ? 'nav-link active bg-primary font-weight-bold px-3 py-2 shadow-sm'
                : 'nav-link text-dark fw-semibold px-3 py-2 bg-light border border-secondary-subtle';
            $html .= \html_writer::link($item['url'], $item['label'], ['class' => $cls]);
        }
        $html .= \html_writer::end_div();

        // Action Buttons Group (Only show Generate Timetable button when on schedules or index page)
        $html .= \html_writer::start_div('d-flex align-items-center gap-2 flex-wrap');
        if ($activepage === 'schedules' || $activepage === 'index') {
            $html .= \html_writer::link($generateurl, get_string('generate_timetable', 'local_schola_timetabler'), [
                'class'          => 'btn btn-success font-weight-bold px-3 py-2 shadow-sm',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#generateTimetableModal',
                'data-toggle'    => 'modal',
                'data-target'    => '#generateTimetableModal',
            ]);
        }

        if ($showclearall) {
            $clearurl = new \moodle_url($schedulesurl, ['action' => 'clearall', 'type' => $scheduletype, 'sesskey' => sesskey()]);
            $cleartitle = ($scheduletype !== 'all')
                ? get_string('clear_timetables', 'local_schola_timetabler') . ' (' . strtoupper($scheduletype) . ')'
                : get_string('clear_timetables', 'local_schola_timetabler');
            $confirmmsg = s(get_string('confirm_clear_timetables', 'local_schola_timetabler'));
            $html .= \html_writer::link($clearurl, $cleartitle, [
                'class' => 'btn btn-outline-danger font-weight-bold px-3 py-2',
                'onclick' => 'return confirm("' . $confirmmsg . '");',
            ]);
        }
        $html .= \html_writer::end_div();

        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Render main plugin dashboard interface.
     *
     * @param mixed $page Page context object or array.
     * @return string Rendered HTML content.
     */
    public function render_dashboard($page) {
        global $DB;

        $indexurl = new \moodle_url('/local/schola_timetabler/index.php');
        $roomsurl = new \moodle_url('/local/schola_timetabler/rooms.php');
        $slotsurl = new \moodle_url('/local/schola_timetabler/slots.php');
        $schedulesurl = new \moodle_url('/local/schola_timetabler/schedules.php');
        $generateurl = new \moodle_url('/local/schola_timetabler/index.php', [
            'action' => 'generate',
            'sesskey' => sesskey(),
        ]);

        $coursecount = $DB->count_records_select('course', 'id > 1 AND visible = 1');
        $roomcount = $DB->count_records('local_schola_timetabler_rooms');
        $schedulecount = $DB->count_records('local_schola_timetabler_schedules');

        $tiernotice = 'Free Local Processing Engine Active — Course and exam timetables are processed locally on your Moodle server.';

        $contextdata = [
            'tier_notice' => $tiernotice,
            'index_url' => $indexurl->out(false),
            'rooms_url' => $roomsurl->out(false),
            'slots_url' => $slotsurl->out(false),
            'schedules_url' => $schedulesurl->out(false),
            'generate_url' => $generateurl->out(false),
            'course_count' => $coursecount,
            'max_courses_label' => 'Unlimited',
            'room_count' => $roomcount,
            'schedule_count' => $schedulecount,
            'is_course_exceeded' => false,
        ];

        return $this->render_from_template('local_schola_timetabler/dashboard', $contextdata);
    }
}
