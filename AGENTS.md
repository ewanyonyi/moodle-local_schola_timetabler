# AGENTS.md — Guidelines for AI Agents (Moodle Plugin)

This document defines architectural conventions, Moodle coding standards, and development practices for AI coding assistants (including Antigravity, Claude, Copilot) operating on the `local_schola_timetabler` Moodle plugin codebase.

---

## 🎯 1. Project Purpose

`local_schola_timetabler` is an administrative Moodle plugin that manages institutional timetabling for courses, campus venues, time slots, and break windows. It features dual constraint satisfaction engines:
- **Native PHP Engine**: Built-in local constraint solver algorithm for standard timetables.
- **Free Local Solver Engine**: Native Moodle-safe PHP scheduling engine that runs entirely on the local server.

---

## 🛠️ 2. Tech Stack Specification

- **Platform**: Moodle LMS (PHP 8.1+, MySQL / PostgreSQL / MariaDB)
- **UI Framework**: Bootstrap 5 + Moodle `html_writer` & class renderers
- **Architecture**: PSR-4 autoloading (`local_schola_timetabler\...`), Moodle DB Abstraction Layer (`$DB`)

---

## 📐 3. Key Directory Structure

```
local/schola_timetabler/
├── index.php         <-- Admin overview & solver execution handler
├── rooms.php         <-- Campus venue, laboratory & room capacity management
├── profiles.php      <-- Institutional weekly schedule profile presets
├── slots.php         <-- Active time slots listing, guided wizard & CSV batch import
├── breaks.php        <-- Tea, lunch, and blockout window settings
├── schedules.php     <-- Timetable management studio & grid matrix view
├── export.php        <-- PDF printing & CSV matrix exporter
├── help.php          <-- Integrated documentation & administrator guide
├── version.php       <-- Plugin release version metadata
├── classes/          <-- Core OOP classes & business logic
│   ├── algorithm/    <-- Native PHP solver engine
│   ├── licensing/    <-- Free OSS compatibility and edition metadata
│   ├── output/       <-- Navigation header & UI renderers
│   └── profile_manager.php <-- Institutional schedule profile logic
├── db/               <-- Database schema (install.xml), upgrade script, and access capabilities
└── lang/en/          <-- Localization language strings
```

---

## ⚠️ 4. Rules for AI Agents

1. **Moodle Coding Standards & Security**:
   - Always enforce capability checks: `require_capability('local/schola_timetabler:manage', $context)`.
   - Never use raw SQL string concatenation; always use parameter binding with Moodle `$DB`.
   - Sanitize all parameters using `required_param()` or `optional_param()` with explicit types (`PARAM_INT`, `PARAM_ALPHA`, `PARAM_TEXT`).

2. **CodeSniffer (`phpcs`) Linting & Compliance**:
   - Verify all modified PHP files against Moodle coding standards by running:
     `/home/ewanyonyi/.config/composer/vendor/bin/phpcs -n --standard=moodle <filepath>`
   - **Variable Naming Rule**: Variable names in procedural/script files must be alphanumeric without underscores (e.g. `$hastitlecol` instead of `$has_title_col`, `$headermap` instead of `$header_map`).
   - **Line Length**: Ensure line lengths do not exceed 180 characters.
   - **Docblocks**: Provide full PHPDoc docblocks for functions, methods, classes, and file headers.

3. **Moodle Plugin Versioning Policy**:
   - **Only update the Moodle plugin version (`version.php`) when generating or packaging a `.zip` release file for distribution.**
   - Do NOT bump `version.php` version numbers during routine code edits, UI polish, or feature development.

5. **Local-Only Solver Contract**:
   - Data structures used by the local solver (`classes/algorithm/solver.php`) must remain compatible with the native Moodle database model.
   - Supported timetable types are strictly limited to `class` (Regular Semester Class Schedule) and `exam` (Examination Schedule).

5. **UI & Navigation Consistency**:
   - The top navigation bar is generated via `\local_schola_timetabler\output\renderer::render_nav_header($activepage)`.
   - The header **`Generate Timetable`** action button should ONLY be displayed when the active page is `schedules.php` or `index.php`.
