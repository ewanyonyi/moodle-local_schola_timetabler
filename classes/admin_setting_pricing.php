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
 * Custom admin setting element to render clean System Performance & Cloud Solver Information.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_pricing extends \admin_setting {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->nosave = true;
        parent::__construct('local_schola_timetabler/commercial_plans', '', '', '');
    }

    /**
     * Get setting value.
     *
     * @return bool
     */
    public function get_setting() {
        return true;
    }

    /**
     * Get default setting value.
     *
     * @return bool
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Write setting value.
     *
     * @param mixed $data Data to write.
     * @return string Empty string.
     */
    public function write_setting($data) {
        return '';
    }

    /**
     * Output HTML for the pricing setting.
     *
     * @param mixed $data Current setting data.
     * @param string $query Search query string.
     * @return string Rendered HTML.
     */
    public function output_html($data, $query = '') {
        $licensekey = get_config('local_schola_timetabler', 'license_key');
        $licensekey = trim((string)$licensekey);

        $iscloudactive = !empty($licensekey);
        $checkouturl = \local_schola_timetabler\licensing\license_manager::get_checkout_url();

        $statuscard = '';
        if ($iscloudactive) {
            $statuscard = '
            <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 rounded-3 border-0 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-success fs-3"><i class="fa fa-cloud-check"></i></div>
                    <div>
                        <strong class="text-success fs-6">High-Performance Cloud Solver Active</strong>
                        <div class="small text-muted">Off-server solver engine connected. Unlimited course scheduling, campus venue capacity, and priority processing enabled.</div>
                    </div>
                </div>
                <span class="badge bg-success text-white px-3 py-2 rounded-pill"><i class="fa fa-check-circle me-1"></i> CLOUD CONNECTED</span>
            </div>';
        } else {
            $statuscard = '
            <div class="alert alert-light border d-flex align-items-center justify-content-between mb-4 rounded-3 shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-primary fs-3"><i class="fa fa-server"></i></div>
                    <div>
                        <strong class="text-dark fs-6">Native Server Processing Engine (Active)</strong>
                        <div class="small text-muted">Course and exam timetables are generated locally on your Moodle server. Ideal for departments & mid-sized schools.</div>
                    </div>
                </div>
                <span class="badge bg-secondary text-white px-3 py-2 rounded-pill"><i class="fa fa-check me-1"></i> NATIVE MODE</span>
            </div>';
        }

        return '
        <div class="mt-4 p-4 bg-white border rounded shadow-sm mb-4">
            <h5 class="font-weight-bold text-dark mb-1"><i class="fa fa-microchip me-2 text-primary"></i>Solver Engine Architecture</h5>
            <p class="text-muted small mb-4">Choose between native server processing or off-server cloud acceleration for large datasets.</p>
            ' . $statuscard . '

            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col">
                    <div class="card h-100 border p-4 rounded-3 bg-light">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fa fa-hdd text-secondary fs-4 me-2"></i>
                            <h6 class="font-weight-bold text-dark m-0">Native Local Engine</h6>
                        </div>
                        <ul class="list-unstyled mb-3 small text-muted">
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Runs directly inside your Moodle PHP environment</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Master weekly timetables & exam scheduling</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Bell schedule wizard & venue management</li>
                        </ul>
                    </div>
                </div>

                <div class="col">
                    <div class="card h-100 border border-primary-subtle p-4 rounded-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-cloud text-primary fs-4 me-2"></i>
                                <h6 class="font-weight-bold text-dark m-0">Cloud Solver Acceleration</h6>
                            </div>
                            <span class="badge bg-primary-subtle text-primary font-weight-normal px-2 py-1">Optional</span>
                        </div>
                        <ul class="list-unstyled mb-3 small text-muted">
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Off-server high-speed solver engine</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Unlimited course datasets & campus venues</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Automated background tasks & batch venue import</li>
                        </ul>
                        <div class="pt-2 mt-auto">
                            <a href="' . s($checkouturl) . '" target="_blank" class="btn btn-outline-primary btn-sm font-weight-bold w-100 py-2">
                                Learn About Cloud Engine Services &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
}
