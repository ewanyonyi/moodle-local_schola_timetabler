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
 * Manage venues and rooms for local_schola_timetabler.
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
$id = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/schola_timetabler/rooms.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_rooms', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('manage_rooms', 'local_schola_timetabler'));

// -------------------------------------------------------------------
// Action: Download Sample CSV Template
// -------------------------------------------------------------------
if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campus_rooms_sample.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        get_string('rooms_csv_name', 'local_schola_timetabler'),
        get_string('rooms_csv_capacity', 'local_schola_timetabler'),
        get_string('rooms_csv_is_lab', 'local_schola_timetabler'),
    ]);
    fputcsv($output, ['Main Auditorium 101', '350', '0']);
    fputcsv($output, ['Computer Science Lab 2', '45', '1']);
    fputcsv($output, ['Engineering Lecture Hall A', '180', '0']);
    fputcsv($output, ['Biology Research Lab', '30', '1']);
    fputcsv($output, ['Executive Seminar Room', '60', '0']);
    fclose($output);
    exit;
}

// -------------------------------------------------------------------
// Action: Delete Single Room
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0) {
    $confirm = optional_param('confirm', 0, PARAM_INT);
    $room = $DB->get_record('local_schola_timetabler_rooms', ['id' => $id]);
    if ($room) {
        if ($confirm && confirm_sesskey()) {
            $DB->delete_records('local_schola_timetabler_rooms', ['id' => $id]);
            redirect($url, 'Room deleted successfully.');
        }
        $confirmurl = new moodle_url($url, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
        $msg = get_string('confirm_delete_room', 'local_schola_timetabler', s($room->name));
        echo $OUTPUT->header();
        echo html_writer::div($OUTPUT->confirm($msg, $confirmurl, $url), 'mt-4');
        echo $OUTPUT->footer();
        exit;
    }
}

// -------------------------------------------------------------------
// Action: Import Rooms from CSV File
// -------------------------------------------------------------------
if ($action === 'import_csv' && confirm_sesskey()) {
    if (!empty($_FILES['room_file']['tmp_name']) && is_uploaded_file($_FILES['room_file']['tmp_name'])) {
        $handle = fopen($_FILES['room_file']['tmp_name'], 'r');
        if ($handle !== false) {
            $imported = 0;
            $skipped = 0;
            $rowcount = 0;

            // Read first line to detect delimiter (comma, semicolon, tab)
            $firstline = fgets($handle);
            rewind($handle);
            $delimiter = ',';
            if (strpos($firstline, ';') !== false) {
                $delimiter = ';';
            } else if (strpos($firstline, "\t") !== false) {
                $delimiter = "\t";
            }

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rowcount++;
                if (empty($data) || count($data) < 2) {
                    continue;
                }

                // Skip header row if present
                $col0 = strtolower(trim($data[0]));
                if ($rowcount === 1 && in_array($col0, ['name', 'room_name', 'room', 'venue', 'title', 'room name'])) {
                    continue;
                }

                $name = trim($data[0]);
                $capacity = isset($data[1]) ? (int)trim($data[1]) : 0;
                $rawlab = isset($data[2]) ? strtolower(trim($data[2])) : '0';
                $islab = in_array($rawlab, ['1', 'yes', 'true', 'lab', 'laboratory', 'studio']) ? 1 : 0;

                if (!empty($name) && $capacity > 0) {
                    $record = (object)[
                        'name' => $name,
                        'capacity' => $capacity,
                        'is_lab' => $islab,
                    ];
                    $DB->insert_record('local_schola_timetabler_rooms', $record);
                    $imported++;
                } else {
                    $skipped++;
                }
            }
            fclose($handle);

            $msg = "Successfully imported {$imported} campus rooms from CSV.";
            if ($skipped > 0) {
                $msg .= " ({$skipped} invalid rows skipped).";
            }
            redirect($url, $msg);
        }
    }
    redirect($url, 'Error reading uploaded CSV file.', null, \core\output\notification::NOTIFY_ERROR);
}

$editroom = null;
if ($action === 'edit' && $id > 0) {
    $editroom = $DB->get_record('local_schola_timetabler_rooms', ['id' => $id]);
}

// -------------------------------------------------------------------
// Action: Manual Single Room Form Submit
// -------------------------------------------------------------------
if ($data = data_submitted() && confirm_sesskey() && empty($action)) {
    $editid = optional_param('roomid', 0, PARAM_INT);
    $name = optional_param('name', '', PARAM_TEXT);
    $capacity = optional_param('capacity', 0, PARAM_INT);
    $islab = optional_param('is_lab', 0, PARAM_INT);

    if (!empty($name) && $capacity > 0) {
        $record = (object)[
            'name' => $name,
            'capacity' => $capacity,
            'is_lab' => $islab ? 1 : 0,
        ];

        if ($editid > 0) {
            $record->id = $editid;
            $DB->update_record('local_schola_timetabler_rooms', $record);
            redirect($url, 'Room updated successfully.');
        } else {
            $DB->insert_record('local_schola_timetabler_rooms', $record);
            redirect($url, 'Room added successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_schola_timetabler\output\renderer::render_nav_header('rooms');

$cardheader = $editroom ? get_string('edit_campus_room', 'local_schola_timetabler') : get_string('add_single_room', 'local_schola_timetabler');
$btnlabel = $editroom ? get_string('update_room', 'local_schola_timetabler') : get_string('save_room', 'local_schola_timetabler');

$templateurl = new moodle_url($url, ['action' => 'download_template']);

// -------------------------------------------------------------------
// Grid Row: Single Room Form (Left) & CSV Import Card (Right - Free OSS CSV Import)
// -------------------------------------------------------------------
echo html_writer::start_div('row g-4 mb-4');

// Manual Single Room Form Column
$formcol = 'col-lg-7';
echo html_writer::start_div($formcol);
echo html_writer::start_div('card shadow-sm h-100');
$cardheaderhtml = html_writer::tag('h5', '<i class="fa fa-plus-circle me-2 text-primary"></i>' . $cardheader, ['class' => 'mb-0 font-weight-bold']);
echo html_writer::div($cardheaderhtml, 'card-header bg-light p-3 border-bottom');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
if ($editroom) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'roomid', 'value' => $editroom->id]);
}

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('rooms_venue_name', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold small']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'name', 'class' => 'form-control',
    'placeholder' => get_string('rooms_venue_name_placeholder', 'local_schola_timetabler'), 'required' => 'required',
    'value' => $editroom ? s($editroom->name) : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('rooms_seating_capacity', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold small']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'capacity', 'class' => 'form-control',
    'placeholder' => '100', 'required' => 'required', 'min' => '1',
    'value' => $editroom ? $editroom->capacity : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-12');
echo html_writer::start_div('form-check form-switch mt-2');
$checkboxattrs = [
    'type' => 'checkbox', 'name' => 'is_lab', 'value' => '1',
    'class' => 'form-check-input', 'id' => 'is_lab_check',
];
if ($editroom && $editroom->is_lab) {
    $checkboxattrs['checked'] = 'checked';
}
echo html_writer::empty_tag('input', $checkboxattrs);
echo html_writer::tag('label', get_string('rooms_is_lab_label', 'local_schola_timetabler'), ['class' => 'form-check-label font-weight-bold small', 'for' => 'is_lab_check']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-4 pt-3 border-top');
echo html_writer::tag('button', '<i class="fa fa-save me-1"></i> ' . $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 me-2']);
if ($editroom) {
    echo html_writer::link($url, get_string('cancel_edit', 'local_schola_timetabler'), ['class' => 'btn btn-secondary px-3 py-2']);
}
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // form col

echo html_writer::start_div('col-lg-5');
echo html_writer::start_div('card shadow-sm h-100 bg-white rounded-3');
$importhead = '<i class="fa fa-file-csv me-2 text-primary"></i>' . get_string('rooms_csv_import_title', 'local_schola_timetabler');
$headerh5 = html_writer::tag('h5', $importhead, ['class' => 'mb-0 font-weight-bold text-primary']);
$samplebtn = html_writer::link($templateurl, '<i class="fa fa-download me-1"></i> ' . get_string('sample_csv', 'local_schola_timetabler'), [
    'class' => 'btn btn-sm btn-outline-primary font-weight-bold',
    'title' => get_string('rooms_download_sample_title', 'local_schola_timetabler'),
]);
echo html_writer::div($headerh5 . $samplebtn, 'card-header bg-light p-3 border-bottom d-flex justify-content-between align-items-center');

echo html_writer::start_div('card-body p-4 d-flex flex-column justify-content-between');

    $importdesc = get_string('rooms_import_desc', 'local_schola_timetabler');
    echo html_writer::tag('p', $importdesc, ['class' => 'text-muted small mb-3']);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $url->out(false),
        'enctype' => 'multipart/form-data',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'import_csv']);

    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('select_csv_file', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold small']);
    echo html_writer::empty_tag('input', [
        'type' => 'file', 'name' => 'room_file', 'class' => 'form-control',
        'accept' => '.csv,.txt', 'required' => 'required',
    ]);
    echo html_writer::end_div();

    // Required columns box
    echo html_writer::start_div('bg-light p-3 rounded mb-3 border');
    echo html_writer::div('<strong>' . get_string('rooms_csv_header_required', 'local_schola_timetabler') . '</strong>', 'small text-dark mb-1');
    echo html_writer::div('<code>Name, Capacity, Is Lab</code>', 'small text-primary font-weight-bold mb-1');
    echo html_writer::div(get_string('rooms_csv_example', 'local_schola_timetabler', '<code>Science Lab 101, 40, 1</code>'), 'text-muted extra-small');
    echo html_writer::end_div();

    echo html_writer::tag('button', '<i class="fa fa-upload me-1"></i> ' . get_string('import_rooms_csv', 'local_schola_timetabler'), [
        'type' => 'submit', 'class' => 'btn btn-primary w-100 font-weight-bold py-2 shadow-sm',
    ]);

    echo html_writer::end_tag('form');

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // col-lg-5

    echo html_writer::end_div(); // row

    // -------------------------------------------------------------------
    // Component: Configured Campus Venues Table
    // -------------------------------------------------------------------
    $rooms = $DB->get_records('local_schola_timetabler_rooms', null, 'id DESC');

    echo html_writer::start_div('card border-0 shadow-sm bg-white rounded-3');
    echo html_writer::start_div('card-header bg-light p-3 border-bottom d-flex justify-content-between align-items-center');
    $titlelabel = '<i class="fa fa-building me-2 text-secondary"></i>Configured Campus Venues (' . count($rooms) . ')';
    echo html_writer::tag('h5', $titlelabel, ['class' => 'mb-0 font-weight-bold text-dark']);
    echo html_writer::end_div();

    echo html_writer::start_div('card-body p-4');

    if (empty($rooms)) {
        echo html_writer::div(get_string('no_rooms', 'local_schola_timetabler'), 'alert alert-info rounded-3 text-center p-4 fs-6');
    } else {
        $table = new html_table();
        $table->head = ['ID', 'Room Name', 'Capacity', 'Type', 'Actions'];
        $table->attributes = ['class' => 'table table-striped table-bordered align-middle mb-0 bg-white'];

        foreach ($rooms as $room) {
            $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $room->id]);
            $editbtn = html_writer::link($editurl, '<i class="fa fa-pen me-1"></i> Edit', ['class' => 'btn btn-sm btn-outline-primary me-2 font-weight-bold']);

            $deleteurl = new moodle_url($url, ['action' => 'delete', 'id' => $room->id]);
            $deletebtn = html_writer::link($deleteurl, '<i class="fa fa-trash me-1"></i> Delete', [
                'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
            ]);

            $isonline = (stripos($room->name, 'online') !== false
                || stripos($room->name, 'virtual') !== false
                || stripos($room->name, 'zoom') !== false
                || stripos($room->name, 'teams') !== false);
            if ($isonline) {
                $typebadge = '<span class="badge online-room-badge"><i class="fa fa-globe me-1"></i> Virtual / Online Space</span>';
            } else {
                $typebadge = $room->is_lab
                ? '<span class="badge att-badge-lab"><i class="fa fa-flask me-1"></i> Lab / Studio</span>'
                : '<span class="badge att-badge-lecture"><i class="fa fa-chalkboard me-1"></i> Lecture Hall</span>';
            }

            $table->data[] = [
            '<span class="font-weight-bold text-dark">#' . $room->id . '</span>',
            '<strong class="text-dark fs-6">' . s($room->name) . '</strong>',
            '<span class="badge att-badge-capacity"><i class="fa fa-users me-1"></i> ' . $room->capacity . ' Seats</span>',
            $typebadge,
            $editbtn . $deletebtn,
            ];
        }
        echo html_writer::table($table);
    }

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card

    echo $OUTPUT->footer();
