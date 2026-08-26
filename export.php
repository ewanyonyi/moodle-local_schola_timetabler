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
 * Export and Print engine for local_schola_timetabler.
 * Supports CSV file download and print-ready HTML/PDF views.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
if (class_exists('context_system')) {
    $context = context_system::instance();
    require_capability('local/schola_timetabler:manage', $context);
}

$action       = optional_param('action', 'print', PARAM_ALPHA);
$roomid       = optional_param('roomid', 0, PARAM_INT);
$teacherid    = optional_param('teacherid', 0, PARAM_INT);
$scheduletype = optional_param('type', 'all', PARAM_ALPHA);
$categoryid   = optional_param('categoryid', 0, PARAM_INT);

global $DB;

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];

$where = [];
$params = [];
if ($scheduletype !== 'all') {
    $where[] = 's.schedule_type = :stype';
    $params['stype'] = $scheduletype;
}
if ($categoryid > 0) {
    $where[] = 'c.category = :categoryid';
    $params['categoryid'] = $categoryid;
}
if ($roomid > 0) {
    $where[] = 's.roomid = :roomid';
    $params['roomid'] = $roomid;
}
if ($teacherid > 0) {
    $where[] = 's.teacherid = :teacherid';
    $params['teacherid'] = $teacherid;
}
$wherestr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT s.id, s.schedule_type, c.shortname AS coursecode, c.fullname AS coursename,
               r.name AS roomname, sl.dayofweek, sl.starttime, sl.endtime,
               u.firstname, u.lastname
          FROM {local_schola_timetabler_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_schola_timetabler_rooms} r ON r.id = s.roomid
          JOIN {local_schola_timetabler_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
          {$wherestr}
      ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

$schedules = $DB->get_records_sql($sql, $params);

// -------------------------------------------------------------------
// Action: CSV Export
// -------------------------------------------------------------------
if ($action === 'csv') {
    $filename = 'academic_timetable_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Schedule ID', 'Schedule Type', 'Course Code', 'Course Name', 'Venue / Room', 'Day of Week', 'Start Time', 'End Time', 'Instructor']);

    foreach ($schedules as $s) {
        $dayname = $days[$s->dayofweek] ?? ('Day ' . $s->dayofweek);
        $teacher = (!empty($s->firstname) || !empty($s->lastname)) ? fullname($s) : 'Unassigned';
        fputcsv($output, [
            $s->id,
            strtoupper($s->schedule_type),
            $s->coursecode,
            $s->coursename,
            $s->roomname,
            $dayname,
            $s->starttime,
            $s->endtime,
            $teacher,
        ]);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------------
// Action: Print / PDF HTML View
// -------------------------------------------------------------------
$autoprint = optional_param('autoprint', 0, PARAM_INT);
$site = get_site();
$currentstrategy = get_config('local_schola_timetabler', 'day_distribution') ?: 'balanced';
$maxday = ($currentstrategy === 'mon_to_sat') ? 6 : 5;
$matrixdays = array_slice($days, 0, $maxday, true);

// Extract unique time windows from configured slots & schedules
$allslots = $DB->get_records('local_schola_timetabler_slots', null, 'starttime ASC');
$timeblocks = [];
$breakwindows = [];
foreach ($allslots as $sl) {
    $window = s($sl->starttime) . ' - ' . s($sl->endtime);
    if (!in_array($window, $timeblocks)) {
        $timeblocks[] = $window;
    }
    if ($sl->type === 'break') {
        $breakwindows[$window] = true;
    }
}
foreach ($schedules as $s) {
    $window = s($s->starttime) . ' - ' . s($s->endtime);
    if (!in_array($window, $timeblocks)) {
        $timeblocks[] = $window;
    }
}
sort($timeblocks);

// Group matrix entries
$matrix = [];
foreach ($schedules as $s) {
    $window = s($s->starttime) . ' - ' . s($s->endtime);
    $matrix[$window][$s->dayofweek][] = $s;
}

$cssurl = (new moodle_url('/theme/styles.php/boost/1/all'))->out(false);
$csvurl = new moodle_url('/local/schola_timetabler/export.php', [
    'action' => 'csv',
    'type' => $scheduletype,
    'categoryid' => $categoryid,
]);

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Timetable - ' . s($site->fullname) . '</title>
    <link rel="stylesheet" href="' . $cssurl . '">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fff; color: #000; padding: 20px; }
        .header-print { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header-print h2 { margin: 0; font-size: 24px; font-weight: bold; }
        .header-print p { margin: 5px 0 0; color: #555; font-size: 14px; }
        .table-matrix { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
        .table-matrix th, .table-matrix td { border: 1px solid #666; padding: 6px; text-align: center; vertical-align: top; }
        .table-matrix th { background-color: #f2f2f2; font-size: 12px; font-weight: bold; }
        .cell-entry { background: #fafafa; border: 1px solid #ccc; border-radius: 4px; padding: 4px; margin-bottom: 4px; text-align: left; }
        .cell-entry strong { color: #000; display: block; font-size: 11px; }
        .cell-entry small { color: #444; display: block; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 d-flex gap-2">
    <button onclick="window.print();" class="btn btn-primary font-weight-bold">
        Print / Save as PDF
    </button>
    <a href="' . $csvurl->out(false) . '" class="btn btn-success font-weight-bold">
        Export to CSV
    </a>
    <button onclick="window.close();" class="btn btn-outline-secondary">
        Close Window
    </button>
</div>

<div class="header-print">
    <h2>' . s($site->fullname) . '</h2>
    <p><strong>Official Academic Schedule Profile: ' . strtoupper($scheduletype) . '</strong> | Generated on ' . date('F j, Y, g:i a') . '</p>
</div>';

if (empty($schedules)) {
    echo '<div class="alert alert-warning text-center">No schedule allocations found matching the selected criteria.</div>';
} else {
    echo '<table class="table-matrix">
        <thead>
            <tr>
                <th style="width: 12%;">Time Window</th>';
    foreach ($matrixdays as $dayname) {
        echo '<th>' . s($dayname) . '</th>';
    }
    echo '  </tr>
        </thead>
        <tbody>';
    foreach ($timeblocks as $timeblock) {
        $isbreak = isset($breakwindows[$timeblock]);
        echo '<tr>
            <td style="background:#f9f9f9; font-weight:bold;">' . s($timeblock) . '</td>';
        if ($isbreak) {
            echo '<td colspan="' . count($matrixdays) . '" style="background:#f1f5f9; color:#64748b; font-weight:bold; font-style:italic; padding:10px;">
                    INSTITUTIONAL BREAK / BLOCKOUT &mdash; NO CLASSES OR EXAMS
                </td>';
        } else {
            foreach (array_keys($matrixdays) as $day) {
                echo '<td>';
                $entries = $matrix[$timeblock][$day] ?? [];
                if (empty($entries)) {
                    echo '&mdash;';
                } else {
                    foreach ($entries as $e) {
                        $teacher = (!empty($e->firstname) || !empty($e->lastname)) ? fullname($e) : 'Unassigned';
                        echo '<div class="cell-entry">
                            <strong>' . s($e->coursecode) . '</strong>
                            <small>' . s($e->roomname) . '</small>
                            <small>' . s($teacher) . '</small>
                        </div>';
                    }
                }
                echo '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</tbody>
    </table>';
}

if ($autoprint) {
    echo '<script>
        window.addEventListener("DOMContentLoaded", () => {
            window.print();
        });
    </script>';
}

echo '</body>
</html>';
