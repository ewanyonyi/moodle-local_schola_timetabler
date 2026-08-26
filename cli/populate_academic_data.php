<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Schola Timetabler CLI Data Population Script - Institutional Scale
 *
 * Populates Moodle with substantial data representing a busy educational institution:
 * Faculties, Courses, Quizzes (Exams), Teachers, Students, Course Enrollments,
 * Campus Rooms (Auditoriums, Labs, Lecture Halls), and Master Time Slots.
 *
 * @package    local_schola_timetabler
 * @copyright  2026 Schola Timetabler Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/testing/generator/lib.php');

// Disable outgoing emails for CLI generation performance
$CFG->noemailever = true;

// Parse CLI options
[$options, $unrecognized] = cli_get_params([
    'help'      => false,
    'courses'   => 60,
    'teachers'  => 35,
    'students'  => 300,
    'rooms'     => 25,
    'slots'     => 55,
    'clear'     => false,
], [
    'h' => 'help',
    'c' => 'clear',
]);

if ($options['help']) {
    $help = "Schola Timetabler Test Data Generator CLI (Busy Institution Scale)

Populates Moodle with departments, courses, exams, teachers, students,
course enrollments, and campus infrastructure (rooms & time slots).

Usage:
  php local/schola_timetabler/cli/populate_academic_data.php [options]

Options:
  -h, --help           Show this help message.
  -c, --clear          Clear existing generated test data before populating.
      --courses=60     Number of courses to create (default: 60).
      --teachers=35    Number of faculty members to create (default: 35).
      --students=300   Number of student accounts to create (default: 300).
      --rooms=25       Number of campus rooms to populate (default: 25).
      --slots=20       Number of time slots to populate (default: 20).

Example:
  php local/schola_timetabler/cli/populate_academic_data.php --courses=100 --teachers=50 --students=500
";
    echo $help;
    exit(0);
}

cli_heading("Schola Timetabler - Institutional Scale Data Generator");

global $DB;

// Fetch core role IDs
$teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], 'id', MUST_EXIST);
$studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);

$generator = new testing_data_generator();

// Clear option handling
if ($options['clear']) {
    cli_writeln("Cleaning up previous timetabler infrastructure data and test courses...");
    $DB->delete_records('local_schola_timetabler_schedules');
    $DB->delete_records('local_schola_timetabler_rooms');
    $DB->delete_records('local_schola_timetabler_slots');

    $selectsql = "id > 1 AND (shortname LIKE 'CS%' OR shortname LIKE 'MATH%' OR shortname LIKE 'ENG%' " .
        "OR shortname LIKE 'PHYS%' OR shortname LIKE 'BUS%' OR shortname LIKE 'MED%' " .
        "OR shortname LIKE 'SOC%' OR shortname LIKE 'BIO%' OR shortname LIKE 'LAW%' OR shortname LIKE 'AGR%')";
    $testcourses = $DB->get_records_select('course', $selectsql);
    foreach ($testcourses as $tc) {
        delete_course($tc->id, false);
    }
    cli_writeln("Cleaned plugin tables and deleted " . count($testcourses) . " previous test courses.");
}

// ---------------------------------------------------------
// 1. Create Academic Faculties & Departments (Categories)
// ---------------------------------------------------------
cli_writeln("\n[1/5] Creating Academic Faculties & Departments...");

$departments = [
    'School of Computing & IT',
    'Department of Mathematics & Statistics',
    'School of Engineering & Technology',
    'Department of Physics & Electronics',
    'School of Business & Economics',
    'Faculty of Health Sciences & Medicine',
    'School of Humanities & Social Sciences',
    'Department of Biological Sciences',
    'School of Law & Public Policy',
    'Department of Agricultural Sciences',
];

$categoryids = [];
foreach ($departments as $deptname) {
    $existing = $DB->get_record('course_categories', ['name' => $deptname]);
    if ($existing) {
        $categoryids[] = $existing->id;
    } else {
        $cat = $generator->create_category(['name' => $deptname]);
        $categoryids[] = $cat->id;
        cli_writeln("  + Faculty Created: {$deptname} (ID: {$cat->id})");
    }
}

// ---------------------------------------------------------
// 2. Create Faculty (Teachers) and Students
// ---------------------------------------------------------
cli_writeln("\n[2/5] Generating Faculty Members and Student Body...");

$numteachers = (int)$options['teachers'];
$numstudents = (int)$options['students'];

$teacherids = [];
for ($i = 1; $i <= $numteachers; $i++) {
    $username = "faculty_$i";
    $existing = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    if ($existing) {
        $teacherids[] = $existing->id;
    } else {
        $user = $generator->create_user([
            'username'  => $username,
            'password'  => 'Password123!',
            'firstname' => "Dr.",
            'lastname'  => "FacultyMember_$i",
            'email'     => "faculty_$i@university.edu",
        ]);
        $teacherids[] = $user->id;
    }
}
cli_writeln("  + Created {$numteachers} Faculty accounts (Login: faculty_1 .. faculty_{$numteachers} / Password123!)");

$studentids = [];
for ($i = 1; $i <= $numstudents; $i++) {
    $username = "student_$i";
    $existing = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    if ($existing) {
        $studentids[] = $existing->id;
    } else {
        $user = $generator->create_user([
            'username'  => $username,
            'password'  => 'Password123!',
            'firstname' => "Student",
            'lastname'  => "Learner_$i",
            'email'     => "student_$i@university.edu",
        ]);
        $studentids[] = $user->id;
    }
}
cli_writeln("  + Created {$numstudents} Student accounts (Login: student_1 .. student_{$numstudents} / Password123!)");

// ---------------------------------------------------------
// 3. Create Courses, Quizzes (Exams), and Enrollments
// ---------------------------------------------------------
cli_writeln("\n[3/5] Creating Courses, Midterm/Final Exams, and Managing Enrollments...");

$numcourses = (int)$options['courses'];
$courseprefixes = ['CS', 'MATH', 'ENG', 'PHYS', 'BUS', 'MED', 'SOC', 'BIO', 'LAW', 'AGR'];
$quizgenerator = $generator->get_plugin_generator('mod_quiz');

$createdcourses = 0;
for ($i = 1; $i <= $numcourses; $i++) {
    $prefixindex = ($i - 1) % count($courseprefixes);
    $prefix = $courseprefixes[$prefixindex];
    $level = (int)(($i - 1) / count($courseprefixes)) + 1; // 100, 200, 300, 400 level
    $code = "{$prefix}" . sprintf("%03d", ($level * 100) + ($i % 10));
    $fullname = "{$prefix} Course: {$code} - Advanced Module {$i}";

    $catid = $categoryids[$prefixindex % count($categoryids)];

    $existing = $DB->get_record('course', ['shortname' => $code]);
    if ($existing) {
        $courseid = $existing->id;
    } else {
        $course = $generator->create_course([
            'category'  => $catid,
            'fullname'  => $fullname,
            'shortname' => $code,
            'summary'   => "Curriculum course {$code} for institutional timetabling.",
        ]);
        $courseid = $course->id;
        $createdcourses++;

        // Create 1 Midterm & 1 Final Exam Quiz per course
        $quizgenerator->create_instance([
            'course' => $courseid,
            'name'   => "Midterm Exam - {$code}",
            'timeopen'  => time(),
            'timeclose' => time() + (7 * 86400),
        ]);
        $quizgenerator->create_instance([
            'course' => $courseid,
            'name'   => "Final Exam - {$code}",
            'timeopen'  => time() + (14 * 86400),
            'timeclose' => time() + (21 * 86400),
        ]);
    }

    // Assign 1-2 primary teachers per course
    $primaryteacherid = $teacherids[($i - 1) % count($teacherids)];
    $generator->enrol_user($primaryteacherid, $courseid, $teacherrole->id);

    // Enrol 25-40 students into each course
    $offset = (($i - 1) * 15) % count($studentids);
    $studentsforcourse = array_slice($studentids, $offset, 30);
    if (count($studentsforcourse) < 30) {
        $studentsforcourse = array_merge($studentsforcourse, array_slice($studentids, 0, 30 - count($studentsforcourse)));
    }

    foreach ($studentsforcourse as $studentid) {
        $generator->enrol_user($studentid, $courseid, $studentrole->id);
    }
}
cli_writeln("  + Configured {$numcourses} courses with 2 exams each and active student enrollments.");

// ---------------------------------------------------------
// 4. Populate Campus Infrastructure (Rooms)
// ---------------------------------------------------------
cli_writeln("\n[4/5] Populating Campus Rooms (local_schola_timetabler_rooms)...");

$allrooms = [
    // Major Auditoriums
    ['name' => 'Main Auditorium', 'capacity' => 500, 'is_lab' => 0],
    ['name' => 'Great Hall A', 'capacity' => 400, 'is_lab' => 0],
    ['name' => 'Science Theater B', 'capacity' => 300, 'is_lab' => 0],
    ['name' => 'Engineering Lecture Theater', 'capacity' => 250, 'is_lab' => 0],

    // Lecture Halls
    ['name' => 'Lecture Hall 101', 'capacity' => 180, 'is_lab' => 0],
    ['name' => 'Lecture Hall 102', 'capacity' => 180, 'is_lab' => 0],
    ['name' => 'Lecture Hall 103', 'capacity' => 150, 'is_lab' => 0],
    ['name' => 'Lecture Hall 104', 'capacity' => 150, 'is_lab' => 0],
    ['name' => 'Lecture Hall 105', 'capacity' => 120, 'is_lab' => 0],

    // Specialized Computer & Hardware Labs
    ['name' => 'Computer Lab 1 (AI/ML)', 'capacity' => 50, 'is_lab' => 1],
    ['name' => 'Computer Lab 2 (Software)', 'capacity' => 50, 'is_lab' => 1],
    ['name' => 'Computer Lab 3 (Networks)', 'capacity' => 45, 'is_lab' => 1],
    ['name' => 'Computer Lab 4 (Cyber)', 'capacity' => 40, 'is_lab' => 1],
    ['name' => 'Robotics & Mechatronics Studio', 'capacity' => 35, 'is_lab' => 1],

    // Science & Medical Labs
    ['name' => 'Advanced Physics Lab', 'capacity' => 40, 'is_lab' => 1],
    ['name' => 'Organic Chemistry Lab', 'capacity' => 35, 'is_lab' => 1],
    ['name' => 'Molecular Biology Lab', 'capacity' => 35, 'is_lab' => 1],
    ['name' => 'Medical Simulation Lab', 'capacity' => 30, 'is_lab' => 1],

    // Standard Classrooms & Seminar Rooms
    ['name' => 'Classroom 201', 'capacity' => 70, 'is_lab' => 0],
    ['name' => 'Classroom 202', 'capacity' => 70, 'is_lab' => 0],
    ['name' => 'Classroom 203', 'capacity' => 60, 'is_lab' => 0],
    ['name' => 'Classroom 204', 'capacity' => 60, 'is_lab' => 0],
    ['name' => 'Classroom 205', 'capacity' => 60, 'is_lab' => 0],
    ['name' => 'Seminar Room 301', 'capacity' => 30, 'is_lab' => 0],
    ['name' => 'Seminar Room 302', 'capacity' => 30, 'is_lab' => 0],
    ['name' => 'Moot Court Room', 'capacity' => 40, 'is_lab' => 0],
    ['name' => 'Agriculture Greenhouse Lab', 'capacity' => 30, 'is_lab' => 1],
];

$numrooms = min((int)$options['rooms'], count($allrooms));
$addedrooms = 0;
for ($r = 0; $r < $numrooms; $r++) {
    $roomdata = $allrooms[$r];
    $existing = $DB->get_record('local_schola_timetabler_rooms', ['name' => $roomdata['name']]);
    if (!$existing) {
        $DB->insert_record('local_schola_timetabler_rooms', (object)$roomdata);
        $addedrooms++;
    }
}
cli_writeln("  + Campus infrastructure populated: {$numrooms} rooms in total.");

// ---------------------------------------------------------
// 5. Populate Master Time Slots (Slots)
// ---------------------------------------------------------
cli_writeln("\n[5/5] Populating Master Time Slots (local_schola_timetabler_slots)...");

$allslots = [];

// Class recurring slots across Monday (1) to Friday (5) from 07:00 to 18:00
$dailytimeblocks = [
    ['07:00', '08:30'],
    ['08:30', '10:00'],
    ['10:00', '11:30'],
    ['11:30', '13:00'],
    ['13:00', '14:30'],
    ['14:30', '16:00'],
    ['16:00', '17:30'],
    ['16:30', '18:00'],
];

for ($day = 1; $day <= 5; $day++) {
    foreach ($dailytimeblocks as $block) {
        $allslots[] = [
            'type'      => 'class',
            'dayofweek' => $day,
            'starttime' => $block[0],
            'endtime'   => $block[1],
            'exactdate' => null,
        ];
    }
}

// Exam slots for upcoming exam window (08:00-11:00, 12:00-15:00, 15:00-18:00)
$baseexamdate = strtotime('next Monday');
for ($d = 0; $d < 5; $d++) {
    $examtimestamp = $baseexamdate + ($d * 86400);
    $allslots[] = [
        'type'      => 'exam',
        'dayofweek' => null,
        'starttime' => '08:00',
        'endtime'   => '11:00',
        'exactdate' => $examtimestamp,
    ];
    $allslots[] = [
        'type'      => 'exam',
        'dayofweek' => null,
        'starttime' => '12:00',
        'endtime'   => '15:00',
        'exactdate' => $examtimestamp,
    ];
    $allslots[] = [
        'type'      => 'exam',
        'dayofweek' => null,
        'starttime' => '15:00',
        'endtime'   => '18:00',
        'exactdate' => $examtimestamp,
    ];
}

$numslots = min((int)($options['slots'] ?? 50), count($allslots));
for ($s = 0; $s < $numslots; $s++) {
    $slotdata = $allslots[$s];
    $existing = $DB->get_record('local_schola_timetabler_slots', [
        'type'      => $slotdata['type'],
        'starttime' => $slotdata['starttime'],
        'endtime'   => $slotdata['endtime'],
        'dayofweek' => $slotdata['dayofweek'],
        'exactdate' => $slotdata['exactdate'],
    ]);
    if (!$existing) {
        $DB->insert_record('local_schola_timetabler_slots', (object)$slotdata);
    }
}
cli_writeln("  + Master schedule time slots populated: {$numslots} active time windows.");

cli_heading("Institutional Data Generation Complete!");
cli_writeln("Summary:");
cli_writeln("  - Academic Faculties: " . count($categoryids));
cli_writeln("  - Faculty (Teachers): {$numteachers} (Login: faculty_1 / Password: Password123!)");
cli_writeln("  - Students Enrolled:  {$numstudents} (Login: student_1 / Password: Password123!)");
cli_writeln("  - Total Courses:      {$numcourses} (with ~" . ($numcourses * 2) . " total exams)");
cli_writeln("  - Total Campus Rooms: " . $DB->count_records('local_schola_timetabler_rooms'));
cli_writeln("  - Total Time Slots:   " . $DB->count_records('local_schola_timetabler_slots'));
cli_writeln("\nYour Moodle instance now represents a busy academic institution!");
