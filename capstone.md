# CAPSTONE PROJECT SPECIFICATION: Moodle Academic & Exam Timetabler (`local_schola_timetabler`)

## Executive Summary
**`local_schola_timetabler`** is a free, open-source, fully self-contained Moodle local plugin designed to solve both **routine course/class scheduling** (weekly recurring lectures, labs, seminars) and **exam timetabling** (midterms, finals) directly inside a Moodle server environment.

The solution provides an on-premise native PHP solver engine adhering strictly to Moodle Frankensytle standards, GPL-3.0 licensing, and GDPR privacy compliance for local institutional timetabling workloads.

---

## 1. System Requirements & Constraints

* **Native On-Premise Execution:** Standard computation executes within Moodle's native PHP environment without reliance on external services.
* **Asynchronous Background Processing:** Heavy constraint-solving routines execute via Moodle's Task API (`\core\task\adhoc_task`) executed via CLI `cron`, preventing web request timeouts (`max_execution_time`).
* **Zero Student & Teacher Conflicts (Hard Constraints):**
  * No student can be scheduled for two overlapping classes or exams.
  * No teacher can be assigned to teach or proctor two different rooms simultaneously.
  * Physical room capacities cannot be exceeded by enrolled student counts.
  * Specialized class requirements (e.g., Computer/Science Labs) must be mapped to compatible rooms (`is_lab = 1`).
* **Native Moodle Integration:**
  * Synchronizes weekly class schedules into core Moodle Calendar events (`mdl_event`).
  * Automatically sets Moodle Quiz timing windows (`timeopen` and `timeclose` in `mdl_quiz`).

---

## 2. Directory & Component Architecture

```text
moodle/local/schola_timetabler/
├── classes/
│   ├── algorithm/
│   │   └── solver.php               # Native PHP Constraint Solver Engine
│   ├── output/
│   │   └── renderer.php             # Page Renderer
│   ├── task/
│   │   └── generate_timetable.php   # Background Ad-hoc Task Runner
│   └── privacy/
│       └── provider.php             # GDPR Privacy API Compliance
├── db/
│   ├── access.php                   # Capability Definitions (local/schola_timetabler:manage)
│   ├── install.xml                  # XMLDB Database Schema
│   ├── tasks.php                    # Task Registration
│   └── upgrade.php                  # Database Upgrade Handler
├── lang/
│   └── en/
│       └── local_schola_timetabler.php # English Language Strings
├── templates/
│   └── dashboard.mustache           # HTML/Mustache Admin Interface
├── index.php                        # Admin Controller View
├── settings.php                     # Moodle Site Admin Menu Integration
├── version.php                      # Frankenstyle Metadata
└── README.md                        # Documentation
```

---

## 3. Database Schema Specification (`db/install.xml`)

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/schola_timetabler/db" VERSION="20260812" COMMENT="Schema for unified course and exam timetabler">
  <TABLES>
    <!-- Physical Campus Infrastructure -->
    <TABLE NAME="local_ss_rooms" COMMENT="Campus rooms and capabilities">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="name" TYPE="char" LENGTH="100" NOTNULL="true"/>
        <FIELD NAME="capacity" TYPE="int" LENGTH="10" NOTNULL="true" DEFAULT="0"/>
        <FIELD NAME="is_lab" TYPE="int" LENGTH="1" NOTNULL="true" DEFAULT="0" COMMENT="1 for specialized lab/studio"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
      </KEYS>
    </TABLE>

    <!-- Master Time Slots (Classes and Exams) -->
    <TABLE NAME="local_ss_slots" COMMENT="Available time windows">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="type" TYPE="char" LENGTH="20" NOTNULL="true" DEFAULT="class" COMMENT="class or exam"/>
        <FIELD NAME="dayofweek" TYPE="int" LENGTH="1" NOTNULL="false" COMMENT="1=Mon, 7=Sun for recurring classes"/>
        <FIELD NAME="exactdate" TYPE="int" LENGTH="10" NOTNULL="false" COMMENT="Unix timestamp for specific exam dates"/>
        <FIELD NAME="starttime" TYPE="char" LENGTH="5" NOTNULL="true" COMMENT="HH:MM"/>
        <FIELD NAME="endtime" TYPE="char" LENGTH="5" NOTNULL="true" COMMENT="HH:MM"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
      </KEYS>
    </TABLE>

    <!-- Unified Schedule Persistence -->
    <TABLE NAME="local_ss_schedules" COMMENT="Generated master schedule">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="schedule_type" TYPE="char" LENGTH="20" NOTNULL="true" COMMENT="class or exam"/>
        <FIELD NAME="courseid" TYPE="int" LENGTH="10" NOTNULL="true"/>
        <FIELD NAME="quizid" TYPE="int" LENGTH="10" NOTNULL="false" COMMENT="Null for class sessions"/>
        <FIELD NAME="roomid" TYPE="int" LENGTH="10" NOTNULL="true"/>
        <FIELD NAME="slotid" TYPE="int" LENGTH="10" NOTNULL="true"/>
        <FIELD NAME="teacherid" TYPE="int" LENGTH="10" NOTNULL="true"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
      </KEYS>
    </TABLE>
  </TABLES>
</XMLDB>
```

---

## 4. Code Implementation Details

### 4.1 Metadata (`version.php`)
```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_schola_timetabler'; 
$plugin->version   = 2026081200;              // YYYYMMDDXX format
$plugin->requires  = 2024042200;              // Minimum Moodle 4.4+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
```

### 4.2 Capability & Access Control (`db/access.php`)
```php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/schola_timetabler:manage' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
```

### 4.3 Native PHP Solver Engine (`classes/algorithm/solver.php`)
```php
namespace local_schola_timetabler\algorithm;

defined('MOODLE_INTERNAL') || die();

class solver {
    private array $courses = [];
    private array $quizzes = [];
    private array $slots = [];
    private array $rooms = [];
    private array $solution = [];

    public function __construct(array $slots, array $rooms) {
        $this->slots = $slots;
        $this->rooms = $rooms;
    }

    public function load_courses(array $courses): void {
        global $DB;
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $students = get_enrolled_users($context, 'moodle/course:view', 0, 'u.id');
            $teachers = get_enrolled_users($context, 'moodle/course:update', 0, 'u.id');

            $this->courses[$course->id] = (object)[
                'id' => $course->id,
                'students' => array_keys($students),
                'teacher_id' => !empty($teachers) ? reset($teachers)->id : 0,
            ];
        }
    }

    public function solve_all(): bool {
        // Most-Constrained-First ordering by enrolled count
        usort($this->courses, fn($a, $b) => count($b->students) <=> count($a->students));
        return $this->backtrack_classes(0);
    }

    private function backtrack_classes(int $index): bool {
        if ($index >= count($this->courses)) {
            return true;
        }

        $course = array_values($this->courses)[$index];
        $class_slots = array_filter($this->slots, fn($s) => $s->type === 'class');

        foreach ($class_slots as $slot) {
            foreach ($this->rooms as $room) {
                if ($this->is_valid_class_assignment($course, $slot, $room)) {
                    $this->solution['classes'][$course->id] = [
                        'slot_id' => $slot->id,
                        'room_id' => $room->id,
                        'teacher_id' => $course->teacher_id,
                    ];

                    if ($this->backtrack_classes($index + 1)) {
                        return true;
                    }

                    unset($this->solution['classes'][$course->id]);
                }
            }
        }

        return false;
    }

    private function is_valid_class_assignment($course, $slot, $room): bool {
        if ($room->capacity < count($course->students)) {
            return false;
        }

        foreach ($this->solution['classes'] ?? [] as $assigned_course_id => $assignment) {
            if ($assignment['slot_id'] == $slot->id) {
                if ($assignment['room_id'] == $room->id) {
                    return false;
                }
                if ($assignment['teacher_id'] == $course->teacher_id) {
                    return false;
                }
                $assigned_course = $this->courses[$assigned_course_id];
                $shared_students = array_intersect($course->students, $assigned_course->students);
                if (!empty($shared_students)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function get_solution(): array {
        return $this->solution;
    }
}
```

### 4.4 Background Task Processor (`classes/task/generate_timetable.php`)
```php
namespace local_schola_timetabler\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use local_schola_timetabler\algorithm\solver;

class generate_timetable extends adhoc_task {

    public function execute() {
        global $DB;

        \core_php_time_limit::raise(600);
        raise_memory_limit(MEMORY_EXTRA);

        mtrace("Executing native Course and Exam Timetabling Engine...");

        $courses = $DB->get_records('course', ['visible' => 1]);
        $slots   = $DB->get_records('local_ss_slots');
        $rooms   = $DB->get_records('local_ss_rooms');

        $solver = new solver($slots, $rooms);
        $solver->load_courses($courses);

        if ($solver->solve_all()) {
            $solution = $solver->get_solution();
            $DB->delete_records('local_ss_schedules');

            foreach ($solution['classes'] ?? [] as $courseid => $sched) {
                $DB->insert_record('local_ss_schedules', (object)[
                    'schedule_type' => 'class',
                    'courseid'     => $courseid,
                    'roomid'       => $sched['room_id'],
                    'slotid'       => $sched['slot_id'],
                    'teacherid'    => $sched['teacher_id'],
                ]);

                $event = new \stdClass();
                $event->name        = 'Weekly Lecture - Room ' . $rooms[$sched['room_id']]->name;
                $event->courseid    = $courseid;
                $event->eventtype   = 'course';
                $event->timestart   = time();
                $event->timeduration = 5400;
                \calendar_event::create($event);
            }

            mtrace("Schedules successfully generated and synchronized!");
        } else {
            mtrace("Error: Unable to find a conflict-free schedule.");
        }
    }
}
```

---

## 5. Deployment Strategy

| Edition | Target Market | Key Capabilities |
|---|---|---|
| **Free Local Edition** | Schools and institutions running Moodle locally | Native PHP solver, room and slot management, exam generation, timetable exports, and local-only operation. |

---

## 6. Testing & Validation Commands

1. **Install Schema Updates:**
   ```bash
   php admin/cli/upgrade.php
   ```
2. **Execute Moodle Code Checker (PHPCS):**
   ```bash
   vendor/bin/phpcs --standard=moodle local/schola_timetabler
   ```
3. **Execute Solver Task in Background CLI:**
   ```bash
   php admin/cli/adhoc_task.php --execute
   ```
