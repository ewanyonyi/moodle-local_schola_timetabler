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
 * Manage reusable Schedule Templates for local_schola_timetabler with a user-friendly UI.
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

$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/schola_timetabler/templates.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('template_saved_title', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('template_saved_title', 'local_schola_timetabler'));

// -------------------------------------------------------------------
// Action: Delete Template
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_timetabler_templates', ['id' => $id]);
    redirect($url, 'Schedule template deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Apply Template to Active Time Slots
// -------------------------------------------------------------------
if ($action === 'apply' && $id > 0 && confirm_sesskey()) {
    $template = $DB->get_record('local_schola_timetabler_templates', ['id' => $id]);
    if ($template && !empty($template->slots_json)) {
        $slotsdata = json_decode($template->slots_json, true);
        if (is_array($slotsdata)) {
            $DB->delete_records('local_schola_timetabler_slots');
            $count = 0;
            foreach ($slotsdata as $s) {
                $day = isset($s['dayofweek']) ? (int)$s['dayofweek'] : 1;
                $DB->insert_record('local_schola_timetabler_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => $s['starttime'] ?? '08:00',
                    'endtime'   => $s['endtime'] ?? '09:30',
                    'type'      => $s['type'] ?? 'class',
                ]);
                $count++;
            }

            $slotsurl = new moodle_url('/local/schola_timetabler/slots.php');
            redirect($slotsurl, "Template '{$template->name}' applied successfully! {$count} time slots configured.");
        }
    }
    redirect($url, 'Failed to apply template or invalid template format.', null, \core\output\notification::NOTIFY_ERROR);
}

// -------------------------------------------------------------------
// Action: Save / Create / Edit Template (User-friendly POST array)
// -------------------------------------------------------------------
$edittemplate = null;
if ($action === 'edit' && $id > 0) {
    $edittemplate = $DB->get_record('local_schola_timetabler_templates', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('save_template', 0, PARAM_INT)) {
    $templateid  = optional_param('templateid', 0, PARAM_INT);
    $name        = optional_param('name', '', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);

    $days   = optional_param_array('slot_day', [], PARAM_INT);
    $starts = optional_param_array('slot_start', [], PARAM_TEXT);
    $ends   = optional_param_array('slot_end', [], PARAM_TEXT);
    $types  = optional_param_array('slot_type', [], PARAM_ALPHA);

    $slotsarr = [];
    if (!empty($starts)) {
        for ($i = 0; $i < count($starts); $i++) {
            $dayval = isset($days[$i]) ? (int)$days[$i] : 0;
            $start  = $starts[$i] ?? '08:00';
            $end    = $ends[$i] ?? '09:30';
            $type   = $types[$i] ?? 'class';

            if (!empty($start) && !empty($end)) {
                if ($dayval === 0) { // All Weekdays Mon-Fri
                    for ($d = 1; $d <= 5; $d++) {
                        $slotsarr[] = [
                            'dayofweek' => $d,
                            'starttime' => $start,
                            'endtime'   => $end,
                            'type'      => $type,
                        ];
                    }
                } else {
                    $slotsarr[] = [
                        'dayofweek' => $dayval,
                        'starttime' => $start,
                        'endtime'   => $end,
                        'type'      => $type,
                    ];
                }
            }
        }
    }

    if (!empty($name)) {
        if (empty($slotsarr)) {
            // Default 7am-6pm standard day if empty
            for ($d = 1; $d <= 5; $d++) {
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '07:00', 'endtime' => '08:30', 'type' => 'class'];
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '08:30', 'endtime' => '10:00', 'type' => 'class'];
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '10:00', 'endtime' => '10:30', 'type' => 'break'];
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '10:30', 'endtime' => '12:00', 'type' => 'class'];
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '12:00', 'endtime' => '13:30', 'type' => 'break'];
                $slotsarr[] = ['dayofweek' => $d, 'starttime' => '13:30', 'endtime' => '15:00', 'type' => 'class'];
            }
        }

        $now = time();
        $record = (object)[
            'name'         => $name,
            'description'  => $description,
            'slots_json'   => json_encode($slotsarr),
            'timemodified' => $now,
        ];

        if ($templateid > 0) {
            $record->id = $templateid;
            $DB->update_record('local_schola_timetabler_templates', $record);
            redirect($url, "Schedule template '{$name}' updated successfully.");
        } else {
            $record->timecreated = $now;
            $DB->insert_record('local_schola_timetabler_templates', $record);
            redirect($url, "Schedule template '{$name}' created successfully.");
        }
    }
}

echo $OUTPUT->header();

echo \local_schola_timetabler\output\renderer::render_nav_header('templates');

// -------------------------------------------------------------------
// Friendly Template Builder Form Card
// -------------------------------------------------------------------
$cardtitle = $edittemplate
    ? get_string('template_edit_title', 'local_schola_timetabler')
    : get_string('template_create_title', 'local_schola_timetabler');
$btnlabel  = $edittemplate
    ? get_string('template_update_button', 'local_schola_timetabler')
    : get_string('template_save_button', 'local_schola_timetabler');

// Extract initial rows for form
$initialrows = [];
if ($edittemplate && !empty($edittemplate->slots_json)) {
    $decoded = json_decode($edittemplate->slots_json, true);
    if (is_array($decoded)) {
        // Group identical time & type windows across days if all 1-5 present
        $grouped = [];
        foreach ($decoded as $item) {
            $key = $item['starttime'] . '-' . $item['endtime'] . '-' . $item['type'];
            $grouped[$key]['days'][] = $item['dayofweek'];
            $grouped[$key]['item'] = $item;
        }

        foreach ($grouped as $g) {
            $days = $g['days'];
            $item = $g['item'];
            $dayval = (count($days) >= 5 && in_array(1, $days) && in_array(5, $days)) ? 0 : ($days[0] ?? 1);
            $initialrows[] = [
                'day'   => $dayval,
                'start' => $item['starttime'],
                'end'   => $item['endtime'],
                'type'  => $item['type'],
            ];
        }
    }
}

if (empty($initialrows)) {
    $initialrows = [
        ['day' => 0, 'start' => '07:00', 'end' => '08:30', 'type' => 'class'],
        ['day' => 0, 'start' => '08:30', 'end' => '10:00', 'type' => 'class'],
        ['day' => 0, 'start' => '10:00', 'end' => '10:30', 'type' => 'break'],
        ['day' => 0, 'start' => '10:30', 'end' => '12:00', 'type' => 'class'],
        ['day' => 0, 'start' => '12:00', 'end' => '13:30', 'type' => 'break'],
        ['day' => 0, 'start' => '13:30', 'end' => '15:00', 'type' => 'class'],
    ];
}

echo html_writer::start_div('card shadow-sm mb-4 bg-white border-0');
echo html_writer::div(html_writer::tag('h5', $cardtitle, ['class' => 'mb-0 font-weight-bold']), 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save_template', 'value' => '1']);
if ($edittemplate) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'templateid', 'value' => $edittemplate->id]);
}

echo html_writer::start_div('row g-3 mb-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('template_name', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'name', 'class' => 'form-control p-2',
    'placeholder' => 'e.g. Standard Semester 1 Class Grid', 'required' => 'required',
    'value' => $edittemplate ? s($edittemplate->name) : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', get_string('template_description', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'description', 'class' => 'form-control p-2',
    'placeholder' => 'e.g. Mon-Fri 1.5hr classes with Morning Tea & Lunch breaks',
    'value' => $edittemplate ? s($edittemplate->description) : '',
]);
echo html_writer::end_div();
echo html_writer::end_div();

// Dynamic Time Slot Rows Table
echo html_writer::start_div('col-12 mt-3');
echo html_writer::tag('label', get_string('template_configuration', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark fs-6']);
echo html_writer::start_div('table-responsive border rounded bg-light p-2 mb-2');
echo html_writer::start_tag('table', ['class' => 'table table-sm align-middle mb-0 bg-white', 'id' => 'slot-builder-table']);
echo html_writer::start_tag('thead', ['class' => 'table-dark']);
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('template_day_schedule', 'local_schola_timetabler'));
echo html_writer::tag('th', get_string('start_time', 'local_schola_timetabler'), ['style' => 'width: 150px;']);
echo html_writer::tag('th', get_string('end_time', 'local_schola_timetabler'), ['style' => 'width: 150px;']);
echo html_writer::tag('th', get_string('template_slot_type', 'local_schola_timetabler'));
echo html_writer::tag('th', get_string('col_action', 'local_schola_timetabler'), ['style' => 'width: 90px;', 'class' => 'text-center']);
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::tag('tbody', '', ['id' => 'slot-rows-container']);
echo html_writer::end_tag('table');
echo html_writer::end_div();

echo html_writer::tag('button', get_string('template_add_row', 'local_schola_timetabler'), [
    'type' => 'button', 'class' => 'btn btn-sm btn-outline-primary font-weight-bold shadow-sm',
    'onclick' => 'addSlotRow();',
]);
echo html_writer::end_div();

echo html_writer::start_div('mt-4 pt-3 border-top d-flex gap-2');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
if ($edittemplate) {
    echo html_writer::link($url, get_string('template_cancel_edit', 'local_schola_timetabler'), ['class' => 'btn btn-outline-secondary px-4 py-2']);
}
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// Inline JavaScript for dynamic slot row builder
echo '
<script>
function addSlotRow(day = 0, start = "08:00", end = "09:30", type = "class") {
    const container = document.getElementById("slot-rows-container");
    const tr = document.createElement("tr");
    tr.innerHTML = ""
        + "<td><select name=\"slot_day[]\" class=\"form-select form-select-sm\">"
        + "<option value=\"0\" " + (day == 0 ? "selected" : "") + ">All Weekdays (Mon-Fri)</option>"
        + "<option value=\"1\" " + (day == 1 ? "selected" : "") + ">Monday</option>"
        + "<option value=\"2\" " + (day == 2 ? "selected" : "") + ">Tuesday</option>"
        + "<option value=\"3\" " + (day == 3 ? "selected" : "") + ">Wednesday</option>"
        + "<option value=\"4\" " + (day == 4 ? "selected" : "") + ">Thursday</option>"
        + "<option value=\"5\" " + (day == 5 ? "selected" : "") + ">Friday</option>"
        + "<option value=\"6\" " + (day == 6 ? "selected" : "") + ">Saturday</option>"
        + "<option value=\"7\" " + (day == 7 ? "selected" : "") + ">Sunday</option></select></td>"
        + "<td><input type=\"time\" name=\"slot_start[]\" value=\"" + start + "\" class=\"form-control form-control-sm\" required></td>"
        + "<td><input type=\"time\" name=\"slot_end[]\" value=\"" + end + "\" class=\"form-control form-control-sm\" required></td>"
        + "<td><select name=\"slot_type[]\" class=\"form-select form-select-sm\">"
        + "<option value=\"class\" " + (type === "class" ? "selected" : "") + ">Class Lecture (Standard Teaching Window)</option>"
        + "<option value=\"lab\" " + (type === "lab" ? "selected" : "") + ">Laboratory Practical (Extended Block)</option>"
        + "<option value=\"break\" " + (type === "break" ? "selected" : "") + ">Break / Blockout (Lunch, Tea Break, Assembly)</option>"
        + "<option value=\"exam\" " + (type === "exam" ? "selected" : "") + ">Examination Period (Dedicated Exam Block)</option></select></td>"
        + "<td class=\"text-center\"><button type=\"button\" class=\"btn btn-sm btn-outline-danger py-0 px-2\" onclick=\"this.parentNode.parentNode.remove();\" title=\"Remove slot window\">&times;</button></td>";
    container.appendChild(tr);
}

document.addEventListener("DOMContentLoaded", () => {
    const initialData = ' . json_encode($initialrows) . ';
    if (initialData && initialData.length > 0) {
        initialData.forEach(r => addSlotRow(r.day, r.start, r.end, r.type));
    } else {
        addSlotRow(0, "07:00", "08:30", "class");
        addSlotRow(0, "08:30", "10:00", "class");
        addSlotRow(0, "10:00", "10:30", "break");
        addSlotRow(0, "10:30", "12:00", "class");
        addSlotRow(0, "12:00", "13:30", "break");
        addSlotRow(0, "13:30", "15:00", "class");
    }
});
</script>';

// -------------------------------------------------------------------
// Saved Schedule Templates Repository Table
// -------------------------------------------------------------------
$templates = $DB->get_records('local_schola_timetabler_templates', null, 'name ASC');

$templatesheading = get_string('template_saved_count', 'local_schola_timetabler', count($templates));
echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3');
echo html_writer::tag('h4', $templatesheading, ['class' => 'mb-0 font-weight-bold']);
echo html_writer::end_div();

if (empty($templates)) {
    echo html_writer::div(get_string('template_empty_state', 'local_schola_timetabler'), 'alert alert-info shadow-sm');
} else {
    $table = new html_table();
    $table->head = [
        get_string('template_name', 'local_schola_timetabler'),
        get_string('template_description', 'local_schola_timetabler'),
        get_string('slots_active_windows', 'local_schola_timetabler'),
        get_string('lastmodified', 'core'),
        get_string('col_action', 'local_schola_timetabler'),
    ];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle bg-white shadow-sm'];

    foreach ($templates as $t) {
        $slotsarr = json_decode($t->slots_json, true);
        $slotcount = is_array($slotsarr) ? count($slotsarr) : 0;
        $modified  = date('Y-m-d H:i', $t->timemodified);

        $applyurl = new moodle_url($url, ['action' => 'apply', 'id' => $t->id, 'sesskey' => sesskey()]);
        $applybtn = html_writer::link($applyurl, get_string('template_apply_button', 'local_schola_timetabler'), [
            'class' => 'btn btn-sm btn-success font-weight-bold me-2',
            'onclick' => "return confirm('Apply template \"{$t->name}\"? This will configure active weekly time slots.');",
        ]);

        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $t->id]);
        $editbtn = html_writer::link($editurl, get_string('template_edit_button', 'local_schola_timetabler'), ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $t->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, get_string('template_delete_button', 'local_schola_timetabler'), [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => "return confirm('Delete template \"{$t->name}\"?');",
        ]);

        $table->data[] = [
            '<strong>' . s($t->name) . '</strong>',
            s($t->description ?: '&mdash;'),
            '<span class="badge bg-secondary px-2 py-1 fs-6">' . get_string('template_slot_count', 'local_schola_timetabler', $slotcount) . '</span>',
            $modified,
            $applybtn . $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
