<?php
/**
 * Lightweight regression-style fixture for the local OSS solver.
 *
 * This test intentionally avoids Moodle bootstrap dependencies by stubbing
 * the minimum functions the solver touches during a deterministic solve.
 */

declare(strict_types=1);

require_once __DIR__ . '/../classes/licensing/license_manager.php';
require_once __DIR__ . '/../classes/algorithm/solver.php';

function get_config(string $component = 'local_schola_timetabler', string $name = 'day_distribution') {
    return 'balanced';
}

function assert_true(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

// 1) Free OSS feature gate: no course limits in the OSS build.
assert_true(\local_schola_timetabler\licensing\license_manager::get_max_courses() === 0, 'OSS build should expose unlimited local course capacity.');
assert_true(\local_schola_timetabler\licensing\license_manager::can_solve_courses(9999) === true, 'OSS build should never reject a course count.');

// 2) Deterministic solver fixture.
$rooms = [
    (object)['id' => 1, 'name' => 'Room A', 'capacity' => 50, 'is_lab' => 0],
    (object)['id' => 2, 'name' => 'Room B', 'capacity' => 80, 'is_lab' => 0],
];

$slots = [
    (object)['id' => 1, 'type' => 'class', 'dayofweek' => 1, 'exactdate' => 0, 'starttime' => '08:00', 'endtime' => '09:00', 'name' => ''],
    (object)['id' => 2, 'type' => 'class', 'dayofweek' => 1, 'exactdate' => 0, 'starttime' => '09:00', 'endtime' => '10:00', 'name' => ''],
    (object)['id' => 3, 'type' => 'class', 'dayofweek' => 2, 'exactdate' => 0, 'starttime' => '08:00', 'endtime' => '09:00', 'name' => ''],
];

$solver = new \local_schola_timetabler\algorithm\solver($slots, $rooms);
$solver->set_slot_type('class');

$reflection = new ReflectionClass($solver);
$coursesprop = $reflection->getProperty('courses');
$coursesprop->setAccessible(true);

$courses = [
    1 => (object)['id' => 1, 'students' => [101, 102], 'teacher_id' => 10],
    2 => (object)['id' => 2, 'students' => [103], 'teacher_id' => 11],
];

$coursesprop->setValue($solver, $courses);

$solved = $solver->solve_all();
assert_true($solved === true, 'Solver fixture should find a valid assignment for 2 courses.');

$solution = $solver->get_solution();
assert_true(isset($solution['classes']) && count($solution['classes']) === 2, 'The fixture solution should create exactly two schedule assignments.');

foreach ($solution['classes'] as $assignment) {
    assert_true(isset($assignment['course_id'], $assignment['slot_id'], $assignment['room_id'], $assignment['teacher_id']), 'Every assignment should carry course, slot, room and teacher ids.');
}

fwrite(STDOUT, "PASS: OSS solver fixture accepted free license policy and produced a valid solution.\n");
