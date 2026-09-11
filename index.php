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
 * Main admin dashboard and timetable generation view for local_schola_timetabler.
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

$PAGE->set_url(new moodle_url('/local/schola_timetabler/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('pluginname', 'local_schola_timetabler'));

$action = optional_param('action', '', PARAM_ALPHA);

// Schema auto-migration check for title & timecreated columns
global $DB;
$dbman = $DB->get_manager();
$schedtable = new xmldb_table('local_schola_timetabler_schedules');
if ($dbman->table_exists($schedtable)) {
    $fieldtitle = new xmldb_field('title', XMLDB_TYPE_CHAR, '100', null, false, false, null);
    if (!$dbman->field_exists($schedtable, $fieldtitle)) {
        $dbman->add_field($schedtable, $fieldtitle);
    }
    $fieldtimecreated = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, false, false, '0');
    if (!$dbman->field_exists($schedtable, $fieldtimecreated)) {
        $dbman->add_field($schedtable, $fieldtimecreated);
    }
}

if ($action === 'generate' && confirm_sesskey()) {
    \core_php_time_limit::raise(600);
    raise_memory_limit(MEMORY_EXTRA);

    $scheduletype = optional_param('scheduletype', 'class', PARAM_ALPHA);
    $categoryid   = optional_param('categoryid', 0, PARAM_INT);
    $genmode      = optional_param('mode', 'version', PARAM_ALPHA);
    $rawtitle     = trim(optional_param('title', '', PARAM_TEXT));

    // Fallback default title if empty
    if (empty($rawtitle)) {
        $year = date('Y');
        $typecaps = ucfirst($scheduletype);
        $rawtitle = "Master {$typecaps} Timetable {$year}";
    }

    // Build course query based on category scope
    $params = [];
    $select = 'id > 1 AND visible = 1';
    if ($categoryid > 0) {
        $select .= ' AND category = :categoryid';
        $params['categoryid'] = $categoryid;
    }

    $courses = $DB->get_records_select('course', $select, $params, 'id ASC');
    $slots   = $DB->get_records('local_schola_timetabler_slots');
    $rooms   = $DB->get_records('local_schola_timetabler_rooms');

    if (empty($rooms)) {
        redirect(
            new moodle_url('/local/schola_timetabler/rooms.php'),
            'Please configure at least one room before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if (empty($slots)) {
        redirect(
            new moodle_url('/local/schola_timetabler/slots.php'),
            'Please configure time slots before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if (empty($courses)) {
        redirect(
            new moodle_url('/local/schola_timetabler/index.php'),
            'No active courses found in the selected department category.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    try {
        $solver = new \local_schola_timetabler\algorithm\solver($slots, $rooms);
        $solver->set_slot_type(($scheduletype === 'exam') ? 'exam' : 'class');
        $solver->load_courses($courses);

        if ($genmode === 'append') {
            // Load ALL existing schedule entries as hard occupied blockouts
            $existingschedules = $DB->get_records('local_schola_timetabler_schedules');
            $solver->load_existing_schedules($existingschedules);
        } else if ($genmode === 'overwrite_all') {
            // Overwrite ALL mode: Delete all schedules of selected type
            if ($categoryid > 0) {
                $catcourseids = array_keys($courses);
                if (!empty($catcourseids)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($catcourseids, SQL_PARAMS_NAMED);
                    $inparams['stype'] = $scheduletype;
                    $DB->delete_records_select('local_schola_timetabler_schedules', "schedule_type = :stype AND courseid {$insql}", $inparams);
                }
            } else {
                $DB->delete_records('local_schola_timetabler_schedules', ['schedule_type' => $scheduletype]);
            }

            // Load remaining non-deleted schedules as occupied blockouts
            $othersexisting = $DB->get_records_select('local_schola_timetabler_schedules', 'schedule_type != :stype', ['stype' => $scheduletype]);
            $solver->load_existing_schedules($othersexisting);
        } else {
            // Version mode (default): Save as new named version or replace version with same title
            $hastitlecol = $DB->get_manager()->field_exists('local_schola_timetabler_schedules', 'title');
            if ($hastitlecol && !empty($rawtitle)) {
                if ($categoryid > 0) {
                    $catcourseids = array_keys($courses);
                    if (!empty($catcourseids)) {
                        [$insql, $inparams] = $DB->get_in_or_equal($catcourseids, SQL_PARAMS_NAMED);
                        $inparams['stype']  = $scheduletype;
                        $inparams['stitle'] = $rawtitle;
                        $DB->delete_records_select('local_schola_timetabler_schedules', "schedule_type = :stype AND title = :stitle AND courseid {$insql}", $inparams);
                    }
                } else {
                    $DB->delete_records('local_schola_timetabler_schedules', ['schedule_type' => $scheduletype, 'title' => $rawtitle]);
                }
            } else {
                if ($categoryid > 0) {
                    $catcourseids = array_keys($courses);
                    if (!empty($catcourseids)) {
                        [$insql, $inparams] = $DB->get_in_or_equal($catcourseids, SQL_PARAMS_NAMED);
                        $inparams['stype'] = $scheduletype;
                        $DB->delete_records_select('local_schola_timetabler_schedules', "schedule_type = :stype AND courseid {$insql}", $inparams);
                    }
                } else {
                    $DB->delete_records('local_schola_timetabler_schedules', ['schedule_type' => $scheduletype]);
                }
            }
        }

        if ($solver->solve_all()) {
            $solution = $solver->get_solution();
            $count = 0;
            $now = time();
            foreach ($solution['classes'] ?? [] as $assignedkey => $sched) {
                if (strpos((string)$assignedkey, 'existing_') === 0) {
                    continue; // Skip loaded blockout entries
                }
                $courseid  = (int)($sched['course_id'] ?? $assignedkey);
                $roomid    = (int)($sched['room_id'] ?? 0);
                $slotid    = (int)($sched['slot_id'] ?? 0);
                $teacherid = (int)($sched['teacher_id'] ?? 0);

                if ($courseid > 0 && $roomid > 0 && $slotid > 0) {
                    $DB->insert_record('local_schola_timetabler_schedules', (object)[
                        'schedule_type' => $scheduletype,
                        'title'        => $rawtitle,
                        'courseid'     => $courseid,
                        'roomid'       => $roomid,
                        'slotid'       => $slotid,
                        'teacherid'    => $teacherid,
                        'timecreated'  => $now,
                    ]);
                    $count++;
                }
            }
            redirect(
                new moodle_url('/local/schola_timetabler/schedules.php', ['type' => $scheduletype, 'title' => $rawtitle, 'categoryid' => $categoryid]),
                "Timetable '{$rawtitle}' generated successfully as a named version! {$count} course sessions assigned conflict-free.",
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                new moodle_url('/local/schola_timetabler/index.php'),
                "Notice: Solver could not assign all courses without conflicts. Try adding more rooms or time slots.",
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/local/schola_timetabler/index.php'),
            "Error running timetable generator: " . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
echo \local_schola_timetabler\output\renderer::render_nav_header('index');

$output = $PAGE->get_renderer('local_schola_timetabler');
echo $output->render_dashboard([]);

// -------------------------------------------------------------------
// Multi-Timetable Generator Options Card
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm my-4 bg-white');
$cardheading = html_writer::tag('h5', get_string('csp_generator_heading', 'local_schola_timetabler'), ['class' => 'mb-0 font-weight-bold']);
echo html_writer::div($cardheading, 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/schola_timetabler/index.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'generate']);

// Fetch course categories (departments)
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$catoptions = [0 => get_string('all_departments', 'local_schola_timetabler')] + $categories;

$typeoptions = [
    'class' => get_string('regular_class_schedule', 'local_schola_timetabler'),
    'exam'  => get_string('examination_schedule', 'local_schola_timetabler'),
];

$modeoptions = [
    'version'       => get_string('version_mode', 'local_schola_timetabler'),
    'overwrite_all' => get_string('overwrite_all_mode', 'local_schola_timetabler'),
    'append'        => get_string('append_existing_mode', 'local_schola_timetabler'),
];

$titlelabel = get_string('timetable_title', 'local_schola_timetabler');
$titlehelp  = get_string('timetable_title_help', 'local_schola_timetabler');
$typelabel  = get_string('timetable_profile_type', 'local_schola_timetabler');
$deptlabel  = get_string('department_scope', 'local_schola_timetabler');
$modelabel  = get_string('generation_conflict_mode', 'local_schola_timetabler');

echo html_writer::start_div('row g-3 mb-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', $titlelabel, ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'title',
    'class' => 'form-control p-2',
    'placeholder' => 'e.g. Semester III 2026 Schedule',
]);
echo html_writer::tag('div', $titlehelp, ['class' => 'form-text extra-small text-muted']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', $typelabel, ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($typeoptions, 'scheduletype', 'class', false, ['class' => 'form-select p-2']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', $deptlabel, ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($catoptions, 'categoryid', 0, false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', $modelabel, ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($modeoptions, 'mode', 'version', false, ['class' => 'form-select p-2']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
$btnlabel = get_string('run_solver_button', 'local_schola_timetabler');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm fs-6']);
echo html_writer::tag('span', get_string('conflict_prevention_notice', 'local_schola_timetabler'), ['class' => 'text-muted small']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
