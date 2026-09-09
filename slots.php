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
 * Manage Active Time Slots & Bell Schedule for local_schola_timetabler.
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

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/schola_timetabler/slots.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('slots_management_title', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('slots_management_heading', 'local_schola_timetabler'));

// -------------------------------------------------------------------
// Action: Download Sample CSV Template
// -------------------------------------------------------------------
if ($action === 'sample_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="schola_timetabler_time_slots_sample.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        get_string('day_of_week', 'local_schola_timetabler'),
        get_string('start_time', 'local_schola_timetabler'),
        get_string('end_time', 'local_schola_timetabler'),
        get_string('type', 'core'),
    ]);
    fputcsv($out, [1, '08:00', '09:00', 'class']);
    fputcsv($out, [1, '09:00', '10:00', 'class']);
    fputcsv($out, [1, '10:00', '10:30', 'break']);
    fputcsv($out, [1, '10:30', '11:30', 'class']);
    fputcsv($out, [1, '11:30', '12:30', 'class']);
    fputcsv($out, [1, '12:30', '13:30', 'break']);
    fputcsv($out, [1, '13:30', '14:30', 'class']);
    fputcsv($out, [1, '14:30', '15:30', 'class']);
    fputcsv($out, [2, '08:00', '09:00', 'class']);
    fputcsv($out, [2, '09:00', '10:00', 'class']);
    fputcsv($out, [2, '10:00', '10:30', 'break']);
    fputcsv($out, [2, '10:30', '12:30', 'lab']);
    fclose($out);
    exit;
}

// -------------------------------------------------------------------
// Action: Delete Single Time Slot
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_timetabler_slots', ['id' => $id]);
    redirect($url, 'Time slot deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Clear All Slots
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    $DB->delete_records('local_schola_timetabler_slots');
    redirect($url, 'All time slots cleared successfully.');
}

// -------------------------------------------------------------------
// Action: Batch CSV Import for Time Slots
// -------------------------------------------------------------------
if ($action === 'import_csv' && confirm_sesskey() && data_submitted()) {
    $wipeexisting = optional_param('wipe_existing', 0, PARAM_INT);

    if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle, 1000, ',');
            if ($header !== false) {
                // Normalize header keys
                $headermap = [];
                foreach ($header as $idx => $col) {
                    $clean = strtolower(trim(str_replace([' ', '_'], '', $col)));
                    $headermap[$clean] = $idx;
                }

                $dayidx   = $headermap['dayofweek'] ?? ($headermap['day'] ?? null);
                $startidx = $headermap['starttime'] ?? ($headermap['start'] ?? null);
                $endidx   = $headermap['endtime'] ?? ($headermap['end'] ?? null);
                $typeidx  = $headermap['type'] ?? ($headermap['category'] ?? null);

                if ($dayidx !== null && $startidx !== null && $endidx !== null) {
                    if ($wipeexisting) {
                        $DB->delete_records('local_schola_timetabler_slots');
                    }

                    $inserted = 0;
                    $daymap = [
                        'mon' => 1, 'monday' => 1, '1' => 1,
                        'tue' => 2, 'tuesday' => 2, '2' => 2,
                        'wed' => 3, 'wednesday' => 3, '3' => 3,
                        'thu' => 4, 'thursday' => 4, '4' => 4,
                        'fri' => 5, 'friday' => 5, '5' => 5,
                        'sat' => 6, 'saturday' => 6, '6' => 6,
                        'sun' => 7, 'sunday' => 7, '7' => 7,
                    ];

                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        $rawday   = strtolower(trim($row[$dayidx] ?? ''));
                        $rawstart = trim($row[$startidx] ?? '');
                        $rawend   = trim($row[$endidx] ?? '');
                        $rawtype  = strtolower(trim($row[$typeidx ?? -1] ?? 'class'));

                        if (empty($rawstart) || empty($rawend)) {
                            continue;
                        }

                        $daynum = $daymap[$rawday] ?? (int)$rawday;
                        if ($daynum < 1 || $daynum > 7) {
                            $daynum = 1;
                        }

                        $stype = in_array($rawtype, ['class', 'lab', 'break', 'exam']) ? $rawtype : 'class';

                        $DB->insert_record('local_schola_timetabler_slots', (object)[
                            'dayofweek' => $daynum,
                            'starttime' => date('H:i', strtotime("2026-01-01 " . $rawstart)),
                            'endtime'   => date('H:i', strtotime("2026-01-01 " . $rawend)),
                            'type'      => $stype,
                        ]);
                        $inserted++;
                    }

                    fclose($handle);
                    redirect($url, "CSV import completed successfully! {$inserted} time slots imported into the database.");
                } else {
                    fclose($handle);
                    redirect($url, 'Invalid CSV format. Please ensure required columns (dayofweek, starttime, endtime) exist.', null, \core\output\notification::NOTIFY_ERROR);
                }
            } else {
                fclose($handle);
                redirect($url, 'Empty or unreadable CSV file.', null, \core\output\notification::NOTIFY_ERROR);
            }
        }
    } else {
        redirect($url, 'Please select a valid CSV file to upload.', null, \core\output\notification::NOTIFY_ERROR);
    }
}

// -------------------------------------------------------------------
// Action: Guided Bell Schedule Wizard Computation
// -------------------------------------------------------------------
if ($action === 'build_bell_schedule' && confirm_sesskey()) {
    $daystart     = optional_param('day_start', '08:00', PARAM_TEXT);
    $dayend       = optional_param('day_end', '17:00', PARAM_TEXT);
    $periodmins   = optional_param('period_minutes', 60, PARAM_INT);
    $teastart     = optional_param('tea_start', '10:00', PARAM_TEXT);
    $teaend       = optional_param('tea_end', '10:30', PARAM_TEXT);
    $lunchstart   = optional_param('lunch_start', '12:30', PARAM_TEXT);
    $lunchend     = optional_param('lunch_end', '13:30', PARAM_TEXT);
    $activedays   = optional_param_array('days', [1, 2, 3, 4, 5], PARAM_INT);
    $wipeexisting = optional_param('wipe_existing', 1, PARAM_INT);

    if ($wipeexisting) {
        $DB->delete_records('local_schola_timetabler_slots');
    }

    $inserted = 0;

    foreach ($activedays as $day) {
        $currsec = strtotime("2026-01-01 " . $daystart);
        $endsec  = strtotime("2026-01-01 " . $dayend);

        $tstartsec = strtotime("2026-01-01 " . $teastart);
        $tendsec   = strtotime("2026-01-01 " . $teaend);
        $lstartsec = strtotime("2026-01-01 " . $lunchstart);
        $lendsec   = strtotime("2026-01-01 " . $lunchend);

        while ($currsec < $endsec) {
            // Check Morning Tea Break
            if ($tstartsec && $tendsec && $currsec >= $tstartsec && $currsec < $tendsec) {
                $DB->insert_record('local_schola_timetabler_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => date('H:i', $tstartsec),
                    'endtime'   => date('H:i', $tendsec),
                    'type'      => 'break',
                ]);
                $inserted++;
                $currsec = $tendsec;
                continue;
            }

            // Check Lunch Break
            if ($lstartsec && $lendsec && $currsec >= $lstartsec && $currsec < $lendsec) {
                $DB->insert_record('local_schola_timetabler_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => date('H:i', $lstartsec),
                    'endtime'   => date('H:i', $lendsec),
                    'type'      => 'break',
                ]);
                $inserted++;
                $currsec = $lendsec;
                continue;
            }

            // Standard Lesson Period
            $nextsec = $currsec + ($periodmins * 60);
            if ($nextsec > $endsec) {
                break;
            }

            if ($tstartsec && $currsec < $tstartsec && $nextsec > $tstartsec) {
                $nextsec = $tstartsec;
            }
            if ($lstartsec && $currsec < $lstartsec && $nextsec > $lstartsec) {
                $nextsec = $lstartsec;
            }

            $DB->insert_record('local_schola_timetabler_slots', (object)[
                'dayofweek' => $day,
                'starttime' => date('H:i', $currsec),
                'endtime'   => date('H:i', $nextsec),
                'type'      => 'class',
            ]);
            $inserted++;
            $currsec = $nextsec;
        }
    }

    redirect($url, "Bell Schedule applied successfully! {$inserted} time windows generated across " . count($activedays) . " operating days.");
}

// -------------------------------------------------------------------
// Action: Save / Edit Single Time Slot
// -------------------------------------------------------------------
$editslot = null;
if ($action === 'edit' && $id > 0) {
    $editslot = $DB->get_record('local_schola_timetabler_slots', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('save_slot', 0, PARAM_INT)) {
    $editslotid = optional_param('slotid', 0, PARAM_INT);
    $dayofweek  = optional_param('dayofweek', 1, PARAM_INT);
    $starttime  = optional_param('starttime', '', PARAM_TEXT);
    $endtime    = optional_param('endtime', '', PARAM_TEXT);
    $type       = optional_param('type', 'class', PARAM_ALPHA);

    if (!empty($starttime) && !empty($endtime)) {
        $record = (object)[
            'type'      => $type,
            'dayofweek' => $dayofweek,
            'starttime' => $starttime,
            'endtime'   => $endtime,
        ];

        if ($editslotid > 0) {
            $record->id = $editslotid;
            $DB->update_record('local_schola_timetabler_slots', $record);
            redirect($url, 'Time slot updated successfully.');
        } else {
            $DB->insert_record('local_schola_timetabler_slots', $record);
            redirect($url, 'Time slot added successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_schola_timetabler\output\renderer::render_nav_header('slots');

// -------------------------------------------------------------------
// Component 1: Batch CSV Import for Time Slots
// -------------------------------------------------------------------
$sampleurl = new moodle_url($url, ['action' => 'sample_csv']);

echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white rounded-3');
echo html_writer::start_div('card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::tag('h5', '<i class="fa fa-file-csv me-2"></i>' . get_string('slots_csv_import_title', 'local_schola_timetabler'), ['class' => 'mb-0 font-weight-bold']);
echo html_writer::link($sampleurl, '<i class="fa fa-download me-1"></i> ' . get_string('slots_csv_download', 'local_schola_timetabler'), ['class' => 'btn btn-sm btn-outline-light font-weight-bold']);
echo html_writer::end_div();

echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', [
    'method'  => 'post',
    'action'  => $url->out(false),
    'enctype' => 'multipart/form-data',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'import_csv']);

echo html_writer::start_div('row g-3 align-items-center');

echo html_writer::start_div('col-md-7');
echo html_writer::tag('label', get_string('slots_csv_select', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type'     => 'file',
    'name'     => 'csv_file',
    'class'    => 'form-control p-2',
    'accept'   => '.csv,text/csv',
    'required' => 'required',
]);
$colinfo = get_string('slots_csv_supported', 'local_schola_timetabler', '<code>dayofweek</code> (1-7 or Mon-Sun), <code>starttime</code> (08:00), <code>endtime</code> (09:00), <code>type</code> (class, lab, break, exam).');
echo html_writer::tag('div', $colinfo, ['class' => 'form-text text-muted small mt-1']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-5 d-flex flex-column align-items-start gap-2 pt-3');
echo html_writer::start_div('form-check d-flex align-items-center gap-2 mb-2');
echo html_writer::checkbox('wipe_existing', '1', true, ' Wipe existing slots before importing CSV', ['class' => 'form-check-input me-1', 'id' => 'chk_wipe_csv']);
echo html_writer::end_div();

echo html_writer::tag('button', '<i class="fa fa-upload me-1"></i> Import Time Slots CSV', [
    'type'  => 'submit',
    'class' => 'btn btn-primary font-weight-bold px-4 py-2 shadow-sm',
]);
echo html_writer::end_div();

echo html_writer::end_div(); // row
echo html_writer::end_tag('form');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// -------------------------------------------------------------------
// Component 2: Guided Bell Schedule Wizard
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white rounded-3');
echo html_writer::div(html_writer::tag('h5', get_string('slots_guided_wizard', 'local_schola_timetabler'), ['class' => 'mb-0 font-weight-bold text-dark']), 'card-header bg-light p-3 border-bottom');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'build_bell_schedule']);

echo html_writer::start_div('row g-3');

// School Hours & Period Length
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day Start Time', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_start', 'value' => '08:00', 'class' => 'form-control p-2', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day End Time', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'day_end', 'value' => '17:00', 'class' => 'form-control p-2', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', 'Lesson / Period Duration', ['class' => 'form-label font-weight-bold text-dark']);
$periodoptions = [
    45  => '45 Minutes (Secondary / High School Period)',
    50  => '50 Minutes (Standard School Hour)',
    60  => '60 Minutes (Standard University Lecture)',
    90  => '90 Minutes (Extended Modular Lecture)',
    120 => '120 Minutes (2-Hour Double Period)',
    180 => '180 Minutes (3-Hour Block Lecture)',
];
echo html_writer::select($periodoptions, 'period_minutes', 60, false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

// Break Windows
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Morning Tea Break Start', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_start', 'value' => '10:00', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Morning Tea Break End', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_end', 'value' => '10:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Lunch Break Start', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_start', 'value' => '12:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Lunch Break End', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_end', 'value' => '13:30', 'class' => 'form-control p-2']);
echo html_writer::end_div();

// Active Days Selection
echo html_writer::start_div('col-12 mt-3');
echo html_writer::tag('label', 'Operating School Days', ['class' => 'form-label font-weight-bold text-dark d-block']);
$dayslist = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
echo html_writer::start_div('d-flex gap-3 flex-wrap');
foreach ($dayslist as $dnum => $dname) {
    $checked = ($dnum <= 5);
    echo html_writer::start_div('form-check form-check-inline');
    echo html_writer::checkbox('days[]', $dnum, $checked, ' ' . $dname, ['class' => 'form-check-input', 'id' => 'day_chk_' . $dnum]);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row

echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::start_div('form-check d-flex align-items-center gap-2 mb-0');
echo html_writer::checkbox('wipe_existing', '1', true, ' Wipe existing time slots before applying', ['class' => 'form-check-input me-1', 'id' => 'chk_wipe_existing']);
echo html_writer::end_div();
echo html_writer::tag('button', get_string('slots_apply_schedule', 'local_schola_timetabler'), ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Component 3: Add / Edit Single Custom Slot
// -------------------------------------------------------------------
if ($editslot) {
    echo html_writer::start_div('card shadow-sm mb-4 border-primary rounded-3');
    echo html_writer::div(html_writer::tag('h5', get_string('slots_edit_window', 'local_schola_timetabler'), ['class' => 'mb-0 font-weight-bold text-primary']), 'card-header bg-light');
    echo html_writer::start_div('card-body p-4');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save_slot', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slotid', 'value' => $editslot->id]);

    $days = [1 => get_string('monday', 'local_schola_timetabler'), 2 => get_string('tuesday', 'local_schola_timetabler'), 3 => get_string('wednesday', 'local_schola_timetabler'), 4 => get_string('thursday', 'local_schola_timetabler'), 5 => get_string('friday', 'local_schola_timetabler'), 6 => get_string('saturday', 'local_schola_timetabler'), 7 => get_string('sunday', 'local_schola_timetabler')];
    $slottypes = [
        'class' => get_string('slot_type_class_lecture', 'local_schola_timetabler'),
        'lab'   => get_string('slot_type_lab_practical', 'local_schola_timetabler'),
        'break' => get_string('slot_type_break_blockout', 'local_schola_timetabler'),
        'exam'  => get_string('slot_type_examination_period', 'local_schola_timetabler'),
    ];

    echo html_writer::start_div('row g-3');
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('day_of_week', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($days, 'dayofweek', $editslot->dayofweek, false, ['class' => 'form-select']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('start_time', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'starttime', 'class' => 'form-control', 'value' => s($editslot->starttime), 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('end_time', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'endtime', 'class' => 'form-control', 'value' => s($editslot->endtime), 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-5');
    echo html_writer::tag('label', get_string('slot_type_category', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold']);
    echo html_writer::select($slottypes, 'type', $editslot->type, false, ['class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('mt-3 d-flex gap-2');
    echo html_writer::tag('button', 'Update Time Slot', ['type' => 'submit', 'class' => 'btn btn-primary font-weight-bold']);
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-outline-secondary']);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// -------------------------------------------------------------------
// Component 4: Visual Daily Bell Schedule Summary Table
// -------------------------------------------------------------------
$slots = $DB->get_records('local_schola_timetabler_slots', null, 'dayofweek ASC, starttime ASC');

echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2');
echo html_writer::tag('h4', get_string('slots_active_windows', 'local_schola_timetabler') . ' (' . count($slots) . ' ' . get_string('time', 'core') . ' ' . get_string('slots', 'local_schola_timetabler') . ')', ['class' => 'mb-0 font-weight-bold']);

if (!empty($slots)) {
    $clearallurl = new moodle_url($url, ['action' => 'clearall', 'sesskey' => sesskey()]);
    echo html_writer::link($clearallurl, 'Clear All Slots', [
        'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
        'onclick' => 'return confirm("Clear all configured time slots?");',
    ]);
}
echo html_writer::end_div();

if (empty($slots)) {
    $noslotmsg = get_string('slots_no_data', 'local_schola_timetabler');
    echo html_writer::div($noslotmsg, 'alert alert-info shadow-sm p-4 text-center fs-6 rounded-3');
} else {
    $days = [1 => get_string('monday', 'local_schola_timetabler'), 2 => get_string('tuesday', 'local_schola_timetabler'), 3 => get_string('wednesday', 'local_schola_timetabler'), 4 => get_string('thursday', 'local_schola_timetabler'), 5 => get_string('friday', 'local_schola_timetabler'), 6 => get_string('saturday', 'local_schola_timetabler'), 7 => get_string('sunday', 'local_schola_timetabler')];

    $table = new html_table();
    $table->head = ['#', get_string('day_of_week', 'local_schola_timetabler'), get_string('time_window', 'local_schola_timetabler'), get_string('functional_category', 'local_schola_timetabler'), get_string('actions', 'core')];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle bg-white shadow-sm rounded-3'];

    $serial = 1;
    foreach ($slots as $slot) {
        $dayname = $days[$slot->dayofweek] ?? ('Day ' . $slot->dayofweek);
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $slot->id]);
        $editbtn = html_writer::link($editurl, '<i class="fa fa-pen me-1"></i> Edit', ['class' => 'btn btn-sm btn-outline-primary font-weight-bold me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $slot->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, '<i class="fa fa-trash me-1"></i> Delete', [
            'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
            'onclick' => 'return confirm("Delete this time slot?");',
        ]);

        $typebadge = match ($slot->type) {
            'break' => '<span class="badge att-badge-break"><i class="fa fa-coffee me-1"></i> ' . get_string('slot_type_break_blockout', 'local_schola_timetabler') . '</span>',
            'lab'   => '<span class="badge att-badge-lab"><i class="fa fa-flask me-1"></i> ' . get_string('slot_type_lab_practical', 'local_schola_timetabler') . '</span>',
            'exam'  => '<span class="badge att-badge-exam"><i class="fa fa-file-alt me-1"></i> ' . get_string('slot_type_examination_period', 'local_schola_timetabler') . '</span>',
            default => '<span class="badge att-badge-class"><i class="fa fa-book me-1"></i> ' . get_string('slot_type_class_lecture', 'local_schola_timetabler') . '</span>',
        };

        $timestr = s($slot->starttime) . ' &mdash; ' . s($slot->endtime);
        $timebadge = '<span class="badge bg-light text-dark border px-3 py-2 fs-6 font-weight-bold">' .
            '<i class="fa fa-clock me-1 text-primary"></i> ' . $timestr . '</span>';

        $table->data[] = [
            '<span class="font-weight-bold text-dark">#' . $serial++ . '</span>',
            '<strong class="text-dark fs-6">' . $dayname . '</strong>',
            $timebadge,
            $typebadge,
            $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
