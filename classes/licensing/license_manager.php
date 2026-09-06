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
    /** @var string Free edition tier identifier. */
    public const TIER_STARTER = 'starter';

    /** @var string Legacy identifier retained for compatibility. */
    public const TIER_PRO = 'pro';

    /** @var int Default course limit for the free local plugin. */
    public const STARTER_COURSE_LIMIT = 50;

    /** @var int Default room limit for the free local plugin. */
    public const STARTER_ROOM_LIMIT = 25;

    /**
     * Legacy checkout URL compatibility hook.
     *
     * @return string Empty string; free plugin does not use commercial checkout flow.
     */
    public static function get_checkout_url(): string {
        return '';
    }

    /**
     * Legacy license key getter. Free plugin does not require external licenses.
     *
     * @return string Empty string.
     */
    public static function get_license_key(): string {
        return '';
    }

    /**
     * Free local plugin always uses the starter/free tier.
     *
     * @return string Active tier.
     */
    public static function get_tier(): string {
        return self::TIER_STARTER;
    }

    /**
     * Human-readable display name for the free edition.
     *
     * @return string Display name.
     */
    public static function get_tier_name(): string {
        return 'Free Local Edition';
    }

    /**
     * Free plugin never uses a paid tier.
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
     * Free plugin local limit.
     *
     * @return int Max courses allowed locally.
     */
    public static function get_max_courses(): int {
        return self::STARTER_COURSE_LIMIT;
    }

    /**
     * Free plugin local limit for rooms.
     *
     * @return int Max rooms allowed locally.
     */
    public static function get_max_rooms(): int {
        return self::STARTER_ROOM_LIMIT;
    }

    /**
     * Verify schedule size against the free local limit.
     *
     * @param int $coursecount Total courses to solve.
     * @return bool True if supported.
     */
    public static function can_solve_courses(int $coursecount): bool {
        return $coursecount <= self::STARTER_COURSE_LIMIT;
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
