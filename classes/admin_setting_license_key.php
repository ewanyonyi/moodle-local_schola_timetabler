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

namespace local_schola_timetabler;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Custom admin setting element for Cloud Solver API Key / License Key with inline Save button.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_license_key extends \admin_setting {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'local_schola_timetabler/license_key',
            'Cloud Solver API Key',
            'Optional. Enter your Cloud Solver API key to connect off-server high-performance solver services.',
            ''
        );
    }

    /**
     * Get setting value.
     *
     * @return mixed Setting value string or false.
     */
    public function get_setting() {
        return get_config('local_schola_timetabler', 'license_key');
    }

    /**
     * Write setting value.
     *
     * @param mixed $data Data to write.
     * @return string Empty string.
     */
    public function write_setting($data) {
        if ($data === null) {
            return '';
        }
        $data = trim((string)$data);
        set_config('license_key', $data, 'local_schola_timetabler');
        return '';
    }

    /**
     * Output HTML for the license key setting element.
     *
     * @param mixed $data Current setting value.
     * @param string $query Search query string.
     * @return string Rendered HTML.
     */
    public function output_html($data, $query = '') {
        $current = $this->get_setting();
        if ($current === null || $current === false) {
            $current = '';
        }

        $id = $this->get_id();
        $name = $this->get_full_name();

        $html = '<div class="form-item row mb-3 align-items-center" id="admin-' . $this->name . '">';
        $html .= '<div class="form-label col-sm-3 text-sm-right">';
        $html .= '<label for="' . $id . '" class="form-label font-weight-bold mb-0">' . s($this->visiblename) . '</label>';
        $html .= '<div class="small text-muted">local_schola_timetabler | license_key</div>';
        $html .= '</div>';
        $html .= '<div class="form-setting col-sm-9">';
        $html .= '<div class="input-group" style="max-width: 550px;">';
        $html .= '<input type="text" class="form-control p-2" id="' . $id . '" name="' . $name . '" value="' . s($current) . '" placeholder="Enter API Key / License Key">';
        $html .= '<button type="submit" class="btn btn-primary font-weight-bold px-3">Save Key</button>';
        $html .= '</div>';
        if (!empty($this->description)) {
            $html .= '<div class="form-text text-muted small mt-1">' . $this->description . '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<style>
        #adminsettings form > div.row:last-child,
        #adminsettings form > div.form-buttons,
        #adminsettings form > div.form-submit,
        #adminsettings div.settingsform > div.row:last-child,
        .settingsform div.form-buttons,
        .settingsform div.form-submit,
        #adminsettings button[type="submit"]:not(.input-group *),
        #adminsettings input[type="submit"]:not(.input-group *) {
            display: none !important;
        }
        </style>';

        return $html;
    }
}
