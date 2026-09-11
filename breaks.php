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
 * Institutional Breaks Masterlist Studio for Schola Timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$context = context_system::instance();
require_login();
require_capability('local/schola_timetabler:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/schola_timetabler/breaks.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('breaks_masterlist_title', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('breaks_masterlist_title', 'local_schola_timetabler'));

// -------------------------------------------------------------------
// Ensure Table Schema Support (name field on local_schola_timetabler_slots)
// -------------------------------------------------------------------
$dbman = $DB->get_manager();
$table = new xmldb_table('local_schola_timetabler_slots');
$field = new xmldb_field('name', XMLDB_TYPE_CHAR, '100', null, false, false, '');
if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
}

// -------------------------------------------------------------------
// Handle Actions: Add, Edit, Delete
// -------------------------------------------------------------------
if ($action === 'add' && data_submitted() && confirm_sesskey()) {
    $title       = required_param('title', PARAM_TEXT);
    $daywindow   = optional_param('daywindow', 0, PARAM_INT); // 0 = All Days, 1 = Mon ... 7 = Sun
    $starttime   = required_param('starttime', PARAM_TEXT);
    $endtime     = required_param('endtime', PARAM_TEXT);
    $scheduletype = optional_param('scheduletype', 'class', PARAM_ALPHA);

    $record = (object)[
        'type'      => 'break',
        'name'      => s($title),
        'dayofweek' => $daywindow,
        'starttime' => s($starttime),
        'endtime'   => s($endtime),
    ];
    $DB->insert_record('local_schola_timetabler_slots', $record);
    redirect($url, get_string('break_added_success', 'local_schola_timetabler'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'edit' && data_submitted() && confirm_sesskey()) {
    $id          = required_param('id', PARAM_INT);
    $title       = required_param('title', PARAM_TEXT);
    $daywindow   = optional_param('daywindow', 0, PARAM_INT);
    $starttime   = required_param('starttime', PARAM_TEXT);
    $endtime     = required_param('endtime', PARAM_TEXT);

    $record = (object)[
        'id'        => $id,
        'type'      => 'break',
        'name'      => s($title),
        'dayofweek' => $daywindow,
        'starttime' => s($starttime),
        'endtime'   => s($endtime),
    ];
    $DB->update_record('local_schola_timetabler_slots', $record);
    redirect($url, get_string('break_updated_success', 'local_schola_timetabler'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_schola_timetabler_slots', ['id' => $id, 'type' => 'break']);
    redirect($url, get_string('break_deleted_success', 'local_schola_timetabler'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Render Unified Top Executive Navigation Bar
echo local_schola_timetabler\output\renderer::render_nav_header('breaks');

// Days Mapping
$daynames = [
    0 => get_string('all_days', 'local_schola_timetabler'),
    1 => get_string('monday_only', 'local_schola_timetabler'),
    2 => get_string('tuesday_only', 'local_schola_timetabler'),
    3 => get_string('wednesday_only', 'local_schola_timetabler'),
    4 => get_string('thursday_only', 'local_schola_timetabler'),
    5 => get_string('friday_only', 'local_schola_timetabler'),
    6 => get_string('saturday_only', 'local_schola_timetabler'),
    7 => get_string('sunday_only', 'local_schola_timetabler'),
];

// Fetch all break slots
$breaks = $DB->get_records('local_schola_timetabler_slots', ['type' => 'break'], 'starttime ASC');
if (empty($breaks)) {
    $DB->insert_record('local_schola_timetabler_slots', (object)[
        'type'      => 'break',
        'name'      => get_string('tea_break', 'local_schola_timetabler'),
        'dayofweek' => 0,
        'starttime' => '08:00',
        'endtime'   => '09:00',
    ]);
    $DB->insert_record('local_schola_timetabler_slots', (object)[
        'type'      => 'break',
        'name'      => get_string('lunch_break', 'local_schola_timetabler'),
        'dayofweek' => 0,
        'starttime' => '13:00',
        'endtime'   => '14:00',
    ]);
    $breaks = $DB->get_records('local_schola_timetabler_slots', ['type' => 'break'], 'starttime ASC');
}

// -------------------------------------------------------------------
// Executive Header Studio Banner
// -------------------------------------------------------------------
echo '
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 font-monospace small rounded-pill mb-2">'
                . get_string('institutional_resource_hub', 'local_schola_timetabler') . '</span>
            <h4 class="fw-bold text-dark mb-1">' . get_string('institutional_master_data_studio', 'local_schola_timetabler') . '</h4>
            <p class="text-muted small mb-0">' . get_string('institutional_master_data_desc', 'local_schola_timetabler') . '</p>
        </div>
    </div>
</div>

<!-- Main Institutional Breaks Masterlist Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-3 border-bottom gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-1">' . get_string('breaks_masterlist_title', 'local_schola_timetabler') . '</h5>
            <p class="text-muted small mb-0">' . get_string('breaks_masterlist_desc', 'local_schola_timetabler') . '</p>
        </div>
        <div>
            <button type="button" class="btn btn-emerald rounded-pill font-weight-bold px-4 py-2 d-inline-flex align-items-center shadow-sm"
                    data-bs-toggle="modal" data-bs-target="#addBreakModal">
                <i class="fa fa-plus me-2"></i>' . get_string('add_break', 'local_schola_timetabler') . '
            </button>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-light p-2.5 rounded-3 mb-3 gap-2">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 450px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
                <input type="text" id="breakSearchInput" class="form-control border-start-0" placeholder="'
                    . s(get_string('search_break_placeholder', 'local_schola_timetabler')) . '" onkeyup="filterBreaks()">
            </div>
            <select class="form-select form-select-sm" style="max-width: 170px;">
                <option value="all">' . get_string('all_schedules', 'local_schola_timetabler') . '</option>
                <option value="class">' . get_string('class_schedules', 'local_schola_timetabler') . '</option>
                <option value="exam">' . get_string('exam_schedules', 'local_schola_timetabler') . '</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted small">
            <span>' . get_string('showing_items', 'local_schola_timetabler', '<strong>' . count($breaks) . '</strong>') . '</span>
            <select class="form-select form-select-sm" style="width: auto;">
                <option>' . get_string('items_per_page', 'local_schola_timetabler', 15) . '</option>
                <option>' . get_string('items_per_page', 'local_schola_timetabler', 25) . '</option>
                <option>' . get_string('items_per_page', 'local_schola_timetabler', 50) . '</option>
            </select>
        </div>
    </div>

    <!-- Breaks Table Grid -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="breaksMasterTable">
            <thead>
                <tr class="text-uppercase font-monospace text-muted small border-bottom bg-light">
                    <th style="width: 40px;" class="py-3 px-3"><input type="checkbox" class="form-check-input"></th>
                    <th style="width: 50px;" class="py-3">#</th>
                    <th class="py-3">' . get_string('col_break_title', 'local_schola_timetabler') . '</th>
                    <th class="py-3">' . get_string('col_day_window', 'local_schola_timetabler') . '</th>
                    <th class="py-3">' . get_string('col_time_slot', 'local_schola_timetabler') . '</th>
                    <th class="py-3">' . get_string('col_schedule', 'local_schola_timetabler') . '</th>
                    <th class="py-3 text-end px-3">' . get_string('col_action', 'local_schola_timetabler') . '</th>
                </tr>
            </thead>
            <tbody>';

if (empty($breaks)) {
    $addbreakbtn = '<strong>+ ' . get_string('add_break', 'local_schola_timetabler') . '</strong>';
    echo '
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fa fa-coffee fa-2x mb-2 d-block text-secondary"></i>
                        ' . get_string('no_breaks_configured', 'local_schola_timetabler', $addbreakbtn) . '
                    </td>
                </tr>';
} else {
    $idx = 1;
    foreach ($breaks as $b) {
        $title = !empty($b->name) ? s($b->name) : (($b->starttime >= '11:30') ?
            get_string('lunch_break', 'local_schola_timetabler') : get_string('tea_break', 'local_schola_timetabler'));
        $daylabel = $daynames[$b->dayofweek] ?? get_string('all_days', 'local_schola_timetabler');
        $timeslot = s($b->starttime) . ' &mdash; ' . s($b->endtime);
        $editurl = new moodle_url('/local/schola_timetabler/breaks.php', ['action' => 'delete', 'id' => $b->id, 'sesskey' => sesskey()]);

        echo '
                <tr class="break-table-row">
                    <td class="px-3"><input type="checkbox" class="form-check-input"></td>
                    <td class="font-monospace text-muted small">' . $idx++ . '</td>
                    <td>
                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1.5 font-weight-bold"
                              style="font-size: 12px; background-color: #fff7ed !important; color: #c2410c !important; border-color: #ffedd5 !important;">
                            ☕ ' . $title . '
                        </span>
                    </td>
                    <td class="fw-bold text-dark font-monospace small">' . $daylabel . '</td>
                    <td class="font-monospace text-muted small">' . $timeslot . '</td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 font-monospace extra-small rounded-pill"
                              style="background-color: #ecfdf5 !important; color: #047857 !important; border-color: #a7f3d0 !important;">
                            ' . get_string('schedule_class_badge', 'local_schola_timetabler') . '
                        </span>
                    </td>
                    <td class="text-end px-3">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 me-1 extra-small"
                                data-bs-toggle="modal" data-bs-target="#editBreakModal' . $b->id . '">
                            ' . get_string('edit', 'local_schola_timetabler') . '
                        </button>
                        <a href="' . $editurl . '" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 extra-small"
                           onclick="return confirm(\'' . s(get_string('confirm_delete_break', 'local_schola_timetabler')) . '\');">
                            ' . get_string('delete', 'local_schola_timetabler') . '
                        </a>
                    </td>
                </tr>

                <!-- Edit Break Modal for each record -->
                <div class="modal fade" id="editBreakModal' . $b->id . '" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                      <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark">' . get_string('edit_break', 'local_schola_timetabler') . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form method="post" action="breaks.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="' . $b->id . '">
                        <input type="hidden" name="sesskey" value="' . sesskey() . '">
                        <div class="modal-body py-3">
                          <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">' . get_string('break_title_label', 'local_schola_timetabler') . '</label>
                            <input type="text" name="title" value="' . s($title) . '" class="form-control rounded-3" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">' . get_string('day_window', 'local_schola_timetabler') . '</label>
                            <select name="daywindow" class="form-select rounded-3">';
        foreach ($daynames as $dval => $dname) {
            $selected = ($b->dayofweek == $dval) ? 'selected' : '';
            echo '<option value="' . $dval . '" ' . $selected . '>' . $dname . '</option>';
        }
        echo '
                            </select>
                          </div>
                          <div class="row g-2 mb-3">
                            <div class="col-6">
                              <label class="form-label small fw-bold text-muted">' . get_string('start_time', 'local_schola_timetabler') . '</label>
                              <input type="time" name="starttime" value="' . s($b->starttime) . '" class="form-control rounded-3" required>
                            </div>
                            <div class="col-6">
                              <label class="form-label small fw-bold text-muted">' . get_string('end_time', 'local_schola_timetabler') . '</label>
                              <input type="time" name="endtime" value="' . s($b->endtime) . '" class="form-control rounded-3" required>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                          <button type="button" class="btn btn-outline-secondary rounded-pill font-weight-bold px-4"
                                  data-bs-dismiss="modal">' . get_string('cancel', 'local_schola_timetabler') . '</button>
                          <button type="submit" class="btn btn-emerald rounded-pill font-weight-bold px-4">'
                              . get_string('save_changes', 'local_schola_timetabler') . '</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>';
    }
}

echo '
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: + Add Institutional Break -->
<div class="modal fade" id="addBreakModal" tabindex="-1" aria-labelledby="addBreakModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="addBreakModalLabel">' . get_string('add_institutional_break', 'local_schola_timetabler') . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="breaks.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="sesskey" value="' . sesskey() . '">
        <div class="modal-body py-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">' . get_string('break_title_label', 'local_schola_timetabler') . '</label>
            <input type="text" name="title" class="form-control rounded-3" placeholder="'
                . s(get_string('break_title_placeholder', 'local_schola_timetabler')) . '" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">' . get_string('day_window', 'local_schola_timetabler') . '</label>
            <select name="daywindow" class="form-select rounded-3">
              <option value="0">' . get_string('all_days', 'local_schola_timetabler') . '</option>
              <option value="1">' . get_string('monday_only', 'local_schola_timetabler') . '</option>
              <option value="2">' . get_string('tuesday_only', 'local_schola_timetabler') . '</option>
              <option value="3">' . get_string('wednesday_only', 'local_schola_timetabler') . '</option>
              <option value="4">' . get_string('thursday_only', 'local_schola_timetabler') . '</option>
              <option value="5">' . get_string('friday_only', 'local_schola_timetabler') . '</option>
              <option value="6">' . get_string('saturday_only', 'local_schola_timetabler') . '</option>
              <option value="7">' . get_string('sunday_only', 'local_schola_timetabler') . '</option>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">' . get_string('start_time', 'local_schola_timetabler') . '</label>
              <input type="time" name="starttime" value="08:00" class="form-control rounded-3" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-bold text-muted">' . get_string('end_time', 'local_schola_timetabler') . '</label>
              <input type="time" name="endtime" value="09:00" class="form-control rounded-3" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">' . get_string('applicable_schedule', 'local_schola_timetabler') . '</label>
            <select name="scheduletype" class="form-select rounded-3">
              <option value="class">' . get_string('all_timetables', 'local_schola_timetabler') . '</option>
              <option value="class">' . get_string('class_schedules_only', 'local_schola_timetabler') . '</option>
              <option value="exam">' . get_string('exam_schedules_only', 'local_schola_timetabler') . '</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill font-weight-bold px-4"
                  data-bs-dismiss="modal">' . get_string('cancel', 'local_schola_timetabler') . '</button>
          <button type="submit" class="btn btn-emerald rounded-pill font-weight-bold px-4">'
              . get_string('add_break_button', 'local_schola_timetabler') . '</button>
        </div>
      </form>
    </div>
</div>
';

$PAGE->requires->js_call_amd('local_schola_timetabler/break_filter', 'init');

echo $OUTPUT->footer();
