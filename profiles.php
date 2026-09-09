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
 * Manage Institutional Schedule Profiles for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_schola_timetabler\profile_manager;

require_login();
$context = context_system::instance();
require_capability('local/schola_timetabler:manage', $context);

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$pkey   = optional_param('profile', '', PARAM_ALPHANUMEXT);

$url      = new moodle_url('/local/schola_timetabler/profiles.php');
$slotsurl = new moodle_url('/local/schola_timetabler/slots.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('profiles_title', 'local_schola_timetabler'));
$PAGE->set_heading(get_string('profiles_title', 'local_schola_timetabler'));

// -------------------------------------------------------------------
// Action: Apply Executive / School Schedule Profile
// -------------------------------------------------------------------
if ($action === 'preset' && !empty($pkey) && confirm_sesskey()) {
    $profile = profile_manager::get_profile($pkey);
    if ($profile) {
        $count = profile_manager::apply_profile($pkey);
        $pname = s($profile['name']);
        redirect($slotsurl, "Schedule Profile '{$pname}' applied successfully! {$count} active time slots generated.");
    } else {
        redirect($url, 'Invalid profile key specified.', null, \core\output\notification::NOTIFY_ERROR);
    }
}

// -------------------------------------------------------------------
// Action: Save / Update Schedule Profile Configuration
// -------------------------------------------------------------------
if ($action === 'save_profile' && confirm_sesskey() && data_submitted()) {
    $key         = optional_param('key', '', PARAM_ALPHANUMEXT);
    $name        = optional_param('name', '', PARAM_TEXT);
    $badge       = optional_param('badge', '', PARAM_TEXT);
    $theme       = optional_param('theme', 'primary', PARAM_ALPHA);
    $icon        = optional_param('icon', 'fa-graduation-cap', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);
    $daystart    = optional_param('day_start', '08:00', PARAM_TEXT);
    $dayend      = optional_param('day_end', '17:00', PARAM_TEXT);
    $periodmins  = optional_param('period_minutes', 60, PARAM_INT);
    $teastart    = optional_param('tea_start', '', PARAM_TEXT);
    $teaend      = optional_param('tea_end', '', PARAM_TEXT);
    $lunchstart  = optional_param('lunch_start', '', PARAM_TEXT);
    $lunchend    = optional_param('lunch_end', '', PARAM_TEXT);
    $activedays  = optional_param_array('days', [1, 2, 3, 4, 5], PARAM_INT);
    $applynow    = optional_param('apply_now', 0, PARAM_INT);

    if (empty($key)) {
        $key = 'custom_' . time();
    }

    if (!empty($name)) {
        $profiledata = [
            'key'            => $key,
            'name'           => $name,
            'badge'          => $badge ?: "{$periodmins}-Min Periods",
            'theme'          => $theme,
            'icon'           => $icon,
            'description'    => $description,
            'day_start'      => $daystart,
            'day_end'        => $dayend,
            'period_minutes' => $periodmins,
            'tea_start'      => $teastart,
            'tea_end'        => $teaend,
            'lunch_start'    => $lunchstart,
            'lunch_end'      => $lunchend,
            'days'           => array_map('intval', $activedays),
            'is_default'     => false,
        ];

        profile_manager::save_profile($key, $profiledata);

        $msg = "Schedule Profile '{$name}' saved successfully.";
        if ($applynow) {
            $count = profile_manager::apply_profile($key);
            $msg .= " Applied immediately with {$count} generated active time slots.";
            redirect($slotsurl, $msg);
        } else {
            redirect($url, $msg);
        }
    }
}

// -------------------------------------------------------------------
// Action: Reset Profiles to System Defaults
// -------------------------------------------------------------------
if ($action === 'reset_profiles' && confirm_sesskey()) {
    profile_manager::reset_defaults();
    redirect($url, 'Schedule profiles reset to system default institutional profiles.');
}

// -------------------------------------------------------------------
// Action: Delete Custom Profile
// -------------------------------------------------------------------
if ($action === 'delete_profile' && !empty($pkey) && confirm_sesskey()) {
    profile_manager::delete_profile($pkey);
    redirect($url, 'Custom schedule profile removed.');
}

// -------------------------------------------------------------------
// Prepare Editing Profile Context
// -------------------------------------------------------------------
$editingprofile = null;
if (($action === 'edit_profile' || $action === 'add_profile') && (!empty($pkey) || $action === 'add_profile')) {
    if (!empty($pkey)) {
        $editingprofile = profile_manager::get_profile($pkey);
    }
    if (!$editingprofile) {
        $editingprofile = [
            'key'            => 'profile_' . time(),
            'name'           => '',
            'badge'          => '60-Min Periods',
            'theme'          => 'primary',
            'icon'           => 'fa-graduation-cap',
            'description'    => '',
            'day_start'      => '08:00',
            'day_end'        => '17:00',
            'period_minutes' => 60,
            'tea_start'      => '10:00',
            'tea_end'        => '10:30',
            'lunch_start'    => '12:30',
            'lunch_end'      => '13:30',
            'days'           => [1, 2, 3, 4, 5],
            'is_default'     => false,
        ];
    }
}

echo $OUTPUT->header();

echo \local_schola_timetabler\output\renderer::render_nav_header('profiles');

// -------------------------------------------------------------------
// Form View: Edit / Create Schedule Profile Form Card
// -------------------------------------------------------------------
if ($editingprofile) {
    $formtitle = !empty($editingprofile['name'])
        ? get_string('profile_edit_title', 'local_schola_timetabler') . ': ' . s($editingprofile['name'])
        : get_string('profile_create_title', 'local_schola_timetabler');

    echo html_writer::start_div('card border-0 shadow-lg mb-4 bg-white rounded-3');
    echo html_writer::start_div('card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between');
    echo html_writer::tag('h5', '<i class="fa fa-pen-to-square me-2"></i>' . $formtitle, ['class' => 'mb-0 font-weight-bold']);
    echo html_writer::link($url, '&times; ' . get_string('close_editor', 'local_schola_timetabler'), ['class' => 'btn btn-sm btn-outline-light']);
    echo html_writer::end_div();

    echo html_writer::start_div('card-body p-4');

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save_profile']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'key', 'value' => s($editingprofile['key'])]);

    echo html_writer::start_div('row g-3 mb-3');

    // Profile Title
    echo html_writer::start_div('col-md-5');
    echo html_writer::tag('label', get_string('profile_name_title', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'name', 'value' => s($editingprofile['name']),
        'class' => 'form-control p-2', 'placeholder' => get_string('profile_placeholder_university', 'local_schola_timetabler'), 'required' => 'required',
    ]);
    echo html_writer::end_div();

    // Badge Label
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_badge_subtitle', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'badge', 'value' => s($editingprofile['badge'] ?? ''),
        'class' => 'form-control p-2', 'placeholder' => get_string('profile_badge_placeholder', 'local_schola_timetabler'),
    ]);
    echo html_writer::end_div();

    // Color Theme
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('color_theme', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    $themeoptions = [
        'primary' => get_string('theme_primary', 'local_schola_timetabler'),
        'success' => get_string('theme_success', 'local_schola_timetabler'),
        'info'    => get_string('theme_info', 'local_schola_timetabler'),
        'purple'  => get_string('theme_purple', 'local_schola_timetabler'),
        'warning' => get_string('theme_warning', 'local_schola_timetabler'),
        'dark'    => get_string('theme_dark', 'local_schola_timetabler'),
    ];
    echo html_writer::select($themeoptions, 'theme', $editingprofile['theme'] ?? 'primary', false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Icon Selection
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('profile_icon', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    $iconoptions = [
        'fa-graduation-cap'   => get_string('profile_icon_graduation', 'local_schola_timetabler'),
        'fa-building-columns' => get_string('profile_icon_executive', 'local_schola_timetabler'),
        'fa-school'           => get_string('profile_icon_school', 'local_schola_timetabler'),
        'fa-cubes'            => get_string('profile_icon_modular', 'local_schola_timetabler'),
        'fa-file-signature'   => get_string('profile_icon_exam', 'local_schola_timetabler'),
        'fa-moon'             => get_string('profile_icon_evening', 'local_schola_timetabler'),
        'fa-clock'            => get_string('profile_icon_clock', 'local_schola_timetabler'),
    ];
    echo html_writer::select($iconoptions, 'icon', $editingprofile['icon'] ?? 'fa-graduation-cap', false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Description
    echo html_writer::start_div('col-12');
    echo html_writer::tag('label', get_string('profile_description', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'description', 'value' => s($editingprofile['description'] ?? ''),
        'class' => 'form-control p-2', 'placeholder' => get_string('profile_description_placeholder', 'local_schola_timetabler'),
    ]);
    echo html_writer::end_div();

    // Day Start & End
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_day_start_time', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'time', 'name' => 'day_start',
        'value' => s($editingprofile['day_start'] ?? '08:00'),
        'class' => 'form-control p-2', 'required' => 'required',
    ]);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_day_end_time', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', [
        'type' => 'time', 'name' => 'day_end',
        'value' => s($editingprofile['day_end'] ?? '17:00'),
        'class' => 'form-control p-2', 'required' => 'required',
    ]);
    echo html_writer::end_div();

    // Period Duration
    echo html_writer::start_div('col-md-6');
    echo html_writer::tag('label', get_string('profile_period_duration', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    $periodoptions = [
        45  => get_string('profile_period_45', 'local_schola_timetabler'),
        50  => get_string('profile_period_50', 'local_schola_timetabler'),
        60  => get_string('profile_period_60', 'local_schola_timetabler'),
        90  => get_string('profile_period_90', 'local_schola_timetabler'),
        120 => get_string('profile_period_120', 'local_schola_timetabler'),
        180 => get_string('profile_period_180', 'local_schola_timetabler'),
    ];
    echo html_writer::select($periodoptions, 'period_minutes', (int)($editingprofile['period_minutes'] ?? 60), false, ['class' => 'form-select p-2']);
    echo html_writer::end_div();

    // Tea & Lunch Breaks
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_tea_start_optional', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_start', 'value' => s($editingprofile['tea_start'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_tea_end_optional', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'tea_end', 'value' => s($editingprofile['tea_end'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_lunch_start_optional', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_start', 'value' => s($editingprofile['lunch_start'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('label', get_string('profile_lunch_end_optional', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark']);
    echo html_writer::empty_tag('input', ['type' => 'time', 'name' => 'lunch_end', 'value' => s($editingprofile['lunch_end'] ?? ''), 'class' => 'form-control p-2']);
    echo html_writer::end_div();

    // Active Days
    echo html_writer::start_div('col-12 mt-2');
    echo html_writer::tag('label', get_string('profile_operating_school_days', 'local_schola_timetabler'), ['class' => 'form-label font-weight-bold text-dark d-block']);
    $dayslist = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $activedays = $editingprofile['days'] ?? [1, 2, 3, 4, 5];
    echo html_writer::start_div('d-flex gap-3 flex-wrap');
    foreach ($dayslist as $dnum => $dname) {
        $checked = in_array($dnum, $activedays);
        echo html_writer::start_div('form-check form-check-inline');
        echo html_writer::checkbox('days[]', $dnum, $checked, ' ' . $dname, ['class' => 'form-check-input', 'id' => 'profile_day_' . $dnum]);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div(); // row

    // Submit actions
    echo html_writer::start_div('mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2');
    echo html_writer::start_div('form-check d-flex align-items-center gap-2 mb-0');
    echo html_writer::checkbox(
        'apply_now',
        '1',
        true,
        ' ' . get_string('profile_apply_now', 'local_schola_timetabler'),
        ['class' => 'form-check-input me-1', 'id' => 'chk_apply_now']
    );
    echo html_writer::end_div();

    echo html_writer::start_div('d-flex gap-2');
    echo html_writer::tag('button', get_string('profile_save_button', 'local_schola_timetabler'), ['type' => 'submit', 'class' => 'btn btn-primary font-weight-bold px-4 py-2 shadow-sm']);
    echo html_writer::link($url, get_string('cancel', 'core'), ['class' => 'btn btn-outline-secondary px-3 py-2']);
    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// -------------------------------------------------------------------
// Component: Professional Quick School Schedule Profiles Gallery
// -------------------------------------------------------------------
$profiles = profile_manager::get_profiles();

echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white rounded-3');
echo html_writer::start_div('card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between flex-wrap gap-2');
echo html_writer::start_div('d-flex align-items-center gap-2');
echo html_writer::tag('h5', 'Institutional Schedule Profiles', ['class' => 'mb-0 font-weight-bold']);
echo html_writer::tag('span', count($profiles) . ' Profiles', ['class' => 'badge bg-secondary px-2 py-1 fs-7']);
echo html_writer::end_div();

// Action Toolbar Top-Right
echo html_writer::start_div('d-flex align-items-center gap-2');
$addprofileurl = new moodle_url($url, ['action' => 'add_profile']);
echo html_writer::link($addprofileurl, '+ Add Custom Profile', ['class' => 'btn btn-sm btn-light text-dark font-weight-bold shadow-sm']);

$reseturl = new moodle_url($url, ['action' => 'reset_profiles', 'sesskey' => sesskey()]);
echo html_writer::link($reseturl, 'Reset Defaults', [
    'class' => 'btn btn-sm btn-outline-light opacity-75',
    'onclick' => 'return confirm("Reset all schedule profiles back to institutional defaults?");',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card-body p-4');
$profilemsg = 'Select an official school structure profile to instantly configure your institution\'s ' .
    'weekly timetabling slots, or click <strong>Edit Profile</strong> to customize period lengths and break windows:';
echo html_writer::div($profilemsg, 'text-muted mb-4');

echo html_writer::start_div('row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3');

foreach ($profiles as $pk => $pinfo) {
    $theme = $pinfo['theme'] ?? 'primary';
    $icon  = $pinfo['icon'] ?? 'fa-graduation-cap';
    $name  = s($pinfo['name']);
    $badge = s($pinfo['badge'] ?? (($pinfo['period_minutes'] ?? 60) . '-Min Periods'));
    $desc  = s($pinfo['description'] ?? '');

    $start = s($pinfo['day_start'] ?? '08:00');
    $end   = s($pinfo['day_end'] ?? '17:00');
    $dayscount = count($pinfo['days'] ?? [1, 2, 3, 4, 5]);

    $tea   = (!empty($pinfo['tea_start']) && !empty($pinfo['tea_end'])) ? s($pinfo['tea_start']) . '-' . s($pinfo['tea_end']) : 'None';
    $lunch = (!empty($pinfo['lunch_start']) && !empty($pinfo['lunch_end'])) ? s($pinfo['lunch_start']) . '-' . s($pinfo['lunch_end']) : 'None';

    $applyurl = new moodle_url($url, ['action' => 'preset', 'profile' => $pk, 'sesskey' => sesskey()]);
    $editprofurl = new moodle_url($url, ['action' => 'edit_profile', 'profile' => $pk]);
    $delprofurl  = new moodle_url($url, ['action' => 'delete_profile', 'profile' => $pk, 'sesskey' => sesskey()]);

    echo html_writer::start_div('col');
    echo html_writer::start_div('card h-100 att-profile-card shadow-sm border-0 bg-white position-relative d-flex flex-column');

    // Accent line on top
    echo html_writer::div('', 'card-header-accent att-accent-' . $theme);

    echo html_writer::start_div('card-body p-4 d-flex flex-column');

    // Card Header Info (Icon + Title + Badge)
    echo html_writer::start_div('d-flex align-items-start gap-3 mb-3');
    echo html_writer::div('<i class="fa ' . $icon . '"></i>', 'att-icon-box att-icon-' . $theme);
    echo html_writer::start_div();
    echo html_writer::tag('h5', $name, ['class' => 'card-title mb-1 font-weight-bold text-dark fs-6']);
    echo html_writer::tag('span', $badge, ['class' => 'badge bg-' . ($theme === 'purple' ? 'primary' : $theme) . ' text-white px-2 py-1 fs-7']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Card Description
    echo html_writer::tag('p', $desc, ['class' => 'card-text text-muted small mb-3 flex-grow-1']);

    // Specs Metadata Grid / Pills
    $dayslabel = profile_manager::format_days_label($pinfo['days'] ?? [1, 2, 3, 4, 5]);
    echo html_writer::start_div('d-flex flex-wrap gap-2 mb-3');
    echo html_writer::div('⏱️ <strong>' . $start . ' &ndash; ' . $end . '</strong>', 'att-meta-pill');
    echo html_writer::div('📅 <strong>' . $dayslabel . '</strong>', 'att-meta-pill');
    if ($tea !== 'None') {
        echo html_writer::div('☕ Tea: <strong>' . $tea . '</strong>', 'att-meta-pill');
    }
    if ($lunch !== 'None') {
        echo html_writer::div('🍽️ Lunch: <strong>' . $lunch . '</strong>', 'att-meta-pill');
    }
    echo html_writer::end_div();

    // Card Action Buttons Footer
    echo html_writer::start_div('d-flex align-items-center gap-2 pt-3 border-top mt-auto');

    // Apply Profile Button
    echo html_writer::link($applyurl, '<i class="fa fa-bolt me-1"></i> Apply Profile', [
        'class' => 'btn btn-' . ($theme === 'purple' ? 'primary' : $theme) . ' btn-sm flex-grow-1 font-weight-bold shadow-sm py-2',
        'onclick' => "return confirm('Apply profile \"{$name}\"? This will configure active slots.');",
    ]);

    // Edit Profile Button
    echo html_writer::link($editprofurl, '<i class="fa fa-pen me-1"></i> Edit Profile', [
        'class' => 'btn btn-outline-secondary btn-sm font-weight-bold px-3 py-2',
        'title' => 'Edit profile parameters and break windows',
    ]);

    // Delete custom profile button (if non-default)
    if (empty($pinfo['is_default'])) {
        echo html_writer::link($delprofurl, '<i class="fa fa-trash"></i>', [
            'class' => 'btn btn-outline-danger btn-sm px-2 py-2',
            'title' => 'Delete custom profile',
            'onclick' => "return confirm('Delete custom profile \"{$name}\"?');",
        ]);
    }

    echo html_writer::end_div(); // action row

    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // col
}

echo html_writer::end_div(); // row
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo $OUTPUT->footer();
