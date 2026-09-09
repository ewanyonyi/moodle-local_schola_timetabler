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
 * Help, Documentation & User Guide for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/schola_timetabler:manage', $context);

$url = new moodle_url('/local/schola_timetabler/help.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('help_title', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('help_heading', 'local_schola_timetabler'));

echo $OUTPUT->header();

// Render navigation header bar with 'help' active
echo \local_schola_timetabler\output\renderer::render_nav_header('help');

$roomsurl    = new moodle_url('/local/schola_timetabler/rooms.php');
$profilesurl = new moodle_url('/local/schola_timetabler/profiles.php');
$slotsurl    = new moodle_url('/local/schola_timetabler/slots.php');
$breaksurl   = new moodle_url('/local/schola_timetabler/breaks.php');
$indexurl    = new moodle_url('/local/schola_timetabler/index.php');
$schedurl    = new moodle_url('/local/schola_timetabler/schedules.php');
$setturl     = new moodle_url('/admin/settings.php', ['section' => 'local_schola_timetabler_settings']);

echo '
<div class="row g-4 mb-4">
    <!-- Hero Banner Card -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-dark text-white p-4 overflow-hidden position-relative"
             style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="badge bg-success text-white font-monospace text-uppercase px-3 py-1 rounded-pill mb-2">FREE LOCAL ENGINE</span>
                    <h2 class="fw-bold text-white mb-2">Schola Timetabler Administrator User Guide</h2>
                    <p class="text-light opacity-75 lead mb-3">
                        Learn how to configure campus venues, set up bell schedule time profiles, batch-import slots via CSV,
                        generate conflict-free timetables, and manage versioned schedules.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="#quickstart" class="btn btn-success font-weight-bold px-4 py-2 rounded-pill shadow-sm">
                            <i class="fa fa-rocket me-2"></i>Quickstart Steps
                        </a>
                        <a href="#faq" class="btn btn-outline-light font-weight-bold px-4 py-2 rounded-pill">
                            <i class="fa fa-question-circle me-2"></i>FAQ & Troubleshooting
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-end d-none d-lg-block">
                    <i class="fa fa-calendar-check fa-8x opacity-25 text-success"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5-Step Timetabling Workflow -->
<div id="quickstart" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fa fa-layer-group text-primary me-2"></i>5-Step Institutional Timetabling Workflow</h4>
            <p class="text-muted small mb-0">Follow these five steps to produce optimized, conflict-free weekly timetables for your school or university.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Step 1 -->
        <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary rounded-pill font-monospace px-3 py-1">STEP 1</span>
                        <i class="fa fa-building-columns text-primary fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Configure Campus Venues</h6>
                    <p class="text-muted small mb-3">
                        Add lecture halls, classrooms, laboratories, and virtual spaces. Set seating capacities
                        and lab types, or batch-import venues in bulk via CSV.
                    </p>
                </div>
                <a href="' . $roomsurl->out(false) . '" class="btn btn-sm btn-outline-primary font-weight-bold rounded-pill w-100">
                    Manage Rooms &rarr;
                </a>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-success rounded-pill font-monospace px-3 py-1">STEP 2</span>
                        <i class="fa fa-clock text-success fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Profiles & Time Slots</h6>
                    <p class="text-muted small mb-3">
                        Apply institutional schedule profiles (University Standard, 3-Hour Block, High School)
                        or batch-import custom period slots via CSV.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="' . $profilesurl->out(false) . '" class="btn btn-sm btn-outline-success font-weight-bold rounded-pill flex-fill text-center">
                        Profiles &rarr;
                    </a>
                    <a href="' . $slotsurl->out(false) . '" class="btn btn-sm btn-success font-weight-bold rounded-pill flex-fill text-center">
                        Slots &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-light h-100 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-warning text-dark rounded-pill font-monospace px-3 py-1">STEP 3</span>
                        <i class="fa fa-coffee text-warning fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Define Breaks & Blockouts</h6>
                    <p class="text-muted small mb-3">Configure campus-wide morning tea breaks and lunch break windows. The solver automatically protects these windows.</p>
                </div>
                <a href="' . $breaksurl->out(false) . '" class="btn btn-sm btn-outline-warning font-weight-bold text-dark rounded-pill w-100">
                    Configure Breaks &rarr;
                </a>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="col-md-6">
            <div class="p-3 border rounded-3 bg-light h-100 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-info text-dark rounded-pill font-monospace px-3 py-1">STEP 4</span>
                        <i class="fa fa-cogs text-info fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Generate Named Timetables</h6>
                    <p class="text-muted small mb-3">
                        Choose schedule type (<strong>Class</strong> or <strong>Exam</strong>), enter a custom title
                        (e.g., "Semester III 2026 Schedule"), and select your conflict mode.
                    </p>
                </div>
                <a href="' . $schedurl->out(false) . '?open_modal=1" class="btn btn-sm btn-outline-info text-dark font-weight-bold rounded-pill w-100">
                    Run Timetable Generator &rarr;
                </a>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="col-md-6">
            <div class="p-3 border rounded-3 bg-light h-100 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-purple text-white rounded-pill font-monospace px-3 py-1" style="background:#7e22ce;">STEP 5</span>
                        <i class="fa fa-table text-purple fs-4" style="color:#7e22ce;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">View, Export & Print Studio</h6>
                    <p class="text-muted small mb-3">
                        Inspect weekly matrix grids by department, venue, or lecturer. Filter timetable views,
                        export assignments to CSV, or print clean PDF schedules.
                    </p>
                </div>
                <a href="' . $schedurl->out(false) . '" class="btn btn-sm btn-outline-dark font-weight-bold rounded-pill w-100">
                    View Timetable Studio &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
<!-- FAQ & Troubleshooting Accordion -->
<div id="faq" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="pb-3 mb-4 border-bottom">
        <h4 class="fw-bold text-dark mb-1"><i class="fa fa-circle-question text-success me-2"></i>Frequently Asked Questions (FAQ)</h4>
        <p class="text-muted small mb-0">
            Find quick solutions to common administrator questions regarding timetabling, venue setup, solver rules,
            CSV imports, and local timetable setup.
        </p>
    </div>

    <div class="accordion accordion-flush" id="faqAccordion">
        <!-- Q1: Rooms & Campus Venues -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headingRooms">
                <button class="accordion-button font-weight-bold text-dark bg-light" type="button"
                        data-bs-toggle="collapse" data-target="#collapseRooms" data-bs-target="#collapseRooms"
                        aria-expanded="true" aria-controls="collapseRooms">
                    <i class="fa fa-building text-primary me-2"></i>How do I configure Campus Venues & Batch-Import Rooms via CSV?
                </button>
            </h2>
            <div id="collapseRooms" class="accordion-collapse collapse show" aria-labelledby="headingRooms" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                    <p class="mb-2">
                        Navigate to <a href="' . $roomsurl->out(false) . '" class="fw-bold">Rooms</a> to add or edit campus venues.
                        Every room requires a <strong>Name</strong> and <strong>Seating Capacity</strong>.
                    </p>
                    <ul class="mb-2">
                        <li><strong>Lecture Halls & Classrooms:</strong> Standard spaces used for general lectures.</li>
                        <li>
                            <strong>Laboratories & Specialized Studios:</strong> Check the <em>"Laboratory / Computer Studio"</em>
                            toggle for rooms requiring specialized equipment or software.
                        </li>
                        <li>
                            <strong>Virtual / Online Spaces:</strong> Rooms with names containing <em>"Online"</em>, <em>"Virtual"</em>,
                            or <em>"Zoom"</em> are automatically identified as virtual spaces.
                        </li>
                    </ul>
                    <p class="mb-0">
                        <strong>Batch CSV Import:</strong> You can bulk-import rooms by uploading a CSV with headers:
                        <code>Name, Capacity, Is Lab</code> (e.g. <code>Computer Science Lab 2, 45, 1</code>).
                        A sample CSV download is available directly on the Rooms page.
                    </p>
                </div>
            </div>
        </div>

        <!-- Q2: Versioning & Conflict Modes -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed font-weight-bold text-dark bg-light" type="button"
                        data-bs-toggle="collapse" data-target="#collapseOne" data-bs-target="#collapseOne"
                        aria-expanded="false" aria-controls="collapseOne">
                    <i class="fa fa-layer-group text-primary me-2"></i>How does Timetable Versioning & Conflict Modes work?
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                    <p class="mb-2">When generating a timetable, you can select one of three conflict modes:</p>
                    <ul class="mb-0">
                        <li>
                            <strong>Save as Named Version:</strong> Assign a title (e.g. <em>Semester III 2026 Schedule</em> vs <em>Draft Option B</em>).
                            Generates side-by-side versions without overwriting existing timetables.
                        </li>
                        <li><strong>Overwrite ALL Timetables:</strong> Replaces existing timetables of the selected type (Class or Exam) with the new generated solution.</li>
                        <li><strong>Append Mode:</strong> Keeps existing schedules active as occupied blockouts and schedules new sessions around them to prevent conflicts.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Q3 -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed font-weight-bold text-dark bg-light" type="button"
                        data-bs-toggle="collapse" data-target="#collapseTwo" data-bs-target="#collapseTwo"
                        aria-expanded="false" aria-controls="collapseTwo">
                    <i class="fa fa-file-csv text-success me-2"></i>How do I batch-import Time Slots via CSV?
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                    <p class="mb-2">Navigate to <a href="' . $slotsurl->out(false) . '" class="fw-bold">Slots</a> and upload a CSV file with these column headers:</p>
                    <code>dayofweek, starttime, endtime, type</code>
                    <p class="mt-2 mb-0">
                        Values for <code>dayofweek</code> can be numbers (1=Monday to 7=Sunday) or day names (e.g. <em>Mon</em>, <em>Tuesday</em>).
                        Supported <code>type</code> categories are <code>class</code>, <code>lab</code>, <code>break</code>, or <code>exam</code>.
                        You can also download a sample CSV directly from the page.
                    </p>
                </div>
            </div>
        </div>

        <!-- Q4 -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed font-weight-bold text-dark bg-light" type="button"
                        data-bs-toggle="collapse" data-target="#collapseThree" data-bs-target="#collapseThree"
                        aria-expanded="false" aria-controls="collapseThree">
                    <i class="fa fa-file-signature text-warning me-2"></i>What is the difference between Class and Exam Timetables?
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                    <strong>Class Timetables</strong> schedule recurring weekly course lectures across period windows.
                    <strong>Exam Timetables</strong> organize course examinations into dedicated assessment windows (e.g. 3-hour exam blocks)
                    while enforcing student conflict avoidance and room capacity constraints.
                </div>
            </div>
        </div>

        <!-- Q5 -->
        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed font-weight-bold text-dark bg-light" type="button"
                        data-bs-toggle="collapse" data-target="#collapseFour" data-bs-target="#collapseFour"
                        aria-expanded="false" aria-controls="collapseFour">
                    <i class="fa fa-bolt text-info me-2"></i>How does the local solver work?
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                    Schola Timetabler runs its timetable generation locally in your Moodle PHP environment. No external API or paid license is required,
                    and the solver uses the configured rooms, slots, and course data to generate conflict-free schedules on your server.
                </div>
            </div>
        </div>
    </div>
</div>v>

<!-- System Status & Licensing Info Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <span class="text-uppercase font-monospace text-muted small fw-bold d-block">ACTIVE ENGINE TIER</span>
            <h5 class="fw-bold text-dark mb-1">' . s($tiername) . '</h5>
            <p class="text-muted small mb-0">
                Free Local Processing Engine Active — Local PHP constraint solver handling timetabling operations.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="' . $setturl->out(false) . '" class="btn btn-outline-dark font-weight-bold px-4 py-2 rounded-pill">
                <i class="fa fa-sliders me-2"></i>Plugin Settings
            </a>
        </div>
    </div>
</div>
';

echo $OUTPUT->footer();
