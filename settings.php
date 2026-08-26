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
 * Admin settings definition for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/schola_timetabler/classes/admin_setting_license_key.php');
require_once($CFG->dirroot . '/local/schola_timetabler/classes/admin_setting_pricing.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_schola_timetabler_settings',
        get_string('pluginname', 'local_schola_timetabler')
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_schola_timetabler/dashboard_heading',
            '',
            '<div class="alert alert-info d-flex align-items-center justify-content-between my-2">' .
            '<div><strong>Schola Timetabler</strong> is installed and operational.</div>' .
            '<a href="' . new moodle_url('/local/schola_timetabler/index.php') . '" class="btn btn-primary font-weight-bold">Open Schola Timetabler Dashboard</a>' .
            '</div>'
        ));

        $settings->add(new \local_schola_timetabler\admin_setting_license_key());

        $settings->add(new \local_schola_timetabler\admin_setting_pricing());
    }

    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_schola_timetabler_dashboard',
        get_string('manage_timetabler', 'local_schola_timetabler'),
        new moodle_url('/local/schola_timetabler/index.php'),
        'local/schola_timetabler:manage'
    ));
}
