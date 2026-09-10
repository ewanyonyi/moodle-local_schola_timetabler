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

namespace local_schola_timetabler\licensing;

/**
 * Free-only license compatibility manager for local_schola_timetabler.
 *
 * @package     local_schola_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class license_manager {
    /**
     * Free OSS plugin is always available locally.
     *
     * @return string Active edition identifier.
     */
    public static function get_tier(): string {
        return 'community';
    }

    /**
     * Human-readable display name for the free OSS edition.
     *
     * @return string Display name.
     */
    public static function get_tier_name(): string {
        return 'Free Local Edition';
    }

    /**
     * This codebase is OSS-only and intentionally has no paid tier.
     *
     * @return bool Always false.
     */
    public static function is_pro(): bool {
        return false;
    }

    /**
     * Free local plugin is always available.
     *
     * @return bool Always true.
     */
    public static function is_starter_or_higher(): bool {
        return true;
    }

    /**
     * Unlimited local course capacity in the free OSS build.
     *
     * @return int Always 0 meaning unlimited.
     */
    public static function get_max_courses(): int {
        return 0;
    }

    /**
     * Unlimited local room capacity in the free OSS build.
     *
     * @return int Always 0 meaning unlimited.
     */
    public static function get_max_rooms(): int {
        return 0;
    }

    /**
     * Verify schedule size against constraints configured by the free OSS build.
     *
     * @param int $coursecount Total courses to solve.
     * @return bool Always true for the OSS build.
     */
    public static function can_solve_courses(int $coursecount): bool {
        return true;
    }

    /**
     * CSV room import remains available in the free local edition.
     *
     * @return bool Always true.
     */
    public static function can_batch_import_rooms(): bool {
        return true;
    }

    /**
     * Examinations are supported in the free local edition.
     *
     * @return bool Always true.
     */
    public static function can_solve_exams(): bool {
        return true;
    }
}
