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

namespace local_schola_timetabler\algorithm;

use local_schola_timetabler\licensing\license_manager;

/**
 * Constraint solver algorithm for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class solver {
    /** @var array Array of course objects. */
    private array $courses = [];

    /** @var array Array of quiz objects. */
    private array $quizzes = [];

    /** @var array Array of available time slot records. */
    private array $slots = [];

    /** @var array Array of campus room records. */
    private array $rooms = [];

    /** @var array Solution mapping output. */
    private array $solution = [];

    /**
     * Constructor for solver.
     *
     * @param array $slots Available slots.
     * @param array $rooms Available rooms.
     */
    public function __construct(array $slots, array $rooms) {
        $this->slots = $slots;
        $this->rooms = $rooms;
    }

    /**
     * Load course and enrollment metadata.
     *
     * @param array $courses Courses list.
     * @return void
     * @throws \moodle_exception If license tier course limits are exceeded.
     */
    public function load_courses(array $courses): void {
        $coursecount = count($courses);
        if (!license_manager::can_solve_courses($coursecount)) {
            $limit = license_manager::STARTER_COURSE_LIMIT;
            throw new \moodle_exception(
                'license_err_limit',
                'local_schola_timetabler',
                '',
                [$coursecount, $limit]
            );
        }

        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'moodle/course:view', 0, 'u.id');
            $teachers = get_enrolled_users($context, 'moodle/course:update', 0, 'u.id');

            $teacherid = !empty($teachers) ? reset($teachers)->id : 0;
            $this->courses[$course->id] = (object)[
                'id' => $course->id,
                'students' => array_keys($students),
                'teacher_id' => $teacherid,
            ];
        }
    }

    /** @var string Slot type to filter for generation (class or exam). */
    private string $slottype = 'class';

    /**
     * Set target slot type for schedule generation.
     *
     * @param string $type Slot type (class or exam).
     * @return void
     */
    public function set_slot_type(string $type): void {
        $this->slottype = $type;
    }

    /**
     * Load existing schedule assignments as hard conflict blockouts.
     *
     * @param array $existingschedules Array of existing schedule records.
     * @return void
     */
    public function load_existing_schedules(array $existingschedules): void {
        foreach ($existingschedules as $s) {
            $key = 'existing_' . $s->id;
            $this->solution['classes'][$key] = [
                'course_id' => $s->courseid,
                'slot_id'   => $s->slotid,
                'room_id'   => $s->roomid,
                'teacher_id' => $s->teacherid,
                'note'      => 'Existing Blockout',
            ];
        }
    }

    /**
     * Solve schedule for all loaded courses.
     *
     * @return bool True if solution found, false otherwise.
     */
    public function solve_all(): bool {
        if (license_manager::is_pro()) {
            if ($this->call_cloud_solver()) {
                return true;
            }
            // If Cloud solver call fails during Pro mode, check course count.
            // If dataset exceeds local PHP capability (> 50 courses), inform user.
            if (count($this->courses) > license_manager::STARTER_COURSE_LIMIT) {
                throw new \moodle_exception(
                    'cloud_offline_limit_err',
                    'local_schola_timetabler'
                );
            }
        }

        // Native PHP Solver Fallback (for datasets under Starter limit)
        uasort($this->courses, fn($a, $b) => count($b->students) <=> count($a->students));
        return $this->backtrack_classes(0);
    }

    /**
     * Dispatch payload to 100% Rust Cloud Solver Engine over REST API.
     *
     * @return bool True if successfully solved by Cloud Engine.
     */
    public function call_cloud_solver(): bool {
        global $CFG;

        $coursespayload = [];
        foreach ($this->courses as $c) {
            $coursespayload[] = [
                'id' => (int)$c->id,
                'code' => 'COURSE_' . $c->id,
                'name' => 'Course ' . $c->id,
                'weekly_sessions' => 1,
                'is_lab_required' => false,
                'lecturer_id' => (int)$c->teacher_id,
            ];
        }

        $roomspayload = [];
        foreach ($this->rooms as $r) {
            $roomspayload[] = [
                'id' => (int)$r->id,
                'name' => (string)$r->name,
                'capacity' => (int)$r->capacity,
                'is_lab' => (!empty($r->is_lab) && $r->is_lab == 1),
            ];
        }

        $slotspayload = [];
        foreach ($this->slots as $s) {
            if ($s->type === 'break') {
                continue;
            }
            $slotspayload[] = [
                'id' => (int)$s->id,
                'day_of_week' => (int)$s->dayofweek,
                'start_time' => (string)$s->starttime,
                'end_time' => (string)$s->endtime,
            ];
        }

        $licensekey = license_manager::get_license_key();

        $payload = [
            'courses' => $coursespayload,
            'rooms' => $roomspayload,
            'slots' => $slotspayload,
            'options' => [
                'day_distribution' => get_config('local_schola_timetabler', 'day_distribution') ?: 'balanced',
                'max_iterations' => 100000,
                'license_key' => $licensekey,
            ],
        ];

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl(['CURLOPT_TIMEOUT' => 5, 'CURLOPT_CONNECTTIMEOUT' => 3]);
        $curl->setHeader('Content-Type: application/json');
        if (!empty($licensekey)) {
            $curl->setHeader('X-License-Key: ' . $licensekey);
        }
        $configuredhost = get_config('local_schola_timetabler', 'solver_url');
        $cloudurl = !empty($configuredhost) ? trim((string)$configuredhost) : 'https://scholaslots.com/api/v1/solve';

        $response = $curl->post($cloudurl, json_encode($payload));
        if ($curl->get_errno() || empty($response)) {
            // Fallback for local development environments running Rust solver locally
            $localurl = 'http://localhost:8080/api/v1/solve';
            $response = $curl->post($localurl, json_encode($payload));
            if ($curl->get_errno() || empty($response)) {
                return false;
            }
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['status']) || $data['status'] === 'error') {
            return false;
        }

        if (!empty($data['assignments']) && is_array($data['assignments'])) {
            foreach ($data['assignments'] as $assign) {
                $cid = $assign['course_id'];
                $this->solution['classes'][$cid] = [
                    'course_id' => $cid,
                    'slot_id'   => $assign['slot_id'],
                    'room_id'   => $assign['room_id'],
                    'teacher_id' => $this->courses[$cid]->teacher_id ?? 0,
                    'note'      => 'Rust Cloud Acceleration Engine',
                ];
            }
            return true;
        }

        return false;
    }

    /**
     * Recursive backtracking to assign rooms and slots to courses.
     *
     * @param int $index Current course index in array.
     * @return bool True if successful assignment sequence completed.
     */
    private function backtrack_classes(int $index): bool {
        $courselist = array_values($this->courses);
        if ($index >= count($courselist)) {
            return true;
        }
        $course = $courselist[$index];

        $targettype = $this->slottype;
        $classslots = array_values(array_filter($this->slots, function ($s) use ($targettype) {
            // Strictly exclude break / blockout slots from scheduling
            if ($s->type === 'break') {
                return false;
            }
            if ($s->type === $targettype) {
                return true;
            }
            if ($targettype === 'class' && $s->type === 'lab') {
                return true;
            }
            // Fallback if no specific exam slots configured
            return ($targettype === 'exam' && $s->type === 'class');
        }));

        $strategy = get_config('local_schola_timetabler', 'day_distribution') ?: 'balanced';

        if ($strategy === 'mon_to_sat') {
            $targetday = ($index % 6) + 1;
            usort($classslots, function ($a, $b) use ($targetday) {
                $adist = ($a->dayofweek == $targetday) ? 0 : 1;
                $bdist = ($b->dayofweek == $targetday) ? 0 : 1;
                return ($adist !== $bdist) ? ($adist <=> $bdist) : ($a->dayofweek <=> $b->dayofweek);
            });
        } else if ($strategy === 'mon_to_thu') {
            $targetday = ($index % 4) + 1;
            usort($classslots, function ($a, $b) use ($targetday) {
                $adist = ($a->dayofweek == $targetday) ? 0 : 1;
                $bdist = ($b->dayofweek == $targetday) ? 0 : 1;
                return ($adist !== $bdist) ? ($adist <=> $bdist) : ($a->dayofweek <=> $b->dayofweek);
            });
        } else if ($strategy === 'frontload') {
            // Preserve standard sequential dayofweek order (1..5).
            usort($classslots, function ($a, $b) {
                return $a->dayofweek <=> $b->dayofweek;
            });
        } else {
            // Default 'balanced': Distribute across 5 days (1 to 5)
            $targetday = ($index % 5) + 1;
            usort($classslots, function ($a, $b) use ($targetday) {
                $adist = ($a->dayofweek == $targetday) ? 0 : 1;
                $bdist = ($b->dayofweek == $targetday) ? 0 : 1;
                return ($adist !== $bdist) ? ($adist <=> $bdist) : ($a->dayofweek <=> $b->dayofweek);
            });
        }

        foreach ($classslots as $slot) {
            foreach ($this->rooms as $room) {
                // Check if this course requires a double-lesson (e.g. lab room or specialized course)
                $requiresdouble = ($room->is_lab == 1) || (isset($course->is_double) && $course->is_double);

                if ($requiresdouble) {
                    $nextslot = $this->get_consecutive_slot($slot);
                    if ($nextslot && $this->is_valid_class_assignment($course, $slot, $room) && $this->is_valid_class_assignment($course, $nextslot, $room)) {
                        $this->solution['classes'][$course->id . '_1'] = [
                            'course_id' => $course->id,
                            'slot_id'   => $slot->id,
                            'room_id'   => $room->id,
                            'teacher_id' => $course->teacher_id,
                            'note'      => 'Double Lesson (Block 1)',
                        ];
                        $this->solution['classes'][$course->id . '_2'] = [
                            'course_id' => $course->id,
                            'slot_id'   => $nextslot->id,
                            'room_id'   => $room->id,
                            'teacher_id' => $course->teacher_id,
                            'note'      => 'Double Lesson (Block 2)',
                        ];

                        if ($this->backtrack_classes($index + 1)) {
                            return true;
                        }

                        unset($this->solution['classes'][$course->id . '_1']);
                        unset($this->solution['classes'][$course->id . '_2']);
                    }
                } else {
                    if ($this->is_valid_class_assignment($course, $slot, $room)) {
                        $this->solution['classes'][$course->id] = [
                            'course_id' => $course->id,
                            'slot_id'   => $slot->id,
                            'room_id'   => $room->id,
                            'teacher_id' => $course->teacher_id,
                            'note'      => 'Single Session',
                        ];

                        if ($this->backtrack_classes($index + 1)) {
                            return true;
                        }

                        unset($this->solution['classes'][$course->id]);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Find immediate consecutive back-to-back time slot on the same day.
     *
     * @param object $slot Primary slot.
     * @return object|null Consecutive slot record or null.
     */
    private function get_consecutive_slot(object $slot): ?object {
        foreach ($this->slots as $s) {
            if ($s->type === 'class' && $s->dayofweek == $slot->dayofweek && $s->starttime === $slot->endtime) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Verify if candidate class assignment satisfies hard constraints.
     *
     * @param object $course Course object.
     * @param object $slot Time slot object.
     * @param object $room Room object.
     * @return bool True if assignment is valid.
     */
    private function is_valid_class_assignment($course, $slot, $room): bool {
        $studentcount = (is_array($course->students) || $course->students instanceof \Countable) ? count($course->students) : 0;
        if ($room->capacity < $studentcount) {
            return false;
        }

        foreach ($this->solution['classes'] ?? [] as $assignedkey => $assignment) {
            if ($assignment['slot_id'] == $slot->id) {
                if ($assignment['room_id'] == $room->id) {
                    return false;
                }
                if ($course->teacher_id > 0 && $assignment['teacher_id'] == $course->teacher_id) {
                    return false;
                }
                $assignedcourseid = $assignment['course_id'] ?? $assignedkey;
                if (isset($this->courses[$assignedcourseid])) {
                    $assignedcourse = $this->courses[$assignedcourseid];
                    $sharedstudents = array_intersect($course->students, $assignedcourse->students);
                    if (!empty($sharedstudents)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Retrieve calculated schedule solution.
     *
     * @return array Solution data structure.
     */
    public function get_solution(): array {
        return $this->solution;
    }
}
